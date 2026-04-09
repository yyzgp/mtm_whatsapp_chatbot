<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppPhoneNumber extends Model
{
    protected $connection = 'shared';
    protected $table = 'sc_whatsapp_phone_numbers';

    protected $fillable = [
        'whatsapp_account_id', 'name', 'phone_number_id',
        'is_active', 'ai_enabled', 'ai_prompt', 'greeting_message',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'ai_enabled' => 'boolean',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(WhatsAppAccount::class, 'whatsapp_account_id');
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(ChatConversation::class);
    }

    public function getAccessTokenAttribute(): string
    {
        return $this->account->access_token ?? '';
    }
}
