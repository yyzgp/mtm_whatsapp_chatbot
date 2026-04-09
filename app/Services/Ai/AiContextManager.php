<?php

namespace App\Services\Ai;

use App\Models\AiConversationContext;
use App\Models\ChatConversation;

class AiContextManager
{
    private const MAX_MESSAGES = 20;

    public function getContext(ChatConversation $conversation): array
    {
        $context = $conversation->aiContext;
        if (!$context) return [];
        return $context->getMessages();
    }

    public function appendUserMessage(ChatConversation $conversation, string $content): array
    {
        $messages = $this->getContext($conversation);
        $messages[] = ['role' => 'user', 'content' => $content];
        $messages = $this->trimContext($messages);
        $this->saveContext($conversation, $messages);
        return $messages;
    }

    public function appendAssistantMessage(ChatConversation $conversation, string $content): void
    {
        $messages = $this->getContext($conversation);
        $messages[] = ['role' => 'assistant', 'content' => $content];
        $messages = $this->trimContext($messages);
        $this->saveContext($conversation, $messages);
    }

    public function buildMessagesForApi(ChatConversation $conversation, string $systemPrompt): array
    {
        $messages = [['role' => 'system', 'content' => $systemPrompt]];
        $context = $this->getContext($conversation);
        return array_merge($messages, $context);
    }

    private function trimContext(array $messages): array
    {
        if (count($messages) > self::MAX_MESSAGES) {
            return array_slice($messages, -self::MAX_MESSAGES);
        }
        return $messages;
    }

    private function saveContext(ChatConversation $conversation, array $messages): void
    {
        AiConversationContext::updateOrCreate(
            ['conversation_id' => $conversation->id],
            [
                'messages_json' => json_encode($messages),
                'token_count' => $this->estimateTokens($messages),
                'last_updated_at' => now(),
            ]
        );
    }

    private function estimateTokens(array $messages): int
    {
        $text = implode(' ', array_column($messages, 'content'));
        return (int) (strlen($text) / 4);
    }
}
