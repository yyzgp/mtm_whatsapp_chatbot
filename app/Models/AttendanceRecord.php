<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    protected $connection = 'shared';
    protected $table = 'attendance_records';

    protected $fillable = [
        'user_id', 'date', 'time_in', 'time_out', 'total_hours',
        'status', 'late_minutes', 'notes', 'ip_address',
    ];

    protected $casts = [
        'date' => 'date',
        'time_in' => 'datetime',
        'time_out' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
