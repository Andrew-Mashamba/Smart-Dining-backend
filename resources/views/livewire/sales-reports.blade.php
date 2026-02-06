<div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Page Heading --}}
        <div class="mb-6">
            <div class="flex items-center gap-2">
                <h1 class="text-lg font-bold text-gray-900">Sales Reports</h1>
                <x-help-tooltip text="View sales analytics including total revenue, number of orders, average order value, and payment method breakdown. Filter by date range and export reports to PDF or CSV format." position="right" />
            </div>
            <p class="text-gray-600 mt-1">Comprehensive sales analytics and performance metrics</p>
        </div>

        {{-- Date Range Filters --}}
        <div class="bg-white shadow-sm rounded-lg p-6 mb-6">
            <h2 class="text-gray-900 font-semibold mb-4">Date Range</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="start_date" class="block text-gray-600 text-sm mb-2">Start Date</label>
                    <input
                        type="date"
                        id="start_date"
                        wire:model.live="start_date"
                        class="w-full px-4 py-2 border border-gray-200 rounded-lg text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                    >
                </div>

                <div>
                    <label for="end_date" class="block text-gray-600 text-sm mb-2">End Date</label>
                    <input
                        type="date"
                        id="end_date"
                        wire:model.live="end_date"
                        class="w-full px-4 py-2 border border-gray-200 rounded-lg text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                    >
                </div>

                <div class="flex items-end gap-2">
                    <button
                        wire:click="exportPdf"
                        class="flex-1 bg-gray-900 text-white px-4 py-2 rounded-lg hover:bg-gray-800 transition-colors font-medium"
                    >
                        Export PDF
                    </button>
                    <button
                        wire:click="exportCsv"
                        class="flex-1 bg-white text-gray-900 border border-gray-200 px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors font-medium"
                    >
                        Export CSV
                    </button>
                </div>
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            {{-- Total Revenue --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-gray-600 text-sm">Total Revenue</p>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <p class="text-3xl font-bold text-gray-900">${{ number_format($summary['total_revenue'], 2) }}</p>
            </div>

            {{-- Total Orders --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-gray-600 text-sm">Total Orders</p>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
                <p class="text-3xl font-bold text-gray-900">{{ number_format($summary['total_orders']) }}</p>
            </div>

            {{-- Average Order Value --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-gray-600 text-sm">Average Order Value</p>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <p class="text-3xl font-bold text-gray-900">${{ number_format($summary['average_order_value'], 2) }}</p>
            </div>

            {{-- Total Tax Collected --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-gray-600 text-sm">Total Tax Collected</p>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"></path>
                    </svg>
                </div>
                <p class="text-3xl font-bold text-gray-900">${{ number_format($summary['total_tax'], 2) }}</p>
            </div>
        </div>

        {{-- Daily Revenue Chart --}}
        <div class="bg-white shadow-sm rounded-lg p-6 mb-6">
            <h2 class="text-gray-900 font-semibold mb-4">Daily Revenue Trend</h2>
            <div class="h-80">
                <canvas id="dailyRevenueChart"></canvas>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            {{-- Revenue by Category --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <h2 class="text-gray-900 font-semibold mb-4">Revenue by Category</h2>
                <div class="space-y-3">
                    @forelse($revenueByCategory as $category)
                        <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                            <span class="text-gray-700">{{ $category->name }}</span>
                            <span class="text-gray-900 font-semibold">${{ number_format($category->total_revenue, 2) }}</span>
                        </div>
                    @empty
                        <p class="text-gray-500 text-center py-4">No category data available for this period</p>
                    @endforelse
                </div>
            </div>

            {{-- Revenue by Payment Method --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <h2 class="text-gray-900 font-semibold mb-4">Revenue by Payment Method</h2>
                <div class="space-y-3">
                    @forelse($revenueByPayment as $payment)
                        <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                            <span class="text-gray-700 capitalize">{{ str_replace('_', ' ', $payment->payment_method) }}</span>
                            <span class="text-gray-900 font-semibold">${{ number_format($payment->total_amount, 2) }}</span>
                        </div>
                    @empty
                        <p class="text-gray-500 text-center py-4">No payment data available for this period</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Top Selling Items --}}
        <div class="bg-white shadow-sm rounded-lg p-6 mb-6">
            <h2 class="text-gray-900 font-semibold mb-4">Top Selling Items</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Item Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Quantity Sold</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Total Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($topItems as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-gray-900">{{ $item->name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-700">${{ number_format($item->price, 2) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-700">{{ number_format($item->total_quantity) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-900 font-semibold">${{ number_format($item->total_revenue, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-gray-500">No sales data available for this period</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('dailyRevenueChart');

            const chartData = {
                labels: @json($dailyRevenue['labels']),
                datasets: [{
                    label: 'Daily Revenue',
                    data: @json($dailyRevenue['data']),
                    borderColor: '#111827',
                    backgroundColor: function(context) {
                        const ctx = context.chart.ctx;
                        const gradient = ctx.createLinearGradient(0, 0, 0, 300);
                        gradient.addColorStop(0, 'rgba(17, 24, 39, 0.1)');
                        gradient.addColorStop(1, 'rgba(17, 24, 39, 0.01)');
                        return gradient;
                    },
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#111827',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                }]
            };

            new Chart(ctx, {
                type: 'line',
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: '#111827',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            padding: 12,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return '$' + context.parsed.y.toFixed(2);
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
                                color: '#6B7280',
                                callback: function(value) {
                                    return '$' + value.toFixed(0);
                                }
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
                if (component.name === 'sales-reports') {
                    location.reload();
                }
            });
        });
    </script>
    @endpush
</div>
