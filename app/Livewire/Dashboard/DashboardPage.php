<?php

namespace App\Livewire\Dashboard;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Customer;
use App\Models\CustomerActivity;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class DashboardPage extends Component
{
    public array $stats = [];
    public array $statusBreakdown = [];
    public array $recentActivities = [];
    public array $conversionFunnelData = [];
    public string $period = '30';

    public function mount(): void
    {
        $this->loadStats();
    }

    public function updatedPeriod(): void
    {
        $this->loadStats();
    }

    private function loadStats(): void
    {
        $days = (int) $this->period;
        $from = now()->subDays($days);

        $query = Customer::query();
        if (!auth()->user()->can('customers.view_all')) {
            $query->where('assigned_to', auth()->id());
        }

        $total = $query->count();
        $newThisPeriod = (clone $query)->where('created_at', '>=', $from)->count();
        $converted = (clone $query)->where('status', 'converted')->count();
        $lost = (clone $query)->where('status', 'lost')->count();

        $conversionRate = $total > 0 ? round(($converted / $total) * 100, 1) : 0;

        $openChats = ChatConversation::where('status', 'open')->count();
        $aiMessages = ChatMessage::where('is_ai_generated', true)->where('created_at', '>=', $from)->count();
        $agentMessages = ChatMessage::where('sender_type', 'agent')->where('created_at', '>=', $from)->count();

        $this->stats = [
            'total_customers' => $total,
            'new_this_period' => $newThisPeriod,
            'converted' => $converted,
            'lost' => $lost,
            'conversion_rate' => $conversionRate,
            'open_chats' => $openChats,
            'ai_messages' => $aiMessages,
            'agent_messages' => $agentMessages,
        ];

        // Status breakdown for funnel
        $breakdown = (clone $query)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $this->statusBreakdown = $breakdown;

        $this->conversionFunnelData = [
            'labels' => ['Inquiry', 'Contacted', 'Qualified', 'Proposal', 'Negotiation', 'Converted'],
            'data' => [
                $breakdown['inquiry'] ?? 0,
                $breakdown['contacted'] ?? 0,
                $breakdown['qualified'] ?? 0,
                $breakdown['proposal'] ?? 0,
                $breakdown['negotiation'] ?? 0,
                $breakdown['converted'] ?? 0,
            ],
        ];

        $this->recentActivities = CustomerActivity::with(['customer', 'user'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn($a) => [
                'id' => $a->id,
                'type' => $a->type->label(),
                'type_color' => $a->type->color(),
                'subject' => $a->subject ?? $a->description,
                'customer_name' => $a->customer?->full_name,
                'customer_id' => $a->customer_id,
                'user_name' => $a->user?->name ?? 'System',
                'time_ago' => $a->created_at->diffForHumans(),
            ])
            ->toArray();
    }

    public function render()
    {
        return view('livewire.dashboard.dashboard-page')
            ->layout('layouts.app');
    }
}
