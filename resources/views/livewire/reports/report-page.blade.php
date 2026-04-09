<div class="px-4 sm:px-6 space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="page-title">Reports</h1>
            <p class="page-subtitle">Analyze performance and trends</p>
        </div>
        <!-- Date Range -->
        <div class="flex items-center gap-2">
            <input wire:model.live="dateFrom" type="date" class="input text-sm">
            <span class="text-gray-400">to</span>
            <input wire:model.live="dateTo" type="date" class="input text-sm">
        </div>
    </div>

    <!-- Report Tabs -->
    <div class="flex gap-2 border-b border-gray-200">
        @foreach(['conversion' => 'Conversion', 'agents' => 'Agent Performance', 'chat' => 'Chat Volume'] as $tab => $label)
        <button wire:click="$set('activeReport', '{{ $tab }}')"
            class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors -mb-px {{ $activeReport === $tab ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            {{ $label }}
        </button>
        @endforeach
    </div>

    @if($activeReport === 'conversion')
        @php $data = $this->conversionReport; @endphp
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="stat-card">
                <p class="text-sm text-gray-500">Total</p>
                <p class="text-3xl font-bold text-gray-900">{{ $data['total'] }}</p>
            </div>
            <div class="stat-card">
                <p class="text-sm text-gray-500">Converted</p>
                <p class="text-3xl font-bold text-green-600">{{ $data['byStatus']['converted'] ?? 0 }}</p>
            </div>
            <div class="stat-card">
                <p class="text-sm text-gray-500">Lost</p>
                <p class="text-3xl font-bold text-red-500">{{ $data['byStatus']['lost'] ?? 0 }}</p>
            </div>
            <div class="stat-card">
                <p class="text-sm text-gray-500">Conv. Rate</p>
                <p class="text-3xl font-bold text-indigo-600">
                    {{ $data['total'] > 0 ? round(($data['byStatus']['converted'] ?? 0) / $data['total'] * 100, 1) : 0 }}%
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="card">
                <div class="card-header"><h3 class="font-semibold text-gray-900">Conversions by Source</h3></div>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead><tr><th>Source</th><th>Total</th><th>Converted</th><th>Rate</th></tr></thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @forelse($data['bySource'] as $row)
                                <tr>
                                    <td>{{ $row['source'] }}</td>
                                    <td>{{ $row['total'] }}</td>
                                    <td class="text-green-600">{{ $row['converted'] }}</td>
                                    <td><span class="badge {{ $row['rate'] >= 30 ? 'bg-green-100 text-green-700' : ($row['rate'] >= 10 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-600') }}">{{ $row['rate'] }}%</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-6 text-center text-gray-400">No data</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="font-semibold text-gray-900">Pipeline Breakdown</h3></div>
                <div class="card-body space-y-2">
                    @foreach(\App\Enums\CustomerStatus::cases() as $status)
                        @php $count = $data['byStatus'][$status->value] ?? 0; @endphp
                        <div class="flex items-center gap-3 text-sm">
                            <span class="badge {{ $status->badgeClass() }} w-24 justify-center">{{ $status->label() }}</span>
                            <div class="flex-1 bg-gray-100 rounded-full h-2">
                                <div class="h-2 rounded-full bg-indigo-500" style="width: {{ $data['total'] > 0 ? round($count/$data['total']*100) : 0 }}%"></div>
                            </div>
                            <span class="text-gray-700 font-medium w-8 text-right">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

    @elseif($activeReport === 'agents')
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Agent</th>
                        <th>Total Customers</th>
                        <th>Converted</th>
                        <th>Lost</th>
                        <th>Conversion Rate</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($this->agentReport as $row)
                    <tr>
                        <td class="font-medium">{{ $row['name'] }}</td>
                        <td>{{ $row['total'] }}</td>
                        <td class="text-green-600">{{ $row['converted'] }}</td>
                        <td class="text-red-500">{{ $row['lost'] }}</td>
                        <td>
                            <div class="flex items-center gap-2">
                                <div class="w-20 bg-gray-100 rounded-full h-2">
                                    <div class="h-2 rounded-full bg-green-500" style="width: {{ $row['rate'] }}%"></div>
                                </div>
                                <span class="text-sm font-medium">{{ $row['rate'] }}%</span>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="py-8 text-center text-gray-400">No agent data found for this period</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    @elseif($activeReport === 'chat')
        @php $data = $this->chatReport; @endphp
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="stat-card">
                <p class="text-sm text-gray-500">Total Messages</p>
                <p class="text-3xl font-bold text-gray-900">{{ number_format($data['totalMessages']) }}</p>
            </div>
            <div class="stat-card">
                <p class="text-sm text-gray-500">AI Messages</p>
                <p class="text-3xl font-bold text-emerald-600">{{ number_format($data['aiMessages']) }}</p>
                <p class="text-xs text-gray-400">{{ $data['totalMessages'] > 0 ? round($data['aiMessages']/$data['totalMessages']*100, 1) : 0 }}%</p>
            </div>
            <div class="stat-card">
                <p class="text-sm text-gray-500">Agent Messages</p>
                <p class="text-3xl font-bold text-indigo-600">{{ number_format($data['agentMessages']) }}</p>
                <p class="text-xs text-gray-400">{{ $data['totalMessages'] > 0 ? round($data['agentMessages']/$data['totalMessages']*100, 1) : 0 }}%</p>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="font-semibold text-gray-900">Daily Message Volume</h3></div>
            <div class="card-body">
                <canvas id="chatChart" height="100"></canvas>
            </div>
        </div>

        @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
        <script>
            const chatCtx = document.getElementById('chatChart');
            if (chatCtx) {
                new Chart(chatCtx, {
                    type: 'bar',
                    data: {
                        labels: @json(array_column($data['daily'], 'date')),
                        datasets: [
                            { label: 'AI', data: @json(array_column($data['daily'], 'ai')), backgroundColor: 'rgba(16,185,129,0.8)', borderRadius: 4 },
                            { label: 'Agent', data: @json(array_column($data['daily'], 'agent')), backgroundColor: 'rgba(99,102,241,0.8)', borderRadius: 4 },
                        ]
                    },
                    options: { responsive: true, scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } } }
                });
            }
        </script>
        @endpush
    @endif
</div>
