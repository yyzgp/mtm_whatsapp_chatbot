<?php

namespace App\Services\Chat;

use App\Events\AgentRepliedToChat;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Team;
use App\Models\User;
use App\Models\WhatsAppTemplate;
use App\Services\PushNotificationService;
use App\Services\WhatsApp\WhatsAppApiService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MessageService
{
    public function __construct(
        private WhatsAppApiService $whatsAppApiService
    ) {}

    public function storeIncoming(
        ChatConversation $conversation,
        array $parsedMessage
    ): ChatMessage {
        // Download media if present
        $mediaUrl = null;
        if (!empty($parsedMessage['media_id']) && $conversation->phoneNumber) {
            try {
                $mediaUrl = $this->whatsAppApiService->downloadMedia(
                    $parsedMessage['media_id'],
                    $conversation->phoneNumber
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Media download failed', ['error' => $e->getMessage()]);
            }
        }

        $message = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'customer',
            'wamid' => $parsedMessage['wamid'],
            'reply_to_wamid' => $parsedMessage['reply_to_wamid'] ?? null,
            'message_type' => $parsedMessage['message_type'],
            'content' => $parsedMessage['content'],
            'media_url' => $mediaUrl,
            'media_mime_type' => $parsedMessage['media_mime_type'] ?? null,
            'media_caption' => $parsedMessage['media_caption'] ?? null,
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);

        $conversation->update([
            'last_message_at' => now(),
            'last_message_preview' => $this->buildPreview($parsedMessage),
            'unread_count' => $conversation->unread_count + 1,
        ]);

        return $message;
    }

    public function sendAgentMessage(
        ChatConversation $conversation,
        string $content,
        User $agent,
        ?string $replyToWamid = null
    ): ChatMessage {
        $phoneNumber = $conversation->phoneNumber;
        $wamid = null;
        $error = null;

        if ($phoneNumber) {
            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $conversation->contact_phone,
                'type' => 'text',
                'text' => ['body' => $content, 'preview_url' => false],
            ];
            if ($replyToWamid) {
                $payload['context'] = ['message_id' => $replyToWamid];
            }
            $apiResponse = $this->whatsAppApiService->sendRawPayload($phoneNumber, $payload);
            $wamid = $apiResponse['messages'][0]['id'] ?? null;
            $error = isset($apiResponse['error']) ? json_encode($apiResponse['error']) : null;
        }

        $message = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'agent',
            'sender_id' => $agent->id,
            'wamid' => $wamid,
            'reply_to_wamid' => $replyToWamid,
            'message_type' => 'text',
            'content' => $content,
            'status' => $wamid ? 'sent' : 'failed',
            'sent_at' => $wamid ? now() : null,
            'error_message' => $error,
        ]);

        $conversation->update([
            'last_message_at' => now(),
            'last_message_preview' => substr($content, 0, 100),
            'unread_count' => 0,
        ]);

        event(new AgentRepliedToChat($conversation, $agent));
        $this->notifyTeamViaFcm($conversation, $agent->name . ': ' . substr($content, 0, 80), $agent->id);

        return $message;
    }

    public function sendAgentMedia(
        ChatConversation $conversation,
        UploadedFile $file,
        ?string $caption,
        User $agent
    ): ChatMessage {
        $phoneNumber = $conversation->phoneNumber;
        $wamid = null;
        $error = null;

        // Determine WhatsApp media type from mime
        $mime = $file->getMimeType();
        $mediaType = match (true) {
            str_starts_with($mime, 'image/') => 'image',
            str_starts_with($mime, 'video/') => 'video',
            str_starts_with($mime, 'audio/') => 'audio',
            default => 'document',
        };

        // Store file locally
        $localPath = $file->store('whatsapp/' . date('Y/m'), 'public');
        $mediaUrl = 'storage/' . $localPath;

        // Upload to WhatsApp and send
        if ($phoneNumber) {
            try {
                $waMediaId = $this->whatsAppApiService->uploadMedia(
                    $file->getRealPath(),
                    $mime,
                    $phoneNumber
                );

                if ($waMediaId) {
                    $apiResponse = $this->whatsAppApiService->sendMediaMessage(
                        $conversation->contact_phone,
                        $mediaType,
                        $waMediaId,
                        $caption,
                        $phoneNumber
                    );
                    $wamid = $apiResponse['messages'][0]['id'] ?? null;
                    $error = isset($apiResponse['error']) ? json_encode($apiResponse['error']) : null;
                } else {
                    $error = 'Failed to upload media to WhatsApp';
                }
            } catch (\Exception $e) {
                Log::error('WhatsApp media send failed', ['error' => $e->getMessage()]);
                $error = $e->getMessage();
            }
        }

        $previewMap = ['image' => '📷 Image', 'video' => '🎥 Video', 'audio' => '🎵 Audio', 'document' => '📄 Document'];
        $preview = $previewMap[$mediaType] ?? '📎 Attachment';
        if ($caption) $preview .= ': ' . substr($caption, 0, 80);

        $message = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'agent',
            'sender_id' => $agent->id,
            'wamid' => $wamid,
            'message_type' => $mediaType,
            'content' => $caption,
            'media_url' => $mediaUrl,
            'media_mime_type' => $mime,
            'media_file_size' => $file->getSize(),
            'media_caption' => $caption,
            'status' => $wamid ? 'sent' : 'failed',
            'sent_at' => $wamid ? now() : null,
            'error_message' => $error,
        ]);

        $conversation->update([
            'last_message_at' => now(),
            'last_message_preview' => $preview,
            'unread_count' => 0,
        ]);

        event(new AgentRepliedToChat($conversation, $agent));
        $this->notifyTeamViaFcm($conversation, $agent->name . ': 📎 Attachment', $agent->id);

        return $message;
    }

    public function storeAiMessage(
        ChatConversation $conversation,
        string $content,
        int $tokensUsed = 0
    ): ChatMessage {
        $message = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'ai',
            'message_type' => 'text',
            'content' => $content,
            'status' => 'sent',
            'is_ai_generated' => true,
            'ai_tokens_used' => $tokensUsed,
            'sent_at' => now(),
        ]);

        $conversation->update([
            'last_message_at' => now(),
            'last_message_preview' => '🤖 ' . substr($content, 0, 97),
        ]);

        return $message;
    }

    public function sendTemplateMessage(
        ChatConversation $conversation,
        WhatsAppTemplate $template,
        User $agent,
        array $params = []
    ): ChatMessage {
        $phoneNumber = $conversation->phoneNumber;
        $wamid = null;
        $error = null;

        if ($phoneNumber) {
            $apiResponse = $this->whatsAppApiService->sendTemplateMessage(
                $conversation->contact_phone,
                $template->name,
                $template->language ?? 'en',
                $this->buildTemplateSendComponents($template->components ?? [], $params),
                $phoneNumber
            );
            $wamid = $apiResponse['messages'][0]['id'] ?? null;
            $error = isset($apiResponse['error']) ? json_encode($apiResponse['error']) : null;
        }

        $message = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'agent',
            'sender_id' => $agent->id,
            'wamid' => $wamid,
            'message_type' => 'template',
            'content' => "Template: {$template->name}",
            'template_name' => $template->name,
            'status' => $wamid ? 'sent' : 'failed',
            'sent_at' => $wamid ? now() : null,
            'error_message' => $error,
        ]);

        $conversation->update([
            'last_message_at' => now(),
            'last_message_preview' => "Template: {$template->name}",
            'unread_count' => 0,
        ]);

        event(new AgentRepliedToChat($conversation, $agent));
        $this->notifyTeamViaFcm($conversation, $agent->name . ": Template: {$template->name}", $agent->id);

        return $message;
    }

    /**
     * Convert stored template definition components into the send-time parameter
     * format required by the WhatsApp Cloud API. Definition components (BODY, HEADER,
     * FOOTER, BUTTONS) with no variable placeholders produce an empty array, which
     * is what Meta expects for static templates.
     */
    private function buildTemplateSendComponents(array $definitionComponents, array $params = []): array
    {
        $sendComponents = [];

        foreach ($definitionComponents as $component) {
            $type = strtolower($component['type'] ?? '');

            if (!in_array($type, ['header', 'body', 'button'])) {
                continue;
            }

            $text = $component['text'] ?? '';
            preg_match_all('/\{\{(\d+)\}\}/', $text, $matches);
            $varNums = $matches[1];

            if (empty($varNums)) {
                continue;
            }

            $typeParams = $params[$type] ?? [];
            $parameters = [];
            foreach ($varNums as $varNum) {
                $idx = (int) $varNum - 1;
                $value = isset($typeParams[$idx]) && $typeParams[$idx] !== ''
                    ? $typeParams[$idx]
                    : "{{$varNum}}";
                $parameters[] = ['type' => 'text', 'text' => $value];
            }

            $entry = ['type' => $type, 'parameters' => $parameters];

            if ($type === 'button') {
                $entry['index'] = $component['index'] ?? 0;
                $entry['sub_type'] = strtolower($component['sub_type'] ?? 'quick_reply');
            }

            $sendComponents[] = $entry;
        }

        return $sendComponents;
    }

    public function sendAgentReaction(
        ChatMessage $message,
        string $emoji
    ): void {
        $conversation = $message->conversation;
        $phoneNumber = $conversation->phoneNumber;

        // Toggle: same emoji = remove reaction
        $newEmoji = ($message->reaction === $emoji) ? null : $emoji;

        // Send to WhatsApp
        if ($phoneNumber && $message->wamid) {
            $this->whatsAppApiService->sendReaction(
                $conversation->contact_phone,
                $message->wamid,
                $newEmoji ?? '', // empty string = remove reaction in WhatsApp API
                $phoneNumber
            );
        }

        $message->update([
            'reaction'    => $newEmoji,
            'reaction_by' => $newEmoji ? 'agent' : null,
        ]);

        try {
            event(new \App\Events\WhatsAppMessageReceived($conversation, $message->fresh()));
        } catch (\Exception $e) {
            Log::warning('Failed to broadcast reaction update', ['error' => $e->getMessage()]);
        }
    }

    public function handleReaction(string $targetWamid, string $emoji, string $reactorType): void
    {
        $message = ChatMessage::where('wamid', $targetWamid)->with('conversation')->first();
        if (!$message) return;

        $message->update([
            'reaction'    => $emoji !== '' ? $emoji : null,
            'reaction_by' => $emoji !== '' ? $reactorType : null,
        ]);

        // Reuse the existing broadcast event so the Livewire chat UI refreshes
        try {
            event(new \App\Events\WhatsAppMessageReceived($message->conversation, $message->fresh()));
        } catch (\Exception $e) {
            Log::warning('Failed to broadcast reaction update', ['error' => $e->getMessage()]);
        }
    }

    public function updateMessageStatus(string $wamid, string $status): void
    {
        $message = ChatMessage::where('wamid', $wamid)->first();
        if (!$message) return;

        $updates = ['status' => $status];
        if ($status === 'delivered') $updates['delivered_at'] = now();
        if ($status === 'read') $updates['read_at'] = now();

        $message->update($updates);
    }

    public function buildPreview(array $parsedMessage): string
    {
        return match($parsedMessage['message_type']) {
            'text' => substr($parsedMessage['content'] ?? '', 0, 100),
            'image' => '📷 Image' . ($parsedMessage['media_caption'] ? ': ' . $parsedMessage['media_caption'] : ''),
            'audio' => '🎵 Audio message',
            'video' => '🎥 Video',
            'document' => '📄 ' . ($parsedMessage['content'] ?? 'Document'),
            'location' => '📍 Location',
            'sticker' => '😀 Sticker',
            'reaction' => ($parsedMessage['reaction_emoji'] ?? '') !== '' ? 'Reacted ' . $parsedMessage['reaction_emoji'] : 'Removed reaction',
            default => '💬 Message',
        };
    }

    private function notifyTeamViaFcm(ChatConversation $conversation, string $body, ?int $excludeUserId = null): void
    {
        try {
            $title = $conversation->contact_name ?? $conversation->contact_phone;
            $fcmData = ['conversation_id' => (string) $conversation->id, 'type' => 'new_message'];
            $pushService = app(PushNotificationService::class);
            $phoneNumber = $conversation->phoneNumber;

            if (!$phoneNumber) return;

            $teamUserIds = Team::where('whatsapp_phone_number_id', $phoneNumber->id)
                ->active()
                ->with('members')
                ->get()
                ->flatMap(fn ($team) => $team->members->pluck('id')->push($team->leader_id))
                ->unique()
                ->reject(fn ($id) => $id === $excludeUserId)
                ->values()
                ->toArray();

            if (!empty($teamUserIds)) {
                $pushService->sendToUsers($teamUserIds, $title, $body, $fcmData);
            }
        } catch (\Exception $e) {
            Log::warning('FCM team notify failed', ['error' => $e->getMessage()]);
        }
    }
}
