<?php

namespace App\Livewire\Reports;

use App\Enums\CustomerStatus;
use App\Models\ChatMessage;
use App\Models\Customer;
use App\Models\CustomerSource;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class ReportPage extends Component
{
    public string $activeReport = 'conversion';
    public string $dateFrom = '';
    public string $dateTo = '';

    public function mount(): void
    {
        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    #[Computed]
    public function conversionReport(): array
    {
        $query = Customer::query()
            ->whereBetween('created_at', [$this->dateFrom, $this->dateTo . ' 23:59:59']);

        if (!auth()->user()->can('customers.view_all')) {
            $query->where('assigned_to', auth()->id());
        }

        $total = $query->count();
        $byStatus = (clone $query)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $bySource = Customer::whereBetween('created_at', [$this->dateFrom, $this->dateTo . ' 23:59:59'])
            ->with('source')
            ->select('source_id', DB::raw('count(*) as total'), DB::raw("SUM(CASE WHEN status='converted' THEN 1 ELSE 0 END) as converted"))
            ->groupBy('source_id')
            ->get()
            ->map(fn($r) => [
                'source' => $r->source?->name ?? 'Unknown',
                'total' => $r->total,
                'converted' => $r->converted,
                'rate' => $r->total > 0 ? round($r->converted / $r->total * 100, 1) : 0,
            ])
            ->toArray();

        $lostReasons = (clone $query)
            ->where('status', 'lost')
            ->whereNotNull('lost_reason')
            ->select('lost_reason', DB::raw('count(*) as count'))
            ->groupBy('lost_reason')
            ->orderByDesc('count')
            ->limit(10)
            ->pluck('count', 'lost_reason')
            ->toArray();

        return compact('total', 'byStatus', 'bySource', 'lostReasons');
    }

    #[Computed]
    public function agentReport(): array
    {
        $agents = User::agents()
            ->withCount([
                'assignedCustomers as total_customers' => fn($q) => $q->whereBetween('created_at', [$this->dateFrom, $this->dateTo . ' 23:59:59']),
                'assignedCustomers as converted_customers' => fn($q) => $q->where('status', 'converted')->whereBetween('created_at', [$this->dateFrom, $this->dateTo . ' 23:59:59']),
                'assignedCustomers as lost_customers' => fn($q) => $q->where('status', 'lost')->whereBetween('created_at', [$this->dateFrom, $this->dateTo . ' 23:59:59']),
            ])
            ->get()
            ->map(fn($u) => [
                'name' => $u->name,
                'total' => $u->total_customers,
                'converted' => $u->converted_customers,
                'lost' => $u->lost_customers,
                'rate' => $u->total_customers > 0 ? round($u->converted_customers / $u->total_customers * 100, 1) : 0,
            ])
            ->sortByDesc('total')
            ->values()
            ->toArray();

        return $agents;
    }

    #[Computed]
    public function chatReport(): array
    {
        $totalMessages = ChatMessage::whereBetween('created_at', [$this->dateFrom, $this->dateTo . ' 23:59:59'])->count();
        $aiMessages = ChatMessage::where('is_ai_generated', true)->whereBetween('created_at', [$this->dateFrom, $this->dateTo . ' 23:59:59'])->count();
        $agentMessages = ChatMessage::where('sender_type', 'agent')->whereBetween('created_at', [$this->dateFrom, $this->dateTo . ' 23:59:59'])->count();

        $daily = ChatMessage::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN is_ai_generated=1 THEN 1 ELSE 0 END) as ai'),
                DB::raw("SUM(CASE WHEN sender_type='agent' THEN 1 ELSE 0 END) as agent")
            )
            ->whereBetween('created_at', [$this->dateFrom, $this->dateTo . ' 23:59:59'])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();

        return compact('totalMessages', 'aiMessages', 'agentMessages', 'daily');
    }

    public function render()
    {
        return view('livewire.reports.report-page')
            ->layout('layouts.app');
    }
}
