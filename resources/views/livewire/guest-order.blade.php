<div class="min-h-screen bg-white">
    {{-- Header --}}
    <div class="sticky top-0 z-10 bg-gray-900 text-white shadow-lg">
        <div class="px-4 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold">{{ config('app.name', 'Restaurant') }}</h1>
                    <p class="text-sm text-gray-300">Table: {{ $guestSession->table->name }}</p>
                </div>
                @if(count($cart) > 0)
                    <button
                        wire:click="toggleCart"
                        class="relative bg-white text-gray-900 rounded-full p-3 hover:bg-gray-100 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                            {{ count($cart) }}
                        </span>
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Order Confirmation Message --}}
    @if($orderPlaced)
        <div class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-lg p-6 max-w-md w-full text-center">
                <div class="mb-4">
                    <svg class="w-16 h-16 text-green-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Order Placed!</h2>
                <p class="text-gray-600 mb-4">Your waiter will serve you shortly.</p>
                <p class="text-sm text-gray-500 mb-6">Order #{{ $orderNumber }}</p>
                <button
                    wire:click="orderAnother"
                    class="w-full bg-gray-900 text-white py-3 px-6 rounded-lg font-semibold hover:bg-gray-800 transition">
                    Order More Items
                </button>
            </div>
        </div>
    @endif

    {{-- Error Message --}}
    @if(session()->has('error'))
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-4">
            <p class="text-red-700">{{ session('error') }}</p>
        </div>
    @endif

    {{-- Category Filter --}}
    <div class="sticky top-[72px] z-10 bg-white border-b border-gray-200 overflow-x-auto">
        <div class="flex px-4 py-3 space-x-2">
            <button
                wire:click="$set('selectedCategoryId', '')"
                class="flex-shrink-0 px-4 py-2 rounded-full text-sm font-medium transition
                    {{ $selectedCategoryId === '' ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                All
            </button>
            @foreach($categories as $category)
                <button
                    wire:click="$set('selectedCategoryId', {{ $category->id }})"
                    class="flex-shrink-0 px-4 py-2 rounded-full text-sm font-medium transition whitespace-nowrap
                        {{ $selectedCategoryId == $category->id ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    {{ $category->name }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Menu Items Grid --}}
    <div class="px-4 py-6">
        @if($menuItems->isEmpty())
            <div class="text-center py-12">
                <p class="text-gray-500">No items available in this category.</p>
            </div>
        @else
            <div class="grid grid-cols-1 gap-4">
                @foreach($menuItems as $item)
                    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-md transition">
                        <div class="p-4">
                            <div class="flex justify-between items-start mb-2">
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-900 text-lg">{{ $item->name }}</h3>
                                    @if($item->description)
                                        <p class="text-sm text-gray-600 mt-1">{{ $item->description }}</p>
                                    @endif
                                    <p class="text-gray-500 text-xs mt-1">{{ $item->menuCategory->name }}</p>
                                </div>
                            </div>
                            <div class="flex items-center justify-between mt-3">
                                <span class="text-lg font-bold text-gray-900">₪{{ number_format($item->price, 2) }}</span>
                                <button
                                    wire:click="addItem({{ $item->id }})"
                                    class="bg-gray-900 text-white px-6 py-2 rounded-lg font-medium hover:bg-gray-800 transition active:scale-95 min-h-[44px]">
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Cart Sidebar --}}
    <div class="fixed inset-0 z-40 pointer-events-none">
        <div class="absolute inset-0 bg-black transition-opacity {{ $showCart ? 'opacity-50 pointer-events-auto' : 'opacity-0' }}"
             wire:click="toggleCart"></div>

        <div class="absolute right-0 top-0 h-full w-full max-w-md bg-white shadow-xl transform transition-transform {{ $showCart ? 'translate-x-0' : 'translate-x-full' }} pointer-events-auto">
            <div class="flex flex-col h-full">
                {{-- Cart Header --}}
                <div class="bg-gray-900 text-white px-4 py-4 flex items-center justify-between">
                    <h2 class="text-lg font-bold">Your Order</h2>
                    <button wire:click="toggleCart" class="text-white hover:text-gray-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Cart Items --}}
                <div class="flex-1 overflow-y-auto p-4">
                    @if(count($cart) === 0)
                        <div class="text-center py-12">
                            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <p class="text-gray-500">Your cart is empty</p>
                        </div>
                    @else
                        <div class="space-y-4">
                            @foreach($cart as $index => $item)
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <div class="flex justify-between items-start mb-3">
                                        <div class="flex-1">
                                            <h4 class="font-semibold text-gray-900">{{ $item['name'] }}</h4>
                                            <p class="text-sm text-gray-600">₪{{ number_format($item['unit_price'], 2) }} each</p>
                                        </div>
                                        <button
                                            wire:click="removeItem({{ $index }})"
                                            class="text-red-500 hover:text-red-700 ml-2">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>

                                    {{-- Quantity Selector --}}
                                    <div class="flex items-center space-x-2 mb-3">
                                        <button
                                            wire:click="updateQuantity({{ $index }}, {{ $item['quantity'] - 1 }})"
                                            class="bg-white border border-gray-300 rounded-lg p-2 hover:bg-gray-100 min-h-[44px] min-w-[44px] flex items-center justify-center">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                                            </svg>
                                        </button>
                                        <input
                                            type="number"
                                            wire:change="updateQuantity({{ $index }}, $event.target.value)"
                                            value="{{ $item['quantity'] }}"
                                            class="w-16 text-center border border-gray-300 rounded-lg py-2 min-h-[44px]"
                                            min="1">
                                        <button
                                            wire:click="updateQuantity({{ $index }}, {{ $item['quantity'] + 1 }})"
                                            class="bg-white border border-gray-300 rounded-lg p-2 hover:bg-gray-100 min-h-[44px] min-w-[44px] flex items-center justify-center">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                        </button>
                                        <span class="ml-auto font-semibold text-gray-900">
                                            ₪{{ number_format($item['unit_price'] * $item['quantity'], 2) }}
                                        </span>
                                    </div>

                                    {{-- Special Instructions --}}
                                    <textarea
                                        wire:change="updateInstructions({{ $index }}, $event.target.value)"
                                        placeholder="Special instructions (optional)"
                                        class="w-full border border-gray-300 rounded-lg p-2 text-sm resize-none min-h-[60px]"
                                        rows="2">{{ $item['special_instructions'] }}</textarea>
                                </div>
                            @endforeach
                        </div>

                        {{-- Guest Info Form --}}
                        <div class="mt-6 bg-gray-50 rounded-lg p-4">
                            <h3 class="font-semibold text-gray-900 mb-3">Contact Information</h3>

                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Phone Number <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="tel"
                                        wire:model="phone_number"
                                        class="w-full border border-gray-300 rounded-lg p-3 min-h-[44px]"
                                        placeholder="Enter your phone number">
                                    @error('phone_number')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Name (Optional)
                                    </label>
                                    <input
                                        type="text"
                                        wire:model="guest_name"
                                        class="w-full border border-gray-300 rounded-lg p-3 min-h-[44px]"
                                        placeholder="Enter your name">
                                </div>
                            </div>
                        </div>

                        {{-- Order Summary --}}
                        <div class="mt-6 bg-gray-50 rounded-lg p-4">
                            <h3 class="font-semibold text-gray-900 mb-3">Order Summary</h3>

                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Subtotal</span>
                                    <span class="text-gray-900">₪{{ number_format($subtotal, 2) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Tax (18%)</span>
                                    <span class="text-gray-900">₪{{ number_format($tax, 2) }}</span>
                                </div>
                                <div class="border-t border-gray-300 pt-2 flex justify-between">
                                    <span class="font-bold text-gray-900">Total</span>
                                    <span class="font-bold text-gray-900 text-lg">₪{{ number_format($total, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Place Order Button --}}
                @if(count($cart) > 0)
                    <div class="p-4 border-t border-gray-200 bg-white">
                        <button
                            wire:click="placeOrder"
                            class="w-full bg-gray-900 text-white py-4 px-6 rounded-lg font-semibold text-lg hover:bg-gray-800 transition active:scale-95 min-h-[56px]">
                            Place Order
                        </button>
                        @error('cart')
                            <p class="text-red-500 text-sm mt-2 text-center">{{ $message }}</p>
                        @enderror
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Bottom spacing for mobile --}}
    <div class="h-20"></div>
</div>
