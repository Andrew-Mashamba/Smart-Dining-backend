<div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Page Heading --}}
        <div class="mb-6">
            <div class="flex items-center gap-2">
                <h1 class="text-lg font-bold text-gray-900">Inventory Reports</h1>
                <x-help-tooltip text="Monitor stock levels, view transaction history, and track low stock alerts. Filter by date range, menu item, or transaction type to analyze inventory movement and identify trends." position="right" />
            </div>
            <p class="text-gray-600 mt-1">Stock levels, transaction history, and low stock alerts</p>
        </div>

        {{-- Date Range Filters --}}
        <div class="bg-white shadow-sm rounded-lg p-6 mb-6">
            <h2 class="text-gray-900 font-semibold mb-4">Date Range & Filters</h2>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
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

                <div>
                    <label for="filter_menu_item" class="block text-gray-600 text-sm mb-2">Menu Item</label>
                    <select
                        id="filter_menu_item"
                        wire:model.live="filter_menu_item_id"
                        class="w-full px-4 py-2 border border-gray-200 rounded-lg text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                    >
                        <option value="">All Items</option>
                        @foreach($menuItems as $item)
                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter_transaction_type" class="block text-gray-600 text-sm mb-2">Transaction Type</label>
                    <select
                        id="filter_transaction_type"
                        wire:model.live="filter_transaction_type"
                        class="w-full px-4 py-2 border border-gray-200 rounded-lg text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                    >
                        <option value="">All Types</option>
                        <option value="restock">Restock</option>
                        <option value="sale">Sale</option>
                        <option value="adjustment">Adjustment</option>
                        <option value="waste">Waste</option>
                    </select>
                </div>
            </div>

            <div class="flex gap-2">
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

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
            {{-- Total Inventory Value --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-gray-600 text-sm">Total Value</p>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <p class="text-3xl font-bold text-gray-900">${{ number_format($totalValue, 2) }}</p>
            </div>

            {{-- Total Restocks --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-gray-600 text-sm">Restocks</p>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                    </svg>
                </div>
                <p class="text-3xl font-bold text-gray-900">{{ number_format($summary['total_restocks']) }}</p>
            </div>

            {{-- Total Sales --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-gray-600 text-sm">Sales</p>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <p class="text-3xl font-bold text-gray-900">{{ number_format($summary['total_sales']) }}</p>
            </div>

            {{-- Total Adjustments --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-gray-600 text-sm">Adjustments</p>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                    </svg>
                </div>
                <p class="text-3xl font-bold text-gray-900">{{ number_format($summary['total_adjustments']) }}</p>
            </div>

            {{-- Total Waste --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-gray-600 text-sm">Waste</p>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </div>
                <p class="text-3xl font-bold text-gray-900">{{ number_format($summary['total_waste']) }}</p>
            </div>
        </div>

        {{-- Low Stock and Out of Stock Alerts --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            {{-- Low Stock Items --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <h2 class="text-gray-900 font-semibold mb-4">Low Stock Alerts</h2>
                <div class="space-y-2">
                    @forelse($lowStockItems as $item)
                        <div class="flex items-center justify-between py-3 px-4 bg-gray-100 rounded-lg">
                            <div>
                                <span class="text-gray-900 font-bold">{{ $item->name }}</span>
                                <p class="text-sm text-gray-600">Threshold: {{ $item->low_stock_threshold }} {{ $item->unit }}</p>
                            </div>
                            <span class="text-gray-900 font-bold">{{ $item->stock_quantity }} {{ $item->unit }}</span>
                        </div>
                    @empty
                        <p class="text-gray-500 text-center py-4">No low stock items</p>
                    @endforelse
                </div>
            </div>

            {{-- Out of Stock Items --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <h2 class="text-gray-900 font-semibold mb-4">Out of Stock</h2>
                <div class="space-y-2">
                    @forelse($outOfStockItems as $item)
                        <div class="flex items-center justify-between py-3 px-4 bg-gray-100 rounded-lg">
                            <div>
                                <span class="text-gray-900 font-bold">{{ $item->name }}</span>
                                <p class="text-sm text-gray-600">Unit: {{ $item->unit }}</p>
                            </div>
                            <span class="text-red-600 font-bold">OUT OF STOCK</span>
                        </div>
                    @empty
                        <p class="text-gray-500 text-center py-4">All items in stock</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Inventory Turnover Chart --}}
        <div class="bg-white shadow-sm rounded-lg p-6 mb-6">
            <h2 class="text-gray-900 font-semibold mb-4">Inventory Turnover Rate</h2>
            <div class="h-80">
                <canvas id="inventoryTurnoverChart"></canvas>
            </div>
        </div>

        {{-- Current Stock Summary --}}
        <div class="bg-white shadow-sm rounded-lg p-6 mb-6">
            <h2 class="text-gray-900 font-semibold mb-4">Current Stock Summary</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Item Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Stock Quantity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Unit</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Low Stock Threshold</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Unit Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Total Value</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($currentStock as $item)
                            <tr class="hover:bg-gray-50 {{ $item->stock_quantity < $item->low_stock_threshold ? 'bg-gray-100' : '' }}">
                                <td class="px-6 py-4 whitespace-nowrap {{ $item->stock_quantity < $item->low_stock_threshold ? 'text-gray-900 font-bold' : 'text-gray-900' }}">
                                    {{ $item->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-700">{{ $item->stock_quantity }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-700">{{ $item->unit }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-700">{{ $item->low_stock_threshold }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-700">${{ number_format($item->price, 2) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-900 font-semibold">
                                    ${{ number_format($item->stock_quantity * $item->price, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($item->stock_quantity == 0)
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-200 text-gray-900">Out of Stock</span>
                                    @elseif($item->stock_quantity < $item->low_stock_threshold)
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-300 text-gray-900">Low Stock</span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-700">In Stock</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center text-gray-500">No inventory items found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Transaction History --}}
        <div class="bg-white shadow-sm rounded-lg p-6 mb-6">
            <h2 class="text-gray-900 font-semibold mb-4">Transaction History</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Menu Item</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Quantity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Created By</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Notes</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($transactions as $transaction)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-gray-700">
                                    {{ $transaction->created_at->format('Y-m-d H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-900">
                                    {{ $transaction->menuItem->name ?? 'Unknown' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full
                                        {{ $transaction->transaction_type == 'restock' ? 'bg-gray-100 text-gray-900' : '' }}
                                        {{ $transaction->transaction_type == 'sale' ? 'bg-gray-200 text-gray-900' : '' }}
                                        {{ $transaction->transaction_type == 'adjustment' ? 'bg-gray-300 text-gray-900' : '' }}
                                        {{ $transaction->transaction_type == 'waste' ? 'bg-gray-400 text-white' : '' }}">
                                        {{ ucfirst($transaction->transaction_type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-700">
                                    {{ $transaction->quantity }} {{ $transaction->unit }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-700">
                                    {{ $transaction->createdBy->name ?? 'Unknown' }}
                                </td>
                                <td class="px-6 py-4 text-gray-700">
                                    {{ $transaction->notes ?? '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">No transactions found for this period</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($transactions->count() >= 100)
                <p class="text-gray-500 text-sm mt-4 text-center">Showing first 100 transactions. Use filters to narrow results.</p>
            @endif
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('inventoryTurnoverChart');

            const chartData = {
                labels: @json($turnoverData['labels']),
                datasets: [
                    {
                        label: 'Usage (Sales + Waste)',
                        data: @json($turnoverData['usage_data']),
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
                    },
                    {
                        label: 'Restocks',
                        data: @json($turnoverData['restock_data']),
                        borderColor: '#6B7280',
                        backgroundColor: function(context) {
                            const ctx = context.chart.ctx;
                            const gradient = ctx.createLinearGradient(0, 0, 0, 300);
                            gradient.addColorStop(0, 'rgba(107, 114, 128, 0.1)');
                            gradient.addColorStop(1, 'rgba(107, 114, 128, 0.01)');
                            return gradient;
                        },
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#6B7280',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                    }
                ]
            };

            new Chart(ctx, {
                type: 'line',
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                color: '#111827',
                                font: {
                                    family: 'system-ui',
                                    size: 12
                                },
                                usePointStyle: true,
                                padding: 15
                            }
                        },
                        tooltip: {
                            backgroundColor: '#111827',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            padding: 12,
                            displayColors: true,
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.parsed.y;
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
                                    return value;
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
                if (component.name === 'inventory-reports') {
                    location.reload();
                }
            });
        });
    </script>
    @endpush
</div>
