<?php

namespace App\Jobs;

use App\Events\WhatsAppMessageReceived;
use App\Models\Setting;
use App\Models\WhatsAppPhoneNumber;
use App\Services\Chat\ConversationService;
use App\Services\Chat\MessageService;
use App\Services\Chat\WorkflowEngine;
use App\Services\PushNotificationService;
use App\Services\WhatsApp\WhatsAppApiService;
use App\Services\WhatsApp\WhatsAppWebhookParser;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessIncomingWhatsAppMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(
        private array $payload,
        private int $phoneNumberId
    ) {
        $this->onQueue('default');
    }

    public function handle(
        WhatsAppWebhookParser $parser,
        ConversationService $conversationService,
        MessageService $messageService
    ): void {
        $phoneNumber = WhatsAppPhoneNumber::with('account')->find($this->phoneNumberId);
        if (!$phoneNumber) {
            Log::warning("WhatsApp phone number {$this->phoneNumberId} not found");
            return;
        }

        $events = $parser->parse($this->payload);

        foreach ($events as $event) {
            if ($event['type'] === 'status') {
                $messageService->updateMessageStatus($event['wamid'], $event['status']);
                continue;
            }

            if ($event['type'] === 'reaction') {
                if ($event['target_wamid']) {
                    $messageService->handleReaction(
                        $event['target_wamid'],
                        $event['emoji'],
                        'customer'
                    );
                }
                continue;
            }

            if ($event['type'] === 'message') {
                $this->processMessage($event, $phoneNumber, $conversationService, $messageService);
            }
        }
    }

    private function processMessage(
        array $event,
        WhatsAppPhoneNumber $phoneNumber,
        ConversationService $conversationService,
        MessageService $messageService
    ): void {
        try {
            $isNewConversation = false;
            $isReopenedConversation = false;

            // Check what state the conversation will be in BEFORE findOrCreate
            $existingConv = \App\Models\ChatConversation::where('whatsapp_phone_number_id', $phoneNumber->id)
                ->where('contact_phone', $event['from'])
                ->latest()
                ->first();

            if (!$existingConv) {
                $isNewConversation = true;
            } elseif ($existingConv->status === 'resolved') {
                $isReopenedConversation = true;
            }

            $conversation = $conversationService->findOrCreate(
                $phoneNumber,
                $event['from'],
                $event['contact_name']
            );

            // Check if greeting should be sent BEFORE storing the message
            $shouldGreet = $this->shouldSendGreeting($conversation, $phoneNumber);

            $message = $messageService->storeIncoming($conversation, $event);

            // Send greeting if needed
            if ($shouldGreet) {
                $this->sendGreeting($phoneNumber, $conversation, $messageService);
            }

            try {
                event(new WhatsAppMessageReceived($conversation->fresh(), $message));
            } catch (\Exception $e) {
                Log::warning('Failed to broadcast message event', ['error' => $e->getMessage()]);
            }

            // Send FCM push to all agents who can see this conversation
            try {
                $title = $conversation->contact_name ?? $conversation->contact_phone;
                $body = $messageService->buildPreview($event) ?? 'New message';
                $fcmData = ['conversation_id' => (string) $conversation->id, 'type' => 'new_message'];
                $pushService = app(PushNotificationService::class);

                if ($conversation->assigned_to) {
                    $pushService->sendToUser($conversation->assignedAgent, $title, $body, $fcmData);
                }

                // Also notify team members linked to this phone number
                $phoneNumber = $conversation->phoneNumber;
                if ($phoneNumber) {
                    $teamUserIds = \App\Models\Team::where('whatsapp_phone_number_id', $phoneNumber->id)
                        ->active()
                        ->with('members')
                        ->get()
                        ->flatMap(fn ($team) => $team->members->pluck('id')->push($team->leader_id))
                        ->unique()
                        ->reject(fn ($id) => $id === $conversation->assigned_to)
                        ->values()
                        ->toArray();

                    if (!empty($teamUserIds)) {
                        $pushService->sendToUsers($teamUserIds, $title, $body, $fcmData);
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Push notification failed', ['error' => $e->getMessage()]);
            }

            // Dispatch AI reply if active (text messages only, and not if greeting was just sent as the first interaction)
            $freshConversation = $conversation->fresh();
            if ($freshConversation->isAiActive() && $event['message_type'] === 'text') {
                SendAiReply::dispatch($conversation->id, $event['content'])
                    ->onQueue('ai');
            }

            // Evaluate workflow automations
            try {
                $workflowEngine = app(WorkflowEngine::class);
                $freshConversation->loadMissing('phoneNumber', 'customer');

                $context = [
                    'is_new_conversation' => $isNewConversation,
                    'is_reopened_conversation' => $isReopenedConversation,
                ];

                // Fire conversation lifecycle triggers
                if ($isNewConversation) {
                    Log::info('Firing conversation_opened workflow', ['conversation_id' => $freshConversation->id]);
                    $workflowEngine->evaluate($freshConversation, 'conversation_opened', $event['content'] ?? null, $context);
                } elseif ($isReopenedConversation) {
                    Log::info('Firing conversation_reopened workflow', ['conversation_id' => $freshConversation->id]);
                    $workflowEngine->evaluate($freshConversation, 'conversation_reopened', $event['content'] ?? null, $context);
                }

                // Always fire message_received
                $workflowEngine->evaluate($freshConversation, 'message_received', $event['content'] ?? null, $context);
            } catch (\Exception $e) {
                Log::error('Workflow evaluation failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to process WhatsApp message', [
                'error' => $e->getMessage(),
                'event' => $event,
            ]);
        }
    }

    private function shouldSendGreeting($conversation, WhatsAppPhoneNumber $phoneNumber): bool
    {
        // No greeting message configured
        if (empty($phoneNumber->greeting_message)) {
            return false;
        }

        // Brand new conversation (no messages yet)
        if (is_null($conversation->last_message_at)) {
            return true;
        }

        // Check cooldown
        $cooldownMinutes = (int) Setting::get('whatsapp', 'greeting_cooldown_minutes', 30);
        $lastMessageAt = $conversation->last_message_at;

        return $lastMessageAt->lt(now()->subMinutes($cooldownMinutes));
    }

    private function sendGreeting(
        WhatsAppPhoneNumber $phoneNumber,
        $conversation,
        MessageService $messageService
    ): void {
        try {
            $whatsAppApi = app(WhatsAppApiService::class);

            // Send via WhatsApp
            $apiResponse = $whatsAppApi->sendTextMessage(
                $conversation->contact_phone,
                $phoneNumber->greeting_message,
                $phoneNumber
            );

            $wamid = $apiResponse['messages'][0]['id'] ?? null;

            // Store as system message
            \App\Models\ChatMessage::create([
                'conversation_id' => $conversation->id,
                'sender_type' => 'system',
                'wamid' => $wamid,
                'message_type' => 'text',
                'content' => $phoneNumber->greeting_message,
                'status' => $wamid ? 'sent' : 'failed',
                'sent_at' => $wamid ? now() : null,
            ]);

            $conversation->update([
                'last_message_at' => now(),
                'last_message_preview' => substr($phoneNumber->greeting_message, 0, 100),
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to send greeting message', ['error' => $e->getMessage()]);
        }
    }
}
