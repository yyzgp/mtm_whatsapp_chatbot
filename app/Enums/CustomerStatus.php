<?php

namespace App\Enums;

enum CustomerStatus: string
{
    case Inquiry = 'inquiry';
    case Contacted = 'contacted';
    case Qualified = 'qualified';
    case Proposal = 'proposal';
    case Negotiation = 'negotiation';
    case Converted = 'converted';
    case Lost = 'lost';
    case OnHold = 'on_hold';

    public function label(): string
    {
        return match($this) {
            self::Inquiry => 'Inquiry',
            self::Contacted => 'Contacted',
            self::Qualified => 'Qualified',
            self::Proposal => 'Proposal',
            self::Negotiation => 'Negotiation',
            self::Converted => 'Converted',
            self::Lost => 'Lost',
            self::OnHold => 'On Hold',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Inquiry => 'blue',
            self::Contacted => 'indigo',
            self::Qualified => 'violet',
            self::Proposal => 'yellow',
            self::Negotiation => 'orange',
            self::Converted => 'green',
            self::Lost => 'red',
            self::OnHold => 'gray',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Inquiry => 'bg-blue-100 text-blue-800',
            self::Contacted => 'bg-indigo-100 text-indigo-800',
            self::Qualified => 'bg-violet-100 text-violet-800',
            self::Proposal => 'bg-yellow-100 text-yellow-800',
            self::Negotiation => 'bg-orange-100 text-orange-800',
            self::Converted => 'bg-green-100 text-green-800',
            self::Lost => 'bg-red-100 text-red-800',
            self::OnHold => 'bg-gray-100 text-gray-800',
        };
    }

    public static function pipeline(): array
    {
        return [
            self::Inquiry,
            self::Contacted,
            self::Qualified,
            self::Proposal,
            self::Negotiation,
            self::Converted,
        ];
    }

    public function pipelineIndex(): int
    {
        return match($this) {
            self::Inquiry => 0,
            self::Contacted => 1,
            self::Qualified => 2,
            self::Proposal => 3,
            self::Negotiation => 4,
            self::Converted => 5,
            self::Lost => -1,
            self::OnHold => -1,
        };
    }
}
