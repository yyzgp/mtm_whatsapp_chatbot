<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    protected $connection = 'shared';
    protected $table = 'leave_types';

    protected $casts = [
        'is_active' => 'boolean',
        'carry_forward' => 'boolean',
        'requires_attachment' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
