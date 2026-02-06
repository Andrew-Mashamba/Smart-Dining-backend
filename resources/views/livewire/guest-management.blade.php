<div>
    <!-- Page Header -->
    <div class="mb-6">
        <div class="flex items-center gap-2">
            <h1 class="text-2xl font-bold text-gray-900">Guest Management</h1>
            <x-help-tooltip text="Manage guest profiles and track customer information. View order history, loyalty points, and preferences. Guests can be created manually or automatically when they place orders via QR code." position="right" />
        </div>
        <p class="text-gray-600 mt-1">View guest profiles, order history, and loyalty points</p>
    </div>

    <!-- Success/Error Messages -->
    @if (session()->has('success'))
        <div class="mb-4 bg-white border border-gray-300 text-gray-900 px-4 py-3 rounded-xl">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-4 bg-white border border-gray-300 text-gray-900 px-4 py-3 rounded-xl">
            {{ session('error') }}
        </div>
    @endif

    <!-- Search and Add Guest -->
    <div class="mb-6 flex flex-col sm:flex-row gap-3">
        <div class="flex-1">
            <input
                type="text"
                wire:model.live="search"
                placeholder="Search by phone number or name..."
                class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-gray-400 text-gray-900"
            />
        </div>
        <button
            wire:click="addGuest"
            class="bg-white text-gray-900 border border-gray-300 px-4 py-2 rounded-xl hover:bg-gray-50 transition-colors font-medium whitespace-nowrap"
        >
            Add Guest
        </button>
    </div>

    <!-- Guests Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                            Phone Number
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                            Name
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                            Loyalty Points
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                            Total Orders
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                            Last Order Date
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($guests as $guest)
                        <tr
                            wire:click="viewGuest({{ $guest['id'] }})"
                            class="hover:bg-gray-50 cursor-pointer transition-colors"
                        >
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $guest['phone_number'] }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $guest['name'] }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-bold text-gray-900">{{ number_format($guest['loyalty_points']) }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $guest['total_orders'] }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-600">{{ $guest['last_order_date'] }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-600">
                                No guests found. {{ $search ? 'Try a different search term.' : 'Add your first guest to get started.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Guest Modal -->
    @if($showAddGuestModal)
        <div class="fixed inset-0 bg-gray-900 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold text-gray-900">Add New Guest</h3>
                        <button
                            wire:click="closeAddGuestModal"
                            class="text-gray-600 hover:text-gray-900 transition-colors"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">
                            Phone Number <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            wire:model="guestPhoneNumber"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-400 text-gray-900"
                            placeholder="Enter phone number"
                        />
                        @error('guestPhoneNumber')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">
                            Name <span class="text-gray-400">(optional)</span>
                        </label>
                        <input
                            type="text"
                            wire:model="guestName"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-400 text-gray-900"
                            placeholder="Enter guest name"
                        />
                        @error('guestName')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                    <button
                        wire:click="closeAddGuestModal"
                        class="bg-white text-gray-900 border border-gray-300 px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors font-medium"
                    >
                        Cancel
                    </button>
                    <button
                        wire:click="saveGuest"
                        class="bg-gray-900 text-white px-4 py-2 rounded-lg hover:bg-gray-800 transition-colors font-medium"
                    >
                        Add Guest
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Guest Details Modal -->
    @if($showGuestDetailsModal && $selectedGuest)
        <div class="fixed inset-0 bg-gray-900 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-200 sticky top-0 bg-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">Guest Details</h3>
                            <p class="text-sm text-gray-600 mt-1">{{ $selectedGuest->phone_number }}</p>
                        </div>
                        <button
                            wire:click="closeGuestDetailsModal"
                            class="text-gray-600 hover:text-gray-900 transition-colors"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-4 space-y-6">
                    <!-- Guest Information -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <p class="text-xs font-medium text-gray-600 uppercase tracking-wider">Name</p>
                                <p class="mt-1 text-sm font-medium text-gray-900">{{ $selectedGuest->name ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-600 uppercase tracking-wider">Total Orders</p>
                                <p class="mt-1 text-sm font-medium text-gray-900">{{ count($guestOrders) }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-600 uppercase tracking-wider">Loyalty Points</p>
                                <p class="mt-1 text-lg font-bold text-gray-900">{{ number_format($selectedGuest->loyalty_points) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Points Adjustment Button -->
                    <div class="flex justify-end">
                        <button
                            wire:click="openPointsModal"
                            class="bg-white text-gray-900 border border-gray-300 px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors font-medium text-sm"
                        >
                            Adjust Points
                        </button>
                    </div>

                    <!-- Order History -->
                    <div>
                        <h4 class="text-md font-bold text-gray-900 mb-3">Order History</h4>
                        @if(count($guestOrders) > 0)
                            <div class="space-y-3">
                                @foreach($guestOrders as $order)
                                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1">
                                                <div class="flex items-center gap-3">
                                                    <p class="font-semibold text-gray-900">{{ $order['order_number'] }}</p>
                                                    <span class="px-2 py-1 text-xs font-medium rounded
                                                        {{ $order['status'] === 'paid' ? 'bg-green-100 text-green-800' : '' }}
                                                        {{ $order['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                        {{ $order['status'] === 'cancelled' ? 'bg-red-100 text-red-800' : '' }}
                                                    ">
                                                        {{ ucfirst($order['status']) }}
                                                    </span>
                                                </div>
                                                <div class="mt-2 space-y-1">
                                                    <p class="text-sm text-gray-600">
                                                        <span class="font-medium text-gray-900">Date:</span> {{ $order['date'] }}
                                                    </p>
                                                    <p class="text-sm text-gray-600">
                                                        <span class="font-medium text-gray-900">Total:</span> ${{ number_format($order['total'], 2) }}
                                                    </p>
                                                    <p class="text-sm text-gray-600">
                                                        <span class="font-medium text-gray-900">Items:</span> {{ $order['items_count'] }}
                                                    </p>
                                                    <p class="text-sm text-gray-600">
                                                        <span class="font-medium text-gray-900">Table:</span> {{ $order['table'] }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-center text-gray-600 py-4">No orders yet.</p>
                        @endif
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end">
                    <button
                        wire:click="closeGuestDetailsModal"
                        class="bg-white text-gray-900 border border-gray-300 px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors font-medium"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Points Adjustment Modal -->
    @if($showPointsModal && $selectedGuest)
        <div class="fixed inset-0 bg-gray-900 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold text-gray-900">Adjust Loyalty Points</h3>
                        <button
                            wire:click="closePointsModal"
                            class="text-gray-600 hover:text-gray-900 transition-colors"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-4 space-y-4">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-xs font-medium text-gray-600 uppercase tracking-wider">Current Points</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($selectedGuest->loyalty_points) }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">
                            Points Adjustment <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="number"
                            wire:model="pointsAdjustment"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-400 text-gray-900"
                            placeholder="Enter positive or negative number (e.g., 100 or -50)"
                        />
                        <p class="mt-1 text-xs text-gray-600">Use positive numbers to add points, negative to subtract</p>
                        @error('pointsAdjustment')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">
                            Reason <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            wire:model="pointsReason"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-400 text-gray-900"
                            placeholder="Enter reason for adjustment"
                        />
                        @error('pointsReason')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                    <button
                        wire:click="closePointsModal"
                        class="bg-white text-gray-900 border border-gray-300 px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors font-medium"
                    >
                        Cancel
                    </button>
                    <button
                        wire:click="adjustPoints"
                        class="bg-gray-900 text-white px-4 py-2 rounded-lg hover:bg-gray-800 transition-colors font-medium"
                    >
                        Adjust Points
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
