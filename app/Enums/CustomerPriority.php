<?php

namespace App\Enums;

enum CustomerPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Low => 'bg-gray-100 text-gray-600',
            self::Medium => 'bg-blue-100 text-blue-700',
            self::High => 'bg-orange-100 text-orange-700',
            self::Urgent => 'bg-red-100 text-red-700',
        };
    }
}
