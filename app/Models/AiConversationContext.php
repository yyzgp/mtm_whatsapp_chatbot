<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiConversationContext extends Model
{
    protected $connection = 'shared';
    protected $table = 'sc_ai_conversations_context';

    protected $fillable = [
        'conversation_id', 'messages_json', 'token_count', 'last_updated_at',
    ];

    protected $casts = [
        'token_count' => 'integer',
        'last_updated_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }

    public function getMessages(): array
    {
        return json_decode($this->messages_json ?? '[]', true) ?? [];
    }

    public function setMessages(array $messages): void
    {
        $this->messages_json = json_encode($messages);
        $this->last_updated_at = now();
        $this->save();
    }
}
