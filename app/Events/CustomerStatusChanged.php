<?php

namespace App\Events;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomerStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Customer $customer,
        public string $fromStatus,
        public string $toStatus,
        public ?User $changedBy = null
    ) {}
}
