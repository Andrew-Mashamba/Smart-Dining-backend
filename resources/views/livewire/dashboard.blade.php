<div wire:poll.30s>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Dashboard Heading --}}
        <div class="mb-6">
            <div class="flex items-center">
                <h1 class="text-lg font-bold text-gray-900">Dashboard Overview</h1>
                <x-help-tooltip text="This dashboard updates automatically every 30 seconds to show real-time metrics. View today's performance, revenue trends, and operational status at a glance." position="right" />
            </div>
            <p class="text-sm text-gray-600">Real-time business metrics and activity</p>
        </div>

        {{-- Today's Metrics Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            {{-- Today's Orders Card --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <div class="text-gray-600 text-sm mb-2">Today's Orders</div>
                <div class="text-gray-900 text-2xl font-semibold">{{ $todayOrdersCount }}</div>
                @if($ordersChange['direction'] !== 'neutral')
                    <div class="text-gray-600 text-xs mt-2 flex items-center">
                        @if($ordersChange['direction'] === 'up')
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                        @else
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        @endif
                        {{ $ordersChange['value'] }}% vs yesterday
                    </div>
                @endif
            </div>

            {{-- Revenue Card --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <div class="text-gray-600 text-sm mb-2">Today's Revenue</div>
                <div class="text-gray-900 text-2xl font-semibold">TZS {{ number_format($todayRevenue, 0) }}</div>
                @if($revenueChange['direction'] !== 'neutral')
                    <div class="text-gray-600 text-xs mt-2 flex items-center">
                        @if($revenueChange['direction'] === 'up')
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                        @else
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        @endif
                        {{ $revenueChange['value'] }}% vs yesterday
                    </div>
                @endif
            </div>

            {{-- Active Tables Card --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <div class="text-gray-600 text-sm mb-2">Active Tables</div>
                <div class="text-gray-900 text-2xl font-semibold">{{ $activeTables }}</div>
                @if($activeTablesChange['direction'] !== 'neutral')
                    <div class="text-gray-600 text-xs mt-2 flex items-center">
                        @if($activeTablesChange['direction'] === 'up')
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                        @else
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        @endif
                        {{ $activeTablesChange['value'] }}% vs yesterday
                    </div>
                @endif
            </div>

            {{-- Average Order Value Card --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <div class="text-gray-600 text-sm mb-2">Avg Order Value</div>
                <div class="text-gray-900 text-2xl font-semibold">TZS {{ number_format($avgOrderValue, 0) }}</div>
                @if($avgOrderValueChange['direction'] !== 'neutral')
                    <div class="text-gray-600 text-xs mt-2 flex items-center">
                        @if($avgOrderValueChange['direction'] === 'up')
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                        @else
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        @endif
                        {{ $avgOrderValueChange['value'] }}% vs yesterday
                    </div>
                @endif
            </div>
        </div>

        {{-- Secondary Metrics and Charts Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            {{-- Revenue Chart --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <div class="flex items-center mb-4">
                    <h2 class="text-gray-900 font-semibold">Revenue Trend (Last 7 Days)</h2>
                    <x-help-tooltip text="Track your daily revenue performance over the past week. Hover over data points to see exact amounts for each day." position="right" />
                </div>
                <canvas id="revenueChart" class="w-full" style="height: 250px;"></canvas>
            </div>

            {{-- Active Orders Widget --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <h2 class="text-gray-900 font-semibold mb-4">Active Orders</h2>
                <div class="text-center py-8">
                    <div class="text-gray-900 text-5xl font-bold">{{ $activeOrdersCount }}</div>
                    <div class="text-gray-600 text-sm mt-2">Orders in progress</div>
                    <div class="text-gray-600 text-xs mt-1">(Pending, Preparing, Ready)</div>
                </div>
            </div>
        </div>

        {{-- Widgets Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
            {{-- Recent Orders Widget --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <h2 class="text-gray-900 font-semibold mb-4">Recent Orders</h2>
                <div class="space-y-3">
                    @forelse($recentOrders as $order)
                        <div class="border-b border-gray-200 pb-2">
                            <div class="flex justify-between items-start">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">{{ $order->order_number }}</div>
                                    <div class="text-xs text-gray-600">
                                        Table: {{ $order->table->name ?? 'N/A' }} |
                                        Waiter: {{ $order->waiter->name ?? 'N/A' }}
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-semibold text-gray-900">TZS {{ number_format($order->total, 0) }}</div>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                        @if($order->status === 'paid') bg-green-100 text-green-800
                                        @elseif($order->status === 'pending') bg-yellow-100 text-yellow-800
                                        @elseif($order->status === 'preparing') bg-blue-100 text-blue-800
                                        @elseif($order->status === 'ready') bg-purple-100 text-purple-800
                                        @elseif($order->status === 'delivered') bg-indigo-100 text-indigo-800
                                        @else bg-gray-100 text-gray-800
                                        @endif">
                                        {{ ucfirst($order->status) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-600 text-sm text-center py-4">No recent orders</p>
                    @endforelse
                </div>
            </div>

            {{-- Low Stock Alerts Widget --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <h2 class="text-gray-900 font-semibold mb-4 flex justify-between items-center">
                    <div class="flex items-center">
                        <span>Low Stock Alerts</span>
                        <x-help-tooltip text="Items that have reached or fallen below their low stock threshold. Take action to reorder inventory to prevent stockouts." position="right" />
                    </div>
                    @if($lowStockItems->count() > 0)
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            {{ $lowStockItems->count() }}
                        </span>
                    @endif
                </h2>
                <div class="space-y-3">
                    @forelse($lowStockItems as $item)
                        <div class="border-b border-gray-200 pb-2">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="text-sm font-medium text-gray-900">{{ $item->name }}</div>
                                    <div class="text-xs text-gray-600">
                                        Threshold: {{ $item->low_stock_threshold }}
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium
                                        @if($item->stock_quantity <= 0) bg-red-100 text-red-800
                                        @else bg-orange-100 text-orange-800
                                        @endif">
                                        {{ $item->stock_quantity }} left
                                    </span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-600 text-sm text-center py-4">All items are well stocked</p>
                    @endforelse
                </div>
                @if($lowStockItems->count() > 0)
                    <div class="mt-4 text-center">
                        <a href="{{ route('inventory') }}" class="text-sm text-gray-900 hover:text-gray-700 font-medium">
                            View Inventory →
                        </a>
                    </div>
                @endif
            </div>

            {{-- Top Selling Items Widget --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <h2 class="text-gray-900 font-semibold mb-4">Top Selling Items (Today)</h2>
                <div class="space-y-3">
                    @forelse($topSellingItems as $item)
                        <div class="border-b border-gray-200 pb-2">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="text-sm font-medium text-gray-900">{{ $item['name'] }}</div>
                                    <div class="text-xs text-gray-600">
                                        {{ $item['count'] }} {{ Str::plural('order', $item['count']) }}
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-semibold text-gray-900">TZS {{ number_format($item['revenue'], 0) }}</div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-600 text-sm text-center py-4">No sales today</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Staff on Duty Widget --}}
        @if($staffOnDuty->count() > 0)
        <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="text-gray-900 font-semibold mb-4">Staff on Duty ({{ $staffOnDuty->count() }})</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($staffOnDuty as $staff)
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="text-sm font-medium text-gray-900">{{ $staff->name }}</div>
                        <div class="text-xs text-gray-600 mt-1">{{ ucfirst($staff->role) }}</div>
                        <div class="mt-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Active
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- Chart.js Script --}}
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Revenue Chart
            const ctx = document.getElementById('revenueChart');
            if (ctx) {
                const revenueData = @json($revenueChartData);

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: revenueData.map(d => d.date),
                        datasets: [{
                            label: 'Revenue (TZS)',
                            data: revenueData.map(d => d.revenue),
                            borderColor: 'rgb(31, 41, 55)',
                            backgroundColor: 'rgba(31, 41, 55, 0.1)',
                            tension: 0.3,
                            fill: true,
                            pointBackgroundColor: 'rgb(31, 41, 55)',
                            pointBorderColor: '#fff',
                            pointHoverBackgroundColor: '#fff',
                            pointHoverBorderColor: 'rgb(31, 41, 55)'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return 'TZS ' + context.parsed.y.toLocaleString();
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return 'TZS ' + value.toLocaleString();
                                    }
                                },
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });
            }

            // Re-render chart on Livewire updates
            Livewire.hook('message.processed', (message, component) => {
                if (component.name === 'dashboard') {
                    const ctx = document.getElementById('revenueChart');
                    if (ctx) {
                        const revenueData = @json($revenueChartData);
                        Chart.getChart(ctx)?.destroy();

                        new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: revenueData.map(d => d.date),
                                datasets: [{
                                    label: 'Revenue (TZS)',
                                    data: revenueData.map(d => d.revenue),
                                    borderColor: 'rgb(31, 41, 55)',
                                    backgroundColor: 'rgba(31, 41, 55, 0.1)',
                                    tension: 0.3,
                                    fill: true,
                                    pointBackgroundColor: 'rgb(31, 41, 55)',
                                    pointBorderColor: '#fff',
                                    pointHoverBackgroundColor: '#fff',
                                    pointHoverBorderColor: 'rgb(31, 41, 55)'
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: false
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                return 'TZS ' + context.parsed.y.toLocaleString();
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            callback: function(value) {
                                                return 'TZS ' + value.toLocaleString();
                                            }
                                        },
                                        grid: {
                                            color: 'rgba(0, 0, 0, 0.05)'
                                        }
                                    },
                                    x: {
                                        grid: {
                                            display: false
                                        }
                                    }
                                }
                            }
                        });
                    }
                }
            });
        });
    </script>
    @endpush
</div>
