<?php

namespace App\Jobs;

use App\Events\WhatsAppMessageReceived;
use App\Models\ChatConversation;
use App\Services\Ai\AiContextManager;
use App\Services\Ai\OpenAiService;
use App\Services\Chat\MessageService;
use App\Services\WhatsApp\WhatsAppApiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendAiReply implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;
    public int $timeout = 60;

    public function __construct(
        private int $conversationId,
        private string $userMessage
    ) {
        $this->onQueue('ai');
    }

    public function handle(
        AiContextManager $contextManager,
        OpenAiService $openAiService,
        WhatsAppApiService $whatsAppApiService,
        MessageService $messageService
    ): void {
        $conversation = ChatConversation::with(['phoneNumber.account', 'customer'])->find($this->conversationId);

        if (!$conversation) {
            Log::warning("Conversation {$this->conversationId} not found for AI reply");
            return;
        }

        // Double-check AI is still active (may have been paused since dispatch)
        if (!$conversation->isAiActive()) {
            Log::info("AI paused for conversation {$this->conversationId}, skipping AI reply");
            return;
        }

        $phoneNumber = $conversation->phoneNumber;
        if (!$phoneNumber || !$phoneNumber->ai_enabled) return;

        // Build system prompt
        $customerContext = [];
        if ($conversation->customer) {
            $customer = $conversation->customer;
            $customerContext = [
                'Name' => $customer->full_name,
                'Company' => $customer->company_name,
                'Status' => $customer->status->label(),
                'Contact' => $customer->phone,
            ];
        }

        $basePrompt = $phoneNumber->ai_prompt ??
            'You are a helpful sales assistant. Answer customer inquiries professionally and helpfully.';

        $systemPrompt = $openAiService->buildSystemPrompt($basePrompt, $customerContext);

        // Append user message to context and get all messages
        $contextManager->appendUserMessage($conversation, $this->userMessage);
        $messages = $contextManager->buildMessagesForApi($conversation, $systemPrompt);

        // Generate AI reply
        $result = $openAiService->generateReply($messages);

        if (!$result['success'] || empty($result['content'])) {
            Log::error("AI reply generation failed for conversation {$this->conversationId}");
            return;
        }

        // Send via WhatsApp
        $apiResponse = $whatsAppApiService->sendTextMessage(
            $conversation->contact_phone,
            $result['content'],
            $phoneNumber
        );

        // Store AI message
        $message = $messageService->storeAiMessage(
            $conversation,
            $result['content'],
            $result['tokens_used']
        );

        // Append AI response to context
        $contextManager->appendAssistantMessage($conversation, $result['content']);

        // Broadcast to UI
        event(new WhatsAppMessageReceived($conversation->fresh(), $message));
    }
}
