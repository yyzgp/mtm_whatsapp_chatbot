<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppTemplate extends Model
{
    protected $connection = 'shared';
    protected $table = 'sc_whatsapp_templates';

    protected $fillable = [
        'whatsapp_account_id', 'name', 'language', 'category', 'status', 'components',
    ];

    protected $casts = [
        'components' => 'array',
    ];

    public function whatsappAccount(): BelongsTo
    {
        return $this->belongsTo(WhatsAppAccount::class, 'whatsapp_account_id');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}
