<div>
    <!-- Page Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Order Details</h1>
                    <p class="text-sm text-gray-600 mt-1">View and manage order information</p>
                </div>
                <x-help-tooltip text="View complete order information, update status, process payments, and manage order items. Status changes are tracked and logged." position="right" />
            </div>
            <a href="{{ route('orders') }}" class="px-4 py-2 bg-white border border-gray-300 text-gray-900 rounded-lg hover:bg-gray-50 transition-colors">
                Back to Orders
            </a>
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('success'))
    <div class="mb-6 bg-white border border-gray-300 text-gray-900 px-4 py-3 rounded-xl shadow-sm">
        {{ session('success') }}
    </div>
    @endif

    @if (session()->has('error'))
    <div class="mb-6 bg-white border border-red-300 text-red-900 px-4 py-3 rounded-xl shadow-sm">
        {{ session('error') }}
    </div>
    @endif

    <!-- Order Header Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
            <!-- Order Number -->
            <div>
                <label class="block text-sm font-medium text-gray-900 mb-1">Order Number</label>
                <p class="text-gray-600">{{ $order->order_number }}</p>
            </div>

            <!-- Status -->
            <div>
                <label class="block text-sm font-medium text-gray-900 mb-1">Status</label>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $this->getStatusBadgeClass($order->status) }}">
                    {{ ucfirst($order->status) }}
                </span>
            </div>

            <!-- Created At -->
            <div>
                <label class="block text-sm font-medium text-gray-900 mb-1">Order Date</label>
                <p class="text-gray-600">{{ $order->created_at->format('M d, Y H:i') }}</p>
            </div>

            <!-- Table -->
            <div>
                <label class="block text-sm font-medium text-gray-900 mb-1">Table</label>
                <p class="text-gray-600">{{ $order->table ? $order->table->name : 'N/A' }}</p>
            </div>

            <!-- Waiter -->
            <div>
                <label class="block text-sm font-medium text-gray-900 mb-1">Waiter</label>
                <p class="text-gray-600">{{ $order->waiter ? $order->waiter->name : 'N/A' }}</p>
            </div>
        </div>

        <!-- Guest Information -->
        @if($order->guest)
        <div class="mt-4 pt-4 border-t border-gray-200">
            <label class="block text-sm font-medium text-gray-900 mb-1">Guest</label>
            <p class="text-gray-600">{{ $order->guest->name }} - {{ $order->guest->email }}</p>
        </div>
        @endif

        <!-- Order Special Instructions -->
        @if($order->special_instructions)
        <div class="mt-4 pt-4 border-t border-gray-200">
            <label class="block text-sm font-medium text-gray-900 mb-1">Order Notes</label>
            <p class="text-gray-600">{{ $order->special_instructions }}</p>
        </div>
        @endif
    </div>

    <!-- Order Items Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">Order Items</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-900 uppercase tracking-wider">Menu Item</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-900 uppercase tracking-wider">Quantity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-900 uppercase tracking-wider">Unit Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-900 uppercase tracking-wider">Subtotal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-900 uppercase tracking-wider">Prep Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-900 uppercase tracking-wider">Special Instructions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($order->orderItems as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm text-gray-900">
                            {{ $item->menuItem ? $item->menuItem->name : 'N/A' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            {{ $item->quantity }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            ${{ number_format($item->unit_price, 2) }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            ${{ number_format($item->subtotal, 2) }}
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $this->getPrepStatusBadgeClass($item->prep_status) }}">
                                {{ ucfirst($item->prep_status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            {{ $item->special_instructions ?: '-' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-600">
                            No items in this order
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Order Summary and Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Order Summary Card -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Order Summary</h2>

                <div class="space-y-3">
                    <!-- Subtotal -->
                    <div class="flex justify-between">
                        <span class="text-gray-900 font-medium">Subtotal:</span>
                        <span class="text-gray-600">${{ number_format($order->subtotal, 2) }}</span>
                    </div>

                    <!-- Tax -->
                    <div class="flex justify-between">
                        <span class="text-gray-900 font-medium">Tax (18%):</span>
                        <span class="text-gray-600">${{ number_format($order->tax, 2) }}</span>
                    </div>

                    <!-- Total -->
                    <div class="flex justify-between pt-3 border-t border-gray-200">
                        <span class="text-lg font-bold text-gray-900">Total:</span>
                        <span class="text-lg font-bold text-gray-900">${{ number_format($order->total, 2) }}</span>
                    </div>

                    <!-- Payment Status -->
                    <div class="flex justify-between pt-3 border-t border-gray-200">
                        <span class="text-gray-900 font-medium">Payment Status:</span>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $paymentStatus['class'] }}">
                            {{ $paymentStatus['label'] }}
                        </span>
                    </div>

                    <!-- Total Paid -->
                    @php
                        $totalPaid = $order->payments()->where('status', 'completed')->sum('amount');
                    @endphp
                    @if($totalPaid > 0)
                    <div class="flex justify-between">
                        <span class="text-gray-900 font-medium">Paid:</span>
                        <span class="text-gray-600">${{ number_format($totalPaid, 2) }}</span>
                    </div>

                    <!-- Remaining Balance -->
                    @if($totalPaid < $order->total)
                    <div class="flex justify-between">
                        <span class="text-gray-900 font-medium">Balance:</span>
                        <span class="text-gray-600">${{ number_format($order->total - $totalPaid, 2) }}</span>
                    </div>
                    @endif
                    @endif

                    <!-- Tip -->
                    @if($order->tip)
                    <div class="flex justify-between pt-3 border-t border-gray-200">
                        <span class="text-gray-900 font-medium">Tip:</span>
                        <span class="text-gray-600">${{ number_format($order->tip->amount, 2) }} ({{ ucfirst($order->tip->tip_method) }})</span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Payments List -->
            @if($order->payments->count() > 0)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mt-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Payment History</h3>
                <div class="space-y-3">
                    @foreach($order->payments as $payment)
                    <div class="flex justify-between items-center py-2 border-b border-gray-100 last:border-b-0">
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</p>
                            <p class="text-xs text-gray-600">{{ $payment->created_at->format('M d, Y H:i') }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-gray-900">${{ number_format($payment->amount, 2) }}</p>
                            <p class="text-xs text-gray-600">{{ ucfirst($payment->status) }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <!-- Actions Column -->
        <div class="lg:col-span-2">
            <!-- Status Workflow -->
            @if(count($allowedTransitions) > 0)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Update Status</h2>
                <div class="flex flex-wrap gap-3">
                    @foreach($allowedTransitions as $status)
                    <button wire:click="updateStatus('{{ $status }}')" class="px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 transition-colors">
                        Update to {{ ucfirst($status) }}
                    </button>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Action Buttons -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Actions</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Add Payment Button -->
                    <button wire:click="openPaymentModal" class="px-4 py-3 bg-gray-900 text-white rounded-lg hover:bg-gray-800 transition-colors flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        Add Payment
                    </button>

                    <!-- Add Tip Button -->
                    <button wire:click="openTipModal" class="px-4 py-3 bg-white border border-gray-300 text-gray-900 rounded-lg hover:bg-gray-50 transition-colors flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Add Tip
                    </button>

                    <!-- Print Receipt Button -->
                    <button wire:click="printReceipt" class="px-4 py-3 bg-white border border-gray-300 text-gray-900 rounded-lg hover:bg-gray-50 transition-colors flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                        </svg>
                        Print Receipt
                    </button>

                    <!-- Cancel Order Button (only if pending) -->
                    @if($order->isPending())
                    <button wire:click="openCancelConfirmation" class="px-4 py-3 bg-white border border-red-300 text-red-900 rounded-lg hover:bg-red-50 transition-colors flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Cancel Order
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    @if($showPaymentModal)
    <div class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-xl font-semibold text-gray-900">Add Payment</h3>
            </div>

            <div class="px-6 py-4">
                <form wire:submit.prevent="addPayment">
                    <!-- Payment Method -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-900 mb-2">Payment Method</label>
                        <select wire:model="payment_method" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent text-gray-900">
                            <option value="">Select Method</option>
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="bank_transfer">Bank Transfer</option>
                        </select>
                        @error('payment_method') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <!-- Amount -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-900 mb-2">Amount</label>
                        <input type="number" step="0.01" wire:model="payment_amount" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent text-gray-900" placeholder="0.00">
                        @error('payment_amount') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <!-- Buttons -->
                    <div class="flex gap-3">
                        <button type="submit" class="flex-1 px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 transition-colors">
                            Add Payment
                        </button>
                        <button type="button" wire:click="closePaymentModal" class="flex-1 px-4 py-2 bg-white border border-gray-300 text-gray-900 rounded-lg hover:bg-gray-50 transition-colors">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Tip Modal -->
    @if($showTipModal)
    <div class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-xl font-semibold text-gray-900">Add Tip</h3>
            </div>

            <div class="px-6 py-4">
                <form wire:submit.prevent="addTip">
                    <!-- Tip Amount -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-900 mb-2">Tip Amount</label>
                        <input type="number" step="0.01" wire:model="tip_amount" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent text-gray-900" placeholder="0.00">
                        @error('tip_amount') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <!-- Tip Method -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-900 mb-2">Tip Method</label>
                        <select wire:model="tip_method" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent text-gray-900">
                            <option value="">Select Method</option>
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="mobile_money">Mobile Money</option>
                        </select>
                        @error('tip_method') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <!-- Buttons -->
                    <div class="flex gap-3">
                        <button type="submit" class="flex-1 px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 transition-colors">
                            Add Tip
                        </button>
                        <button type="button" wire:click="closeTipModal" class="flex-1 px-4 py-2 bg-white border border-gray-300 text-gray-900 rounded-lg hover:bg-gray-50 transition-colors">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Cancel Confirmation Modal -->
    @if($showCancelConfirmation)
    <div class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-xl font-semibold text-gray-900">Cancel Order</h3>
            </div>

            <div class="px-6 py-4">
                <p class="text-gray-600 mb-6">Are you sure you want to cancel this order? This action cannot be undone.</p>

                <div class="flex gap-3">
                    <button wire:click="cancelOrder" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        Yes, Cancel Order
                    </button>
                    <button wire:click="closeCancelConfirmation" class="flex-1 px-4 py-2 bg-white border border-gray-300 text-gray-900 rounded-lg hover:bg-gray-50 transition-colors">
                        No, Keep Order
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
