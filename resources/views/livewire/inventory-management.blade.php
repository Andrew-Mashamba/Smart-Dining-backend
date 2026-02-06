<div>
    <!-- Page Header -->
    <div class="mb-6">
        <div class="flex items-center gap-2">
            <h1 class="text-2xl font-bold text-gray-900">Inventory Management</h1>
            <x-help-tooltip text="Track stock levels for all menu items. Items with low stock are highlighted in red. Use the action button to adjust quantities when you receive or use inventory." position="right" />
        </div>
        <p class="text-gray-600 mt-1">Monitor stock levels and manage inventory transactions</p>
    </div>

    <!-- Flash Messages -->
    @if(session()->has('success'))
        <div class="mb-6 bg-white border border-gray-300 text-gray-900 px-4 py-3 rounded-xl">
            {{ session('success') }}
        </div>
    @endif

    @if(session()->has('error'))
        <div class="mb-6 bg-white border border-gray-300 text-gray-900 px-4 py-3 rounded-xl">
            {{ session('error') }}
        </div>
    @endif

    <!-- Tabs -->
    <div class="mb-6 border-b border-gray-200">
        <div class="flex gap-6">
            <button
                wire:click="switchTab('inventory')"
                class="pb-3 border-b-2 transition-colors {{ $activeTab === 'inventory' ? 'border-gray-900 text-gray-900 font-bold' : 'border-transparent text-gray-600 hover:text-gray-900' }}"
            >
                Inventory Status
            </button>
            <button
                wire:click="switchTab('history')"
                class="pb-3 border-b-2 transition-colors {{ $activeTab === 'history' ? 'border-gray-900 text-gray-900 font-bold' : 'border-transparent text-gray-600 hover:text-gray-900' }}"
            >
                Transaction History
            </button>
        </div>
    </div>

    <!-- Inventory Tab -->
    @if($activeTab === 'inventory')
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <!-- Table Header -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">Item Name</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">Current Stock</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">Low Stock Threshold</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">Unit</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($menuItems as $item)
                            @php
                                $isLow = $item->stock_quantity < $item->low_stock_threshold;
                            @endphp
                            <tr class="{{ $isLow ? 'bg-gray-100' : '' }}">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm {{ $isLow ? 'font-bold text-gray-900' : 'text-gray-600' }}">
                                        {{ $item->name }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm {{ $isLow ? 'font-bold text-gray-900' : 'text-gray-600' }}">
                                        {{ $item->menuCategory->name ?? 'N/A' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm {{ $isLow ? 'font-bold text-gray-900' : 'text-gray-600' }}">
                                        {{ $item->stock_quantity ?? 0 }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm {{ $isLow ? 'font-bold text-gray-900' : 'text-gray-600' }}">
                                        {{ $item->low_stock_threshold ?? 0 }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm {{ $isLow ? 'font-bold text-gray-900' : 'text-gray-600' }}">
                                        units
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($isLow)
                                        <span class="px-2 py-1 text-xs font-bold text-gray-900 bg-gray-200 rounded">Low Stock</span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-medium text-gray-600 bg-white border border-gray-300 rounded">Normal</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex gap-2">
                                        <button
                                            wire:click="openRestockModal({{ $item->id }})"
                                            class="bg-white text-gray-900 border border-gray-300 px-3 py-1.5 rounded-lg hover:bg-gray-50 transition-colors text-sm font-medium"
                                        >
                                            Restock
                                        </button>
                                        <button
                                            wire:click="openAdjustmentModal({{ $item->id }})"
                                            class="bg-white text-gray-900 border border-gray-300 px-3 py-1.5 rounded-lg hover:bg-gray-50 transition-colors text-sm font-medium"
                                        >
                                            Adjust
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-gray-600">
                                    No menu items found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Transaction History Tab -->
    @if($activeTab === 'history')
        <div class="space-y-6">
            <!-- Filters -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Filters</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-900 mb-2">Menu Item</label>
                        <select
                            wire:model.live="filterMenuItemId"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-400"
                        >
                            <option value="">All Items</option>
                            @foreach($menuItems as $item)
                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-900 mb-2">Transaction Type</label>
                        <select
                            wire:model.live="filterTransactionType"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-400"
                        >
                            <option value="">All Types</option>
                            <option value="restock">Restock</option>
                            <option value="waste">Waste</option>
                            <option value="loss">Loss</option>
                            <option value="correction">Correction</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-900 mb-2">Date From</label>
                        <input
                            type="date"
                            wire:model.live="filterDateFrom"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-400"
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-900 mb-2">Date To</label>
                        <input
                            type="date"
                            wire:model.live="filterDateTo"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-400"
                        >
                    </div>
                </div>
                <div class="mt-4">
                    <button
                        wire:click="resetFilters"
                        class="bg-white text-gray-900 border border-gray-300 px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors text-sm font-medium"
                    >
                        Reset Filters
                    </button>
                </div>
            </div>

            <!-- Transactions Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">Date & Time</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">Item</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">Quantity</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">Unit</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">Created By</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($transactions as $transaction)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-600">
                                            {{ $transaction->created_at->format('M d, Y') }}
                                        </div>
                                        <div class="text-xs text-gray-600">
                                            {{ $transaction->created_at->format('h:i A') }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 font-medium">
                                            {{ $transaction->menuItem->name ?? 'N/A' }}
                                        </div>
                                        <div class="text-xs text-gray-600">
                                            {{ $transaction->menuItem->menuCategory->name ?? '' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-medium rounded
                                            {{ $transaction->transaction_type === 'restock' ? 'bg-white text-gray-900 border border-gray-300' : 'bg-gray-100 text-gray-900' }}">
                                            {{ ucfirst($transaction->transaction_type) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium {{ $transaction->quantity >= 0 ? 'text-gray-900' : 'text-gray-600' }}">
                                            {{ $transaction->quantity >= 0 ? '+' : '' }}{{ $transaction->quantity }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-600">
                                            {{ $transaction->unit }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-600">
                                            {{ $transaction->createdBy->name ?? 'System' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-600 max-w-xs truncate">
                                            {{ $transaction->notes ?? '-' }}
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-8 text-center text-gray-600">
                                        No transactions found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <!-- Restock Modal -->
    @if($showRestockModal)
        <div class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl shadow-lg max-w-md w-full mx-4">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-bold text-gray-900">Restock Item</h3>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-900 mb-2">Quantity *</label>
                        <input
                            type="number"
                            wire:model="restockQuantity"
                            min="1"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-400"
                            placeholder="Enter quantity to add"
                        >
                        @error('restockQuantity')
                            <p class="text-sm text-gray-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-900 mb-2">Unit *</label>
                        <input
                            type="text"
                            wire:model="restockUnit"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-400"
                            placeholder="e.g., units, kg, liters"
                        >
                        @error('restockUnit')
                            <p class="text-sm text-gray-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-900 mb-2">Notes</label>
                        <textarea
                            wire:model="restockNotes"
                            rows="3"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-400"
                            placeholder="Optional notes about this restock"
                        ></textarea>
                        @error('restockNotes')
                            <p class="text-sm text-gray-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                    <button
                        wire:click="closeRestockModal"
                        class="bg-white text-gray-900 border border-gray-300 px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors font-medium"
                    >
                        Cancel
                    </button>
                    <button
                        wire:click="processRestock"
                        class="bg-gray-900 text-white px-4 py-2 rounded-lg hover:bg-gray-800 transition-colors font-medium"
                    >
                        Confirm Restock
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Adjustment Modal -->
    @if($showAdjustmentModal)
        <div class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl shadow-lg max-w-md w-full mx-4">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-bold text-gray-900">Adjust Stock</h3>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-900 mb-2">Adjustment Type *</label>
                        <select
                            wire:model="adjustmentType"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-400"
                        >
                            <option value="waste">Waste</option>
                            <option value="loss">Loss</option>
                            <option value="correction">Manual Correction</option>
                        </select>
                        @error('adjustmentType')
                            <p class="text-sm text-gray-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-900 mb-2">Quantity *</label>
                        <input
                            type="number"
                            wire:model="adjustmentQuantity"
                            min="1"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-400"
                            placeholder="Enter quantity to remove"
                        >
                        @error('adjustmentQuantity')
                            <p class="text-sm text-gray-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-900 mb-2">Unit *</label>
                        <input
                            type="text"
                            wire:model="adjustmentUnit"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-400"
                            placeholder="e.g., units, kg, liters"
                        >
                        @error('adjustmentUnit')
                            <p class="text-sm text-gray-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-900 mb-2">Notes</label>
                        <textarea
                            wire:model="adjustmentNotes"
                            rows="3"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-400"
                            placeholder="Optional notes about this adjustment"
                        ></textarea>
                        @error('adjustmentNotes')
                            <p class="text-sm text-gray-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                    <button
                        wire:click="closeAdjustmentModal"
                        class="bg-white text-gray-900 border border-gray-300 px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors font-medium"
                    >
                        Cancel
                    </button>
                    <button
                        wire:click="processAdjustment"
                        class="bg-gray-900 text-white px-4 py-2 rounded-lg hover:bg-gray-800 transition-colors font-medium"
                    >
                        Confirm Adjustment
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
