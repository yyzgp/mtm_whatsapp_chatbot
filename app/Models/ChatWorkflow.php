<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatWorkflow extends Model
{
    protected $connection = 'shared';
    protected $table = 'sc_chat_workflows';

    const TRIGGER_MESSAGE_RECEIVED = 'message_received';
    const TRIGGER_CONVERSATION_OPENED = 'conversation_opened';
    const TRIGGER_CONVERSATION_REOPENED = 'conversation_reopened';
    const TRIGGER_NO_REPLY_TIMEOUT = 'no_reply_timeout';
    const TRIGGER_KEYWORD_MATCH = 'keyword_match';
    const TRIGGER_AI_HANDOFF = 'ai_handoff';

    const TRIGGERS = [
        self::TRIGGER_MESSAGE_RECEIVED => 'Message Received',
        self::TRIGGER_CONVERSATION_OPENED => 'New Conversation',
        self::TRIGGER_CONVERSATION_REOPENED => 'Conversation Reopened',
        self::TRIGGER_NO_REPLY_TIMEOUT => 'No Reply Timeout',
        self::TRIGGER_KEYWORD_MATCH => 'Keyword Match',
        self::TRIGGER_AI_HANDOFF => 'AI Handoff',
    ];

    protected $fillable = [
        'name', 'description', 'trigger_type', 'trigger_config',
        'conditions', 'is_active', 'priority',
    ];

    protected $casts = [
        'trigger_config' => 'array',
        'conditions' => 'array',
        'is_active' => 'boolean',
    ];

    public function actions(): HasMany
    {
        return $this->hasMany(ChatWorkflowAction::class, 'workflow_id')->orderBy('sort_order');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForTrigger($query, string $trigger)
    {
        return $query->where('trigger_type', $trigger);
    }
}
