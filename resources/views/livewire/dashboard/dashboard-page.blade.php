<div class="px-4 sm:px-6 space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
            <p class="text-sm text-gray-500 mt-1">Welcome back, {{ auth()->user()->name }}</p>
        </div>
        <select wire:model.live="period" class="input w-auto">
            <option value="7">Last 7 days</option>
            <option value="30" selected>Last 30 days</option>
            <option value="90">Last 90 days</option>
        </select>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="stat-card">
            <p class="text-sm text-gray-500">Total Customers</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($stats['total_customers'] ?? 0) }}</p>
            <p class="text-xs text-green-600 mt-1">+{{ $stats['new_this_period'] ?? 0 }} this period</p>
        </div>
        <div class="stat-card">
            <p class="text-sm text-gray-500">Converted</p>
            <p class="text-3xl font-bold text-green-600 mt-1">{{ number_format($stats['converted'] ?? 0) }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ $stats['conversion_rate'] ?? 0 }}% rate</p>
        </div>
        <div class="stat-card">
            <p class="text-sm text-gray-500">Open Chats</p>
            <p class="text-3xl font-bold text-indigo-600 mt-1">{{ number_format($stats['open_chats'] ?? 0) }}</p>
            <p class="text-xs text-gray-400 mt-1">Active conversations</p>
        </div>
        <div class="stat-card">
            <p class="text-sm text-gray-500">AI Messages</p>
            <p class="text-3xl font-bold text-emerald-600 mt-1">{{ number_format($stats['ai_messages'] ?? 0) }}</p>
            <p class="text-xs text-gray-400 mt-1">vs {{ $stats['agent_messages'] ?? 0 }} agent</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Conversion Funnel -->
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold text-gray-900">Conversion Funnel</h3>
            </div>
            <div class="card-body">
                <canvas id="funnelChart" height="250"></canvas>
            </div>
        </div>

        <!-- Status Breakdown -->
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold text-gray-900">Pipeline Overview</h3>
            </div>
            <div class="card-body space-y-3">
                @foreach([
                    ['inquiry', 'Inquiry', 'bg-blue-500'],
                    ['contacted', 'Contacted', 'bg-indigo-500'],
                    ['qualified', 'Qualified', 'bg-violet-500'],
                    ['proposal', 'Proposal', 'bg-yellow-500'],
                    ['negotiation', 'Negotiation', 'bg-orange-500'],
                    ['converted', 'Converted', 'bg-green-500'],
                    ['lost', 'Lost', 'bg-red-400'],
                    ['on_hold', 'On Hold', 'bg-gray-400'],
                ] as [$key, $label, $color])
                    @php $count = $statusBreakdown[$key] ?? 0; $total = array_sum($statusBreakdown) ?: 1; @endphp
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-gray-600 w-24">{{ $label }}</span>
                        <div class="flex-1 bg-gray-100 rounded-full h-2">
                            <div class="{{ $color }} h-2 rounded-full transition-all" style="width: {{ round($count/$total*100) }}%"></div>
                        </div>
                        <span class="text-sm font-medium text-gray-700 w-8 text-right">{{ $count }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="card">
        <div class="card-header flex items-center justify-between">
            <h3 class="font-semibold text-gray-900">Recent Activities</h3>
            <a href="{{ route('customers.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">View all</a>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($recentActivities as $activity)
                <div class="px-6 py-3 flex items-start gap-4">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 {{ $activity['type_color'] }}">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-900">
                            <span class="font-medium">{{ $activity['user_name'] }}</span>
                            logged a <span class="font-medium">{{ $activity['type'] }}</span>
                            @if($activity['customer_name'])
                                for <a href="{{ route('customers.show', $activity['customer_id']) }}" class="text-indigo-600 hover:underline">{{ $activity['customer_name'] }}</a>
                            @endif
                        </p>
                        @if($activity['subject'])
                            <p class="text-xs text-gray-500 truncate">{{ $activity['subject'] }}</p>
                        @endif
                    </div>
                    <span class="text-xs text-gray-400 flex-shrink-0">{{ $activity['time_ago'] }}</span>
                </div>
            @empty
                <div class="px-6 py-8 text-center text-sm text-gray-400">No recent activities</div>
            @endforelse
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    const ctx = document.getElementById('funnelChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json($conversionFunnelData['labels'] ?? []),
                datasets: [{
                    label: 'Customers',
                    data: @json($conversionFunnelData['data'] ?? []),
                    backgroundColor: [
                        'rgba(59,130,246,0.8)',
                        'rgba(99,102,241,0.8)',
                        'rgba(139,92,246,0.8)',
                        'rgba(245,158,11,0.8)',
                        'rgba(249,115,22,0.8)',
                        'rgba(34,197,94,0.8)',
                    ],
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });
    }
</script>
@endpush
