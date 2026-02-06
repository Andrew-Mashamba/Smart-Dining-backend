<div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Page Heading --}}
        <div class="mb-6">
            <div class="flex items-center gap-2">
                <h1 class="text-lg font-bold text-gray-900">Staff Performance Reports</h1>
                <x-help-tooltip text="Track individual staff performance metrics including orders served, tips earned, average service time, and customer satisfaction ratings. Filter by date range or specific staff member." position="right" />
            </div>
            <p class="text-gray-600 mt-1">Track waiter performance, orders served, tips earned, and efficiency metrics</p>
        </div>

        {{-- Filters Section --}}
        <div class="bg-white shadow-sm rounded-lg p-6 mb-6">
            <h2 class="text-gray-900 font-semibold mb-4">Filters</h2>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                {{-- Start Date --}}
                <div>
                    <label for="start_date" class="block text-gray-600 text-sm mb-2">Start Date</label>
                    <input
                        type="date"
                        id="start_date"
                        wire:model.live="start_date"
                        class="w-full px-4 py-2 border border-gray-200 rounded-lg text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                    >
                </div>

                {{-- End Date --}}
                <div>
                    <label for="end_date" class="block text-gray-600 text-sm mb-2">End Date</label>
                    <input
                        type="date"
                        id="end_date"
                        wire:model.live="end_date"
                        class="w-full px-4 py-2 border border-gray-200 rounded-lg text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                    >
                </div>

                {{-- Staff Selector --}}
                <div>
                    <label for="staff_selector" class="block text-gray-600 text-sm mb-2">Select Waiter</label>
                    <select
                        id="staff_selector"
                        wire:model.live="selected_staff_id"
                        class="w-full px-4 py-2 border border-gray-200 rounded-lg text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                    >
                        <option value="all">All Waiters</option>
                        @foreach($waiters as $waiter)
                            <option value="{{ $waiter->id }}">{{ $waiter->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Export Buttons --}}
                <div class="flex items-end gap-2">
                    <button
                        wire:click="exportPdf"
                        class="flex-1 bg-gray-900 text-white px-4 py-2 rounded-lg hover:bg-gray-800 transition-colors font-medium text-sm"
                        title="Export to PDF"
                    >
                        PDF
                    </button>
                    <button
                        wire:click="exportCsv"
                        class="flex-1 bg-white text-gray-900 border border-gray-200 px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors font-medium text-sm"
                        title="Export to CSV"
                    >
                        CSV
                    </button>
                </div>
            </div>
        </div>

        {{-- Performance Metrics Table --}}
        <div class="bg-white shadow-sm rounded-lg p-6 mb-6">
            <h2 class="text-gray-900 font-semibold mb-4">Performance Metrics</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                Waiter Name
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                wire:click="sortBy('orders')"
                            >
                                <div class="flex items-center gap-1">
                                    Orders Served
                                    @if($sort_by === 'orders')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            @if($sort_direction === 'asc')
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                            @else
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            @endif
                                        </svg>
                                    @endif
                                </div>
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                wire:click="sortBy('revenue')"
                            >
                                <div class="flex items-center gap-1">
                                    Revenue Generated
                                    @if($sort_by === 'revenue')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            @if($sort_direction === 'asc')
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                            @else
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            @endif
                                        </svg>
                                    @endif
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                Avg Order Value
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                wire:click="sortBy('tips')"
                            >
                                <div class="flex items-center gap-1">
                                    Tips Earned
                                    @if($sort_by === 'tips')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            @if($sort_direction === 'asc')
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                            @else
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            @endif
                                        </svg>
                                    @endif
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                Avg Tip %
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                Tip Breakdown
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($staffPerformance as $staff)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-gray-900 font-medium">{{ $staff->name }}</div>
                                    <div class="text-gray-500 text-sm">{{ $staff->email }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-900">
                                    {{ number_format($staff->total_orders) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-900 font-semibold">
                                    ${{ number_format($staff->total_revenue, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-700">
                                    ${{ number_format($staff->average_order_value, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-900 font-semibold">
                                    ${{ number_format($staff->total_tips, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-700">
                                    {{ number_format($staff->average_tip_percentage, 1) }}%
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm space-y-1">
                                        @if(isset($staff->tip_breakdown['cash']) && $staff->tip_breakdown['cash'] > 0)
                                            <div class="text-gray-700">
                                                <span class="text-gray-500">Cash:</span> ${{ number_format($staff->tip_breakdown['cash'], 2) }}
                                            </div>
                                        @endif
                                        @if(isset($staff->tip_breakdown['card']) && $staff->tip_breakdown['card'] > 0)
                                            <div class="text-gray-700">
                                                <span class="text-gray-500">Card:</span> ${{ number_format($staff->tip_breakdown['card'], 2) }}
                                            </div>
                                        @endif
                                        @if(isset($staff->tip_breakdown['mobile']) && $staff->tip_breakdown['mobile'] > 0)
                                            <div class="text-gray-700">
                                                <span class="text-gray-500">Mobile:</span> ${{ number_format($staff->tip_breakdown['mobile'], 2) }}
                                            </div>
                                        @endif
                                        @if(empty($staff->tip_breakdown))
                                            <span class="text-gray-400">No tips</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                    No staff performance data available for this period
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Performance Comparison Chart --}}
        <div class="bg-white shadow-sm rounded-lg p-6 mb-6">
            <h2 class="text-gray-900 font-semibold mb-4">Waiter Performance Comparison</h2>
            <div class="h-80">
                <canvas id="staffPerformanceChart"></canvas>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('staffPerformanceChart');

            const chartData = @json($chartData);

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: chartData.labels,
                    datasets: [
                        {
                            label: 'Orders Served',
                            data: chartData.orders,
                            backgroundColor: '#111827',
                            borderColor: '#111827',
                            borderWidth: 1
                        },
                        {
                            label: 'Revenue ($)',
                            data: chartData.revenue,
                            backgroundColor: '#4B5563',
                            borderColor: '#4B5563',
                            borderWidth: 1
                        },
                        {
                            label: 'Tips ($)',
                            data: chartData.tips,
                            backgroundColor: '#9CA3AF',
                            borderColor: '#9CA3AF',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                color: '#374151',
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: '#111827',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            padding: 12,
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.dataset.label === 'Orders Served') {
                                        label += context.parsed.y;
                                    } else {
                                        label += '$' + context.parsed.y.toFixed(2);
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#E5E7EB',
                                drawBorder: false
                            },
                            ticks: {
                                color: '#6B7280'
                            }
                        },
                        x: {
                            grid: {
                                display: false,
                                drawBorder: false
                            },
                            ticks: {
                                color: '#6B7280'
                            }
                        }
                    }
                }
            });

            // Reinitialize chart on Livewire updates
            Livewire.hook('message.processed', (message, component) => {
                if (component.name === 'staff-reports') {
                    location.reload();
                }
            });
        });
    </script>
    @endpush
</div>
