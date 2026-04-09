<?php

use Illuminate\Support\Facades\Broadcast;

// General chat channel — any agent with chat access
Broadcast::channel('chat', function ($user) {
    return $user->can('chat.view') || $user->can('chat.view_all');
});

// Specific conversation channel
Broadcast::channel('chat.{conversationId}', function ($user, $conversationId) {
    return $user->can('chat.view') || $user->can('chat.view_all');
});
