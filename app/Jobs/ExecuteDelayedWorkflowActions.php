<?php

namespace App\Jobs;

use App\Models\ChatConversation;
use App\Models\ChatWorkflowAction;
use App\Services\Chat\WorkflowEngine;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ExecuteDelayedWorkflowActions implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;
    public int $timeout = 30;

    public function __construct(
        private int $actionId,
        private int $conversationId,
        private ?string $messageContent = null
    ) {
        $this->onQueue('default');
    }

    public function handle(WorkflowEngine $engine): void
    {
        $action = ChatWorkflowAction::find($this->actionId);
        $conversation = ChatConversation::find($this->conversationId);

        if (!$action || !$conversation) {
            Log::warning('Delayed workflow action: action or conversation not found', [
                'action_id' => $this->actionId,
                'conversation_id' => $this->conversationId,
            ]);
            return;
        }

        $engine->executeAction($action, $conversation, $this->messageContent);
    }
}
