<?php

namespace App\Models;

use App\Enums\MessageSenderType;
use App\Enums\MessageStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    protected $connection = 'shared';
    protected $table = 'sc_chat_messages';

    protected $fillable = [
        'conversation_id', 'sender_type', 'sender_id', 'wamid', 'reply_to_wamid',
        'reaction', 'reaction_by',
        'message_type', 'content', 'media_url', 'media_mime_type', 'media_file_size',
        'media_caption', 'template_name', 'template_params', 'status',
        'error_code', 'error_message', 'ai_tokens_used', 'is_ai_generated',
        'sent_at', 'delivered_at', 'read_at',
    ];

    protected $casts = [
        'sender_type' => MessageSenderType::class,
        'status' => MessageStatus::class,
        'template_params' => 'array',
        'is_ai_generated' => 'boolean',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function scopeFromCustomer($query)
    {
        return $query->where('sender_type', MessageSenderType::Customer);
    }

    public function scopeFromAgent($query)
    {
        return $query->where('sender_type', MessageSenderType::Agent);
    }

    public function scopeFromAi($query)
    {
        return $query->where('sender_type', MessageSenderType::Ai);
    }

    public function isMine(): bool
    {
        return $this->sender_type === MessageSenderType::Agent
            && $this->sender_id === auth()->id();
    }

    public function getBubbleClassAttribute(): string
    {
        return match($this->sender_type) {
            MessageSenderType::Customer => 'bg-white border border-gray-200 text-gray-800 rounded-tl-none',
            MessageSenderType::Agent => 'bg-indigo-600 text-white rounded-tr-none',
            MessageSenderType::Ai => 'bg-emerald-50 border border-emerald-200 text-gray-800 rounded-tl-none',
            MessageSenderType::System => 'bg-gray-100 text-gray-500 text-center text-sm rounded-full',
        };
    }
}
