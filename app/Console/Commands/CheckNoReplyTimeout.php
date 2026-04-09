<?php

namespace App\Console\Commands;

use App\Models\ChatConversation;
use App\Models\ChatWorkflow;
use App\Services\Chat\WorkflowEngine;
use Illuminate\Console\Command;

class CheckNoReplyTimeout extends Command
{
    protected $signature = 'crm:check-no-reply-timeout';
    protected $description = 'Check for conversations with no reply and trigger timeout workflows';

    public function handle(WorkflowEngine $engine): void
    {
        // Get all active no_reply_timeout workflows
        $workflows = ChatWorkflow::active()
            ->forTrigger(ChatWorkflow::TRIGGER_NO_REPLY_TIMEOUT)
            ->get();

        if ($workflows->isEmpty()) return;

        foreach ($workflows as $workflow) {
            $timeoutMinutes = $workflow->trigger_config['timeout_minutes'] ?? 30;

            // Find open conversations where:
            // 1. Last message is from customer (not agent/ai/system)
            // 2. That message was sent more than X minutes ago
            // 3. No agent/ai reply after that customer message
            $conversations = ChatConversation::whereIn('status', ['open', 'pending'])
                ->where('last_message_at', '<=', now()->subMinutes($timeoutMinutes))
                ->whereHas('messages', function ($q) use ($timeoutMinutes) {
                    $q->where('sender_type', 'customer')
                      ->where('created_at', '<=', now()->subMinutes($timeoutMinutes))
                      ->whereNotExists(function ($sub) {
                          $sub->selectRaw('1')
                              ->from('sc_chat_messages as later')
                              ->whereColumn('later.conversation_id', 'sc_chat_messages.conversation_id')
                              ->whereColumn('later.created_at', '>', 'sc_chat_messages.created_at')
                              ->whereIn('later.sender_type', ['agent', 'ai', 'system']);
                      });
                })
                ->get();

            $count = 0;
            foreach ($conversations as $conversation) {
                // Prevent firing multiple times: check metadata
                $lastTimeout = $conversation->metadata['last_timeout_workflow_at'] ?? null;
                if ($lastTimeout && now()->diffInMinutes($lastTimeout) < $timeoutMinutes) {
                    continue;
                }

                $engine->evaluate($conversation, ChatWorkflow::TRIGGER_NO_REPLY_TIMEOUT);

                // Mark as processed
                $metadata = $conversation->metadata ?? [];
                $metadata['last_timeout_workflow_at'] = now()->toISOString();
                $conversation->update(['metadata' => $metadata]);
                $count++;
            }

            if ($count > 0) {
                $this->info("Triggered '{$workflow->name}' for {$count} conversation(s).");
            }
        }
    }
}
