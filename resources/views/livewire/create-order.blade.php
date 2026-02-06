<div>
    <!-- Page Header -->
    <div class="mb-6">
        <div class="flex items-center gap-2">
            <h1 class="text-2xl font-bold text-gray-900">New Order</h1>
            <x-help-tooltip text="Select items from the menu, add them to the cart, choose a table, and place the order. Orders are automatically sent to the kitchen/bar." position="right" />
        </div>
        <p class="mt-1 text-sm text-gray-600">Create a new order for your customers</p>
    </div>

    <!-- Two-column Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column: Menu Items (2/3 width) -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="p-6">
                    <!-- Search and Filter -->
                    <div class="mb-6 space-y-4">
                        <!-- Search Bar -->
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            <input
                                type="text"
                                wire:model.live="searchTerm"
                                placeholder="Search menu items..."
                                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                            >
                        </div>

                        <!-- Category Filter -->
                        <div class="flex gap-2 flex-wrap">
                            <button
                                wire:click="$set('selectedCategoryId', '')"
                                class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $selectedCategoryId === '' ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
                            >
                                All Items
                            </button>
                            @foreach($categories as $category)
                                <button
                                    wire:click="$set('selectedCategoryId', {{ $category->id }})"
                                    class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $selectedCategoryId == $category->id ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
                                >
                                    {{ $category->name }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Menu Items Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        @forelse($menuItems as $item)
                            <div class="border border-gray-200 rounded-lg p-4 hover:border-gray-900 transition-colors cursor-pointer" wire:click="addItem({{ $item->id }})">
                                <div class="flex justify-between items-start mb-2">
                                    <h3 class="font-semibold text-gray-900">{{ $item->name }}</h3>
                                    <span class="text-lg font-bold text-gray-900">${{ number_format($item->price, 2) }}</span>
                                </div>
                                @if($item->description)
                                    <p class="text-sm text-gray-600 mb-2">{{ Str::limit($item->description, 60) }}</p>
                                @endif
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">{{ $item->menuCategory->name }}</span>
                                    <button
                                        wire:click.stop="addItem({{ $item->id }})"
                                        class="text-sm bg-gray-900 text-white px-3 py-1 rounded-lg hover:bg-gray-800 transition-colors"
                                    >
                                        Add
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="col-span-full text-center py-12 text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                </svg>
                                <p>No menu items found</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Order Cart (1/3 width) -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 sticky top-6">
                <div class="p-6">
                    <h2 class="text-lg font-bold text-gray-900 mb-4">Order Cart</h2>

                    <!-- Table Selection -->
                    <div class="mb-6">
                        <div class="flex items-center gap-2 mb-2">
                            <label class="block text-sm font-medium text-gray-700">Select Table</label>
                            <x-help-tooltip text="Choose the table number for this order. Only available tables are shown." position="right" />
                        </div>
                        <select
                            wire:model="selectedTableId"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent {{ $errors->has('selectedTableId') ? 'border-red-500' : '' }}"
                        >
                            <option value="">Choose a table...</option>
                            @foreach($tables as $table)
                                <option value="{{ $table->id }}">
                                    {{ $table->name }} ({{ ucfirst($table->status) }})
                                </option>
                            @endforeach
                        </select>
                        @error('selectedTableId')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Cart Items -->
                    <div class="mb-6">
                        @if(count($cart) > 0)
                            <div class="space-y-4 max-h-96 overflow-y-auto">
                                @foreach($cart as $index => $item)
                                    <div class="border border-gray-200 rounded-lg p-3">
                                        <div class="flex justify-between items-start mb-2">
                                            <h4 class="font-medium text-gray-900 text-sm">{{ $item['name'] }}</h4>
                                            <button
                                                wire:click="removeItem({{ $index }})"
                                                class="text-red-600 hover:text-red-800 transition-colors"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>

                                        <!-- Quantity and Price -->
                                        <div class="flex items-center justify-between mb-2">
                                            <div class="flex items-center gap-2">
                                                <button
                                                    wire:click="updateQuantity({{ $index }}, {{ $item['quantity'] - 1 }})"
                                                    class="w-6 h-6 flex items-center justify-center bg-gray-100 rounded hover:bg-gray-200"
                                                    {{ $item['quantity'] <= 1 ? 'disabled' : '' }}
                                                >
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                                    </svg>
                                                </button>
                                                <span class="font-medium w-8 text-center">{{ $item['quantity'] }}</span>
                                                <button
                                                    wire:click="updateQuantity({{ $index }}, {{ $item['quantity'] + 1 }})"
                                                    class="w-6 h-6 flex items-center justify-center bg-gray-100 rounded hover:bg-gray-200"
                                                >
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-xs text-gray-600">${{ number_format($item['unit_price'], 2) }} each</div>
                                                <div class="font-bold text-gray-900">${{ number_format($item['unit_price'] * $item['quantity'], 2) }}</div>
                                            </div>
                                        </div>

                                        <!-- Special Instructions -->
                                        <div>
                                            <textarea
                                                wire:model.blur="cart.{{ $index }}.special_instructions"
                                                placeholder="Special instructions..."
                                                rows="2"
                                                class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-gray-900 focus:border-transparent"
                                            ></textarea>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8 text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                </svg>
                                <p class="text-sm">Cart is empty</p>
                                <p class="text-xs mt-1">Add items from the menu</p>
                            </div>
                        @endif

                        @error('cart')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Order Totals -->
                    @if(count($cart) > 0)
                        <div class="border-t border-gray-200 pt-4 mb-6 space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Subtotal</span>
                                <span class="font-medium text-gray-900">${{ number_format($subtotal, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Tax ({{ $taxRate * 100 }}%)</span>
                                <span class="font-medium text-gray-900">${{ number_format($tax, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-lg font-bold border-t border-gray-200 pt-2">
                                <span class="text-gray-900">Total</span>
                                <span class="text-gray-900">${{ number_format($total, 2) }}</span>
                            </div>
                        </div>
                    @endif

                    <!-- Action Buttons -->
                    <div class="space-y-2">
                        <button
                            wire:click="placeOrder"
                            class="w-full bg-gray-900 text-white py-3 rounded-lg font-medium hover:bg-gray-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            {{ count($cart) === 0 || !$selectedTableId ? 'disabled' : '' }}
                        >
                            Place Order
                        </button>

                        @if(count($cart) > 0)
                            <button
                                wire:click="clearCart"
                                class="w-full bg-gray-100 text-gray-700 py-2 rounded-lg font-medium hover:bg-gray-200 transition-colors"
                            >
                                Clear Cart
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
