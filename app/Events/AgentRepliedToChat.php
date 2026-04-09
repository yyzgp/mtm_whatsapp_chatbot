<?php

namespace App\Events;

use App\Models\ChatConversation;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AgentRepliedToChat
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ChatConversation $conversation,
        public User $agent
    ) {}
}
