<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold text-gray-900">Orders Management</h1>
                <x-help-tooltip text="View and manage all orders in the system. Search by order number, filter by status or date, and view detailed order information. Click on an order to see full details." position="right" />
            </div>
            <p class="text-sm text-gray-600 mt-1">View, search, and manage all orders</p>
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('message'))
        <div class="bg-white border border-gray-300 text-gray-900 px-4 py-3 rounded-xl shadow-sm">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-white border border-red-300 text-red-900 px-4 py-3 rounded-xl shadow-sm">
            {{ session('error') }}
        </div>
    @endif

    <!-- Search and Filters -->
    <div class="bg-white rounded-xl shadow-sm p-6 space-y-4">
        <!-- Search Bar -->
        <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                <input
                    type="text"
                    id="search"
                    wire:model.live="search"
                    placeholder="Search by order number or guest name..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-gray-900 text-gray-900"
                >
            </div>
            <div class="flex items-end">
                <button
                    wire:click="resetFilters"
                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors"
                >
                    Reset Filters
                </button>
            </div>
        </div>

        <!-- Filters Row -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Status Filter -->
            <div>
                <label for="statusFilter" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select
                    id="statusFilter"
                    wire:model.live="statusFilter"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-gray-900 text-gray-900"
                >
                    <option value="">All Statuses</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Waiter Filter -->
            <div>
                <label for="waiterFilter" class="block text-sm font-medium text-gray-700 mb-2">Waiter</label>
                <select
                    id="waiterFilter"
                    wire:model.live="waiterFilter"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-gray-900 text-gray-900"
                >
                    <option value="">All Waiters</option>
                    @foreach($waiters as $waiter)
                        <option value="{{ $waiter->id }}">{{ $waiter->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Table Filter -->
            <div>
                <label for="tableFilter" class="block text-sm font-medium text-gray-700 mb-2">Table</label>
                <select
                    id="tableFilter"
                    wire:model.live="tableFilter"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-gray-900 text-gray-900"
                >
                    <option value="">All Tables</option>
                    @foreach($tables as $table)
                        <option value="{{ $table->id }}">{{ $table->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Source Filter -->
            <div>
                <label for="sourceFilter" class="block text-sm font-medium text-gray-700 mb-2">Order Source</label>
                <select
                    id="sourceFilter"
                    wire:model.live="sourceFilter"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-gray-900 text-gray-900"
                >
                    <option value="">All Sources</option>
                    @foreach($sources as $source)
                        <option value="{{ $source }}">{{ ucfirst(str_replace('_', ' ', $source)) }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Date Range Filters -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="dateFrom" class="block text-sm font-medium text-gray-700 mb-2">Date From</label>
                <input
                    type="date"
                    id="dateFrom"
                    wire:model.live="dateFrom"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-gray-900 text-gray-900"
                >
            </div>
            <div>
                <label for="dateTo" class="block text-sm font-medium text-gray-700 mb-2">Date To</label>
                <input
                    type="date"
                    id="dateTo"
                    wire:model.live="dateTo"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-gray-900 text-gray-900"
                >
            </div>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <!-- Per Page Selector -->
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <label for="perPage" class="text-sm text-gray-700">Show</label>
                <select
                    id="perPage"
                    wire:model.live="perPage"
                    class="px-3 py-1 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gray-900 focus:border-gray-900 text-gray-900"
                >
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span class="text-sm text-gray-700">entries</span>
            </div>
            <div class="text-sm text-gray-700">
                Total: {{ $orders->total() }} orders
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">
                            Order Number
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">
                            Table
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">
                            Guest
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">
                            Waiter
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">
                            Source
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">
                            Total
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">
                            Created At
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($orders as $order)
                        <tr
                            wire:click="viewOrder({{ $order->id }})"
                            class="hover:bg-gray-50 cursor-pointer transition-colors"
                        >
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $order->order_number }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                {{ $order->table?->name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                {{ $order->guest?->name ?? 'Walk-in' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                {{ $order->waiter?->name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                {{ ucfirst(str_replace('_', ' ', $order->order_source)) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    // Monochrome status badge colors with different gray shades
                                    $statusColors = [
                                        'pending' => 'bg-gray-100 text-gray-700 border-gray-300',
                                        'preparing' => 'bg-gray-200 text-gray-800 border-gray-400',
                                        'ready' => 'bg-gray-300 text-gray-900 border-gray-500',
                                        'delivered' => 'bg-gray-400 text-gray-900 border-gray-600',
                                        'paid' => 'bg-gray-600 text-white border-gray-700',
                                        'cancelled' => 'bg-gray-50 text-gray-500 border-gray-200',
                                    ];
                                    $statusClass = $statusColors[$order->status] ?? 'bg-gray-100 text-gray-700 border-gray-300';
                                @endphp
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full border {{ $statusClass }}">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                ${{ number_format($order->total, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                {{ $order->created_at->format('M d, Y H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm" onclick="event.stopPropagation()">
                                @php
                                    $allowedTransitions = $this->getAllowedTransitions($order->status);
                                @endphp
                                @if(count($allowedTransitions) > 0)
                                    <select
                                        wire:change="updateOrderStatus({{ $order->id }}, $event.target.value)"
                                        class="px-3 py-1 border border-gray-300 rounded-lg text-xs focus:ring-2 focus:ring-gray-900 focus:border-gray-900 text-gray-900"
                                    >
                                        <option value="">Update Status</option>
                                        @foreach($allowedTransitions as $transition)
                                            <option value="{{ $transition }}">{{ ucfirst($transition) }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <span class="text-xs text-gray-500">No actions</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-sm text-gray-500">
                                <div class="flex flex-col items-center gap-2">
                                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                    <p class="font-medium">No orders found</p>
                                    <p class="text-xs">Try adjusting your search or filters</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($orders->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</div>
