<?php

namespace App\Listeners;

use App\Events\AgentRepliedToChat;

class PauseAiOnAgentReply
{
    public function handle(AgentRepliedToChat $event): void
    {
        $event->conversation->pauseAi(60);
    }
}
