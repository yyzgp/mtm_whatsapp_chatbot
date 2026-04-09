<?php

namespace App\Listeners;

use App\Enums\ActivityType;
use App\Events\CustomerStatusChanged;
use App\Models\CustomerActivity;
use App\Models\CustomerStatusHistory;

class LogCustomerStatusChange
{
    public function handle(CustomerStatusChanged $event): void
    {
        CustomerStatusHistory::create([
            'customer_id' => $event->customer->id,
            'changed_by' => $event->changedBy?->id,
            'from_status' => $event->fromStatus,
            'to_status' => $event->toStatus,
        ]);

        CustomerActivity::create([
            'customer_id' => $event->customer->id,
            'user_id' => $event->changedBy?->id,
            'type' => ActivityType::StatusChange,
            'subject' => 'Status changed',
            'description' => "Status changed from {$event->fromStatus} to {$event->toStatus}",
            'is_completed' => true,
            'completed_at' => now(),
        ]);
    }
}
