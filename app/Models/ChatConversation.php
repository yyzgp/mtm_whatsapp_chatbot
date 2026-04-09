<?php

namespace App\Models;

use App\Enums\ConversationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ChatConversation extends Model
{
    protected $connection = 'shared';
    protected $table = 'sc_chat_conversations';

    protected $fillable = [
        'whatsapp_phone_number_id', 'customer_id', 'assigned_to', 'contact_name',
        'contact_phone', 'waba_conversation_id', 'status', 'ai_active',
        'ai_paused_until', 'last_agent_reply_at', 'last_message_at',
        'last_message_preview', 'unread_count', 'metadata',
    ];

    protected $casts = [
        'status' => ConversationStatus::class,
        'ai_active' => 'boolean',
        'ai_paused_until' => 'datetime',
        'last_agent_reply_at' => 'datetime',
        'last_message_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function isAiActive(): bool
    {
        if (!$this->ai_active) return false;
        if (is_null($this->ai_paused_until)) return true;
        return now()->gt($this->ai_paused_until);
    }

    public function pauseAi(int $minutes = 60): void
    {
        $this->update([
            'ai_paused_until' => now()->addMinutes($minutes),
            'last_agent_reply_at' => now(),
        ]);
    }

    public function resumeAi(): void
    {
        $this->update(['ai_paused_until' => null]);
    }

    public function phoneNumber(): BelongsTo
    {
        return $this->belongsTo(WhatsAppPhoneNumber::class, 'whatsapp_phone_number_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'conversation_id')->oldest();
    }

    public function latestMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'conversation_id')->latest()->limit(50);
    }

    public function aiContext(): HasOne
    {
        return $this->hasOne(AiConversationContext::class, 'conversation_id');
    }

    public function getAiPausedRemainingAttribute(): ?int
    {
        if (!$this->ai_paused_until || now()->gt($this->ai_paused_until)) return null;
        return (int) now()->diffInMinutes($this->ai_paused_until);
    }

    /**
     * Check if the 24hr messaging window is open (customer messaged within last 24hrs).
     */
    public function isWithinMessageWindow(): bool
    {
        $lastCustomerMessage = $this->messages()
            ->where('sender_type', 'customer')
            ->latest()
            ->value('created_at');

        if (!$lastCustomerMessage) return false;

        return $lastCustomerMessage->gt(now()->subHours(24));
    }

    public function scopeOpen($query)
    {
        return $query->where('status', ConversationStatus::Open);
    }

    public function scopeWithUnread($query)
    {
        return $query->where('unread_count', '>', 0);
    }
}
