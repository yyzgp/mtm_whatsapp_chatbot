<?php

namespace App\Enums;

enum ConversationStatus: string
{
    case Open = 'open';
    case Pending = 'pending';
    case Resolved = 'resolved';
    case Spam = 'spam';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Open => 'bg-green-100 text-green-700',
            self::Pending => 'bg-yellow-100 text-yellow-700',
            self::Resolved => 'bg-gray-100 text-gray-600',
            self::Spam => 'bg-red-100 text-red-700',
        };
    }
}
