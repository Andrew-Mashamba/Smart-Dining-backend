<div>
    <!-- Page Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Process Payment</h1>
                    <p class="text-sm text-gray-600 mt-1">Complete payment for order {{ $order->order_number }}</p>
                </div>
                <x-help-tooltip text="Choose a payment method (cash, card, or Stripe), enter the amount, and optionally add a tip. Click 'Process Payment' to complete the transaction." position="right" />
            </div>
            <a href="{{ route('orders.show', $order->id) }}" class="px-4 py-2 bg-white border border-gray-300 text-gray-900 rounded-lg hover:bg-gray-50 transition-colors">
                Back to Order
            </a>
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('success'))
    <div class="mb-6 bg-white border border-green-300 text-green-900 px-4 py-3 rounded-xl shadow-sm">
        {{ session('success') }}
    </div>
    @endif

    @if (session()->has('error'))
    <div class="mb-6 bg-white border border-red-300 text-red-900 px-4 py-3 rounded-xl shadow-sm">
        {{ session('error') }}
    </div>
    @endif

    @if (session()->has('info'))
    <div class="mb-6 bg-white border border-blue-300 text-blue-900 px-4 py-3 rounded-xl shadow-sm">
        {{ session('info') }}
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Order Summary (Left Column) -->
        <div class="lg:col-span-1">
            <!-- Order Information Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Order Summary</h2>

                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-900 font-medium">Order Number:</span>
                        <span class="text-sm text-gray-600">{{ $order->order_number }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-sm text-gray-900 font-medium">Date:</span>
                        <span class="text-sm text-gray-600">{{ $order->created_at->format('M d, Y H:i') }}</span>
                    </div>

                    @if($order->table)
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-900 font-medium">Table:</span>
                        <span class="text-sm text-gray-600">{{ $order->table->name }}</span>
                    </div>
                    @endif

                    @if($order->waiter)
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-900 font-medium">Waiter:</span>
                        <span class="text-sm text-gray-600">{{ $order->waiter->name }}</span>
                    </div>
                    @endif

                    @if($order->guest)
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-900 font-medium">Guest:</span>
                        <span class="text-sm text-gray-600">{{ $order->guest->name }}</span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Order Items Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Order Items</h3>
                <div class="space-y-3">
                    @foreach($order->orderItems as $item)
                    <div class="flex justify-between items-start py-2 border-b border-gray-100 last:border-b-0">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900">{{ $item->menuItem ? $item->menuItem->name : 'N/A' }}</p>
                            <p class="text-xs text-gray-600">Qty: {{ $item->quantity }} × ${{ number_format($item->unit_price, 2) }}</p>
                        </div>
                        <p class="text-sm font-semibold text-gray-900">${{ number_format($item->subtotal, 2) }}</p>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Payment Summary Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Payment Summary</h3>

                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-900 font-medium">Subtotal:</span>
                        <span class="text-gray-600">${{ number_format($order->subtotal, 2) }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-900 font-medium">Tax (18%):</span>
                        <span class="text-gray-600">${{ number_format($order->tax, 2) }}</span>
                    </div>

                    <div class="flex justify-between pt-3 border-t border-gray-200">
                        <span class="text-lg font-bold text-gray-900">Total:</span>
                        <span class="text-lg font-bold text-gray-900">${{ number_format($order->total, 2) }}</span>
                    </div>

                    @if($totalPaid > 0)
                    <div class="flex justify-between pt-3 border-t border-gray-200">
                        <span class="text-gray-900 font-medium">Paid:</span>
                        <span class="text-green-700 font-semibold">${{ number_format($totalPaid, 2) }}</span>
                    </div>

                    @if($remainingBalance > 0)
                    <div class="flex justify-between">
                        <span class="text-gray-900 font-medium">Balance Due:</span>
                        <span class="text-red-700 font-semibold">${{ number_format($remainingBalance, 2) }}</span>
                    </div>
                    @else
                    <div class="flex justify-between">
                        <span class="text-gray-900 font-medium">Status:</span>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-900 text-white">
                            Fully Paid
                        </span>
                    </div>
                    @endif
                    @endif

                    @if($order->tip)
                    <div class="flex justify-between pt-3 border-t border-gray-200">
                        <span class="text-gray-900 font-medium">Tip:</span>
                        <span class="text-gray-600">${{ number_format($order->tip->amount, 2) }}</span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Payment History -->
            @if($payments->count() > 0)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mt-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Payment History</h3>
                <div class="space-y-3">
                    @foreach($payments as $payment)
                    <div class="flex justify-between items-center py-2 border-b border-gray-100 last:border-b-0">
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $this->getPaymentMethodName($payment->payment_method) }}</p>
                            <p class="text-xs text-gray-600">{{ $payment->created_at->format('M d, Y H:i') }}</p>
                        </div>
                        <p class="text-sm font-semibold text-gray-900">${{ number_format($payment->amount, 2) }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <!-- Payment Processing (Right Column) -->
        <div class="lg:col-span-2">
            @if($remainingBalance > 0)
            <!-- Payment Form -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Payment Details</h2>

                <form wire:submit.prevent="processPayment">
                    <!-- Payment Method Selection -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-900 mb-3">Select Payment Method</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <label class="relative flex items-center p-4 border-2 rounded-lg cursor-pointer transition-all {{ $payment_method === 'cash' ? 'border-gray-900 bg-gray-50' : 'border-gray-300 hover:border-gray-400' }}">
                                <input type="radio" wire:model.live="payment_method" value="cash" class="sr-only">
                                <div class="flex-1">
                                    <div class="flex items-center">
                                        <svg class="w-6 h-6 mr-3 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                        </svg>
                                        <span class="font-semibold text-gray-900">Cash</span>
                                    </div>
                                </div>
                                @if($payment_method === 'cash')
                                <svg class="w-5 h-5 text-gray-900" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                @endif
                            </label>

                            <label class="relative flex items-center p-4 border-2 rounded-lg cursor-pointer transition-all {{ $payment_method === 'card' ? 'border-gray-900 bg-gray-50' : 'border-gray-300 hover:border-gray-400' }}">
                                <input type="radio" wire:model.live="payment_method" value="card" class="sr-only">
                                <div class="flex-1">
                                    <div class="flex items-center">
                                        <svg class="w-6 h-6 mr-3 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                        </svg>
                                        <span class="font-semibold text-gray-900">Card</span>
                                    </div>
                                </div>
                                @if($payment_method === 'card')
                                <svg class="w-5 h-5 text-gray-900" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                @endif
                            </label>

                            <label class="relative flex items-center p-4 border-2 rounded-lg cursor-pointer transition-all {{ $payment_method === 'mobile' ? 'border-gray-900 bg-gray-50' : 'border-gray-300 hover:border-gray-400' }}">
                                <input type="radio" wire:model.live="payment_method" value="mobile" class="sr-only">
                                <div class="flex-1">
                                    <div class="flex items-center">
                                        <svg class="w-6 h-6 mr-3 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                        </svg>
                                        <span class="font-semibold text-gray-900">Mobile</span>
                                    </div>
                                </div>
                                @if($payment_method === 'mobile')
                                <svg class="w-5 h-5 text-gray-900" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                @endif
                            </label>

                            <label class="relative flex items-center p-4 border-2 rounded-lg cursor-pointer transition-all {{ $payment_method === 'gateway' ? 'border-gray-900 bg-gray-50' : 'border-gray-300 hover:border-gray-400' }}">
                                <input type="radio" wire:model.live="payment_method" value="gateway" class="sr-only">
                                <div class="flex-1">
                                    <div class="flex items-center">
                                        <svg class="w-6 h-6 mr-3 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                                        </svg>
                                        <span class="font-semibold text-gray-900">Gateway</span>
                                    </div>
                                </div>
                                @if($payment_method === 'gateway')
                                <svg class="w-5 h-5 text-gray-900" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                @endif
                            </label>
                        </div>
                        @error('payment_method') <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Amount Input -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-900 mb-2">Payment Amount</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-600 font-semibold">$</span>
                            <input
                                type="number"
                                step="0.01"
                                wire:model="amount"
                                class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent text-gray-900 text-lg font-semibold"
                                placeholder="0.00"
                            >
                        </div>
                        @error('amount') <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                        <p class="text-xs text-gray-600 mt-2">Remaining balance: ${{ number_format($remainingBalance, 2) }}</p>
                    </div>

                    <!-- Process Payment Button -->
                    <button
                        type="submit"
                        class="w-full px-6 py-3 bg-gray-900 text-white rounded-lg hover:bg-gray-800 transition-colors font-semibold text-lg flex items-center justify-center"
                        {{ $payment_method === '' || $amount === '' ? 'disabled' : '' }}
                    >
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Process Payment
                    </button>
                </form>
            </div>
            @endif

            <!-- Tip Section (shown after payment or if already paid) -->
            @if($show_tip_section || $totalPaid >= $order->total)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Add Tip (Optional)</h2>

                @if(!$order->tip)
                <form wire:submit.prevent="processTip">
                    <!-- Suggested Tip Amounts -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-900 mb-3">Suggested Amounts</label>
                        <div class="grid grid-cols-3 gap-3">
                            @foreach($suggestedTips as $percentage => $amount)
                            <button
                                type="button"
                                wire:click="setTipAmount({{ $percentage }})"
                                class="px-4 py-3 border-2 border-gray-300 rounded-lg hover:border-gray-900 transition-colors text-center"
                            >
                                <div class="text-lg font-bold text-gray-900">{{ $percentage }}%</div>
                                <div class="text-sm text-gray-600">${{ $amount }}</div>
                            </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Custom Tip Amount -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-900 mb-2">Custom Tip Amount</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-600 font-semibold">$</span>
                            <input
                                type="number"
                                step="0.01"
                                wire:model="tip_amount"
                                class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent text-gray-900 font-semibold"
                                placeholder="0.00"
                            >
                        </div>
                        @error('tip_amount') <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Tip Method -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-900 mb-2">Tip Method</label>
                        <select wire:model="tip_method" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent text-gray-900">
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="mobile">Mobile Payment</option>
                        </select>
                        @error('tip_method') <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Tip Buttons -->
                    <div class="flex gap-3">
                        <button
                            type="submit"
                            class="flex-1 px-6 py-3 bg-gray-900 text-white rounded-lg hover:bg-gray-800 transition-colors font-semibold"
                        >
                            Add Tip
                        </button>
                        <button
                            type="button"
                            wire:click="skipTip"
                            class="flex-1 px-6 py-3 bg-white border border-gray-300 text-gray-900 rounded-lg hover:bg-gray-50 transition-colors font-semibold"
                        >
                            Skip Tip
                        </button>
                    </div>
                </form>
                @else
                <div class="text-center py-4">
                    <p class="text-gray-600 mb-4">Tip of <span class="font-semibold text-gray-900">${{ number_format($order->tip->amount, 2) }}</span> already added.</p>
                    <p class="text-sm text-gray-500">Thank you for your generosity!</p>
                </div>
                @endif
            </div>
            @endif

            <!-- Action Buttons -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Actions</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Download Receipt -->
                    <button
                        wire:click="downloadReceipt"
                        class="px-6 py-3 bg-white border border-gray-300 text-gray-900 rounded-lg hover:bg-gray-50 transition-colors flex items-center justify-center font-semibold"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Download Receipt
                    </button>

                    <!-- Complete and Return to Orders -->
                    @if($totalPaid >= $order->total)
                    <a
                        href="{{ route('orders') }}"
                        class="px-6 py-3 bg-gray-900 text-white rounded-lg hover:bg-gray-800 transition-colors flex items-center justify-center font-semibold"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Complete & Return
                    </a>
                    @else
                    <a
                        href="{{ route('orders.show', $order->id) }}"
                        class="px-6 py-3 bg-white border border-gray-300 text-gray-900 rounded-lg hover:bg-gray-50 transition-colors flex items-center justify-center font-semibold"
                    >
                        View Order Details
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
