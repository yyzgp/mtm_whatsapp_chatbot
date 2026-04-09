<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerStatusHistory extends Model
{
    protected $connection = 'shared';
    protected $table = 'sc_customer_status_histories';

    public $timestamps = false;

    protected $fillable = [
        'customer_id', 'changed_by', 'from_status', 'to_status', 'notes',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
