<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveBalance extends Model
{
    protected $connection = 'shared';
    protected $table = 'leave_balances';

    protected $fillable = [
        'user_id', 'leave_type_id', 'year', 'entitled', 'used', 'pending', 'carried_forward', 'adjustment',
    ];

    protected $appends = ['remaining'];

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function getRemainingAttribute()
    {
        return $this->entitled + $this->carried_forward + $this->adjustment - $this->used - $this->pending;
    }
}
