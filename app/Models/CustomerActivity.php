<?php

namespace App\Models;

use App\Enums\ActivityType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerActivity extends Model
{
    protected $connection = 'shared';
    protected $table = 'sc_customer_activities';

    protected $fillable = [
        'customer_id', 'user_id', 'type', 'subject',
        'description', 'outcome', 'scheduled_at', 'completed_at',
        'is_completed', 'metadata',
    ];

    protected $casts = [
        'type' => ActivityType::class,
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
        'is_completed' => 'boolean',
        'metadata' => 'array',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
