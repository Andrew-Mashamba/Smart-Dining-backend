<div class="p-6" wire:poll.5s>
    <!-- Page Header -->
    <div class="mb-6">
        <div class="flex items-center gap-2">
            <h1 class="text-2xl font-bold text-gray-900">Table Management</h1>
            <x-help-tooltip text="Create and manage restaurant tables. Each table gets a unique QR code that guests can scan to place orders. You can set capacity, location, and monitor table status (available, occupied, reserved)." position="right" />
        </div>
        <p class="text-gray-600">Manage restaurant tables, generate QR codes, and monitor status</p>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-gray-100 border border-gray-300 rounded-lg text-gray-900">
            {{ session('message') }}
        </div>
    @endif

    <!-- Add/Edit Table Form -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
            {{ $editingTableId ? 'Edit Table' : 'Add New Table' }}
        </h2>

        <form wire:submit.prevent="{{ $editingTableId ? 'updateTable' : 'addTable' }}">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <!-- Table Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                        Table Name
                    </label>
                    <input
                        type="text"
                        id="name"
                        wire:model="name"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                        placeholder="e.g., Table 1"
                    >
                    @error('name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <!-- Location -->
                <div>
                    <label for="location" class="block text-sm font-medium text-gray-700 mb-1">
                        Location
                    </label>
                    <input
                        type="text"
                        id="location"
                        wire:model="location"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                        placeholder="e.g., Main Dining Area"
                    >
                    @error('location') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <!-- Capacity -->
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <label for="capacity" class="block text-sm font-medium text-gray-700">
                            Capacity
                        </label>
                        <x-help-tooltip text="Maximum number of guests this table can accommodate. Helps staff assign appropriate tables to parties." position="right" />
                    </div>
                    <input
                        type="number"
                        id="capacity"
                        wire:model="capacity"
                        min="1"
                        max="20"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                        placeholder="e.g., 4"
                    >
                    @error('capacity') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Form Buttons -->
            <div class="flex gap-2">
                <button
                    type="submit"
                    class="px-6 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 transition-colors"
                >
                    {{ $editingTableId ? 'Update Table' : 'Add Table' }}
                </button>

                @if($editingTableId)
                    <button
                        type="button"
                        wire:click="cancelEdit"
                        class="px-6 py-2 bg-gray-200 text-gray-900 rounded-lg hover:bg-gray-300 transition-colors"
                    >
                        Cancel
                    </button>
                @endif
            </div>
        </form>
    </div>

    <!-- Tables Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @forelse($tables as $table)
            <div class="bg-white rounded-lg shadow-sm overflow-hidden
                {{ $table->status === 'available' ? 'bg-gray-100' : '' }}
                {{ $table->status === 'occupied' ? 'bg-gray-900 text-white' : '' }}
                {{ $table->status === 'reserved' ? 'bg-gray-300' : '' }}
            ">
                <!-- Table Header -->
                <div class="p-4 border-b {{ $table->status === 'occupied' ? 'border-gray-700' : 'border-gray-200' }}">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-lg font-bold {{ $table->status === 'occupied' ? 'text-white' : 'text-gray-900' }}">
                            {{ $table->name }}
                        </h3>
                        <span class="px-2 py-1 text-xs font-semibold rounded
                            {{ $table->status === 'available' ? 'bg-white text-gray-900' : '' }}
                            {{ $table->status === 'occupied' ? 'bg-white text-gray-900' : '' }}
                            {{ $table->status === 'reserved' ? 'bg-white text-gray-900' : '' }}
                        ">
                            {{ ucfirst($table->status) }}
                        </span>
                    </div>
                    <p class="text-sm {{ $table->status === 'occupied' ? 'text-gray-300' : 'text-gray-600' }}">
                        {{ $table->location }}
                    </p>
                    <p class="text-sm {{ $table->status === 'occupied' ? 'text-gray-300' : 'text-gray-600' }}">
                        Capacity: {{ $table->capacity }} guests
                    </p>
                </div>

                <!-- QR Code Display -->
                @if($table->qr_code)
                    <div class="p-4 bg-white flex justify-center">
                        <div class="w-48 h-48">
                            <img src="{{ asset('storage/' . $table->qr_code) }}"
                                 alt="QR Code for {{ $table->name }}"
                                 class="w-full h-full object-contain">
                        </div>
                    </div>
                @endif

                <!-- Table Actions -->
                <div class="p-4 space-y-2">
                    <!-- Status Update Buttons -->
                    <div class="flex gap-2 mb-2">
                        <button
                            wire:click="updateStatus({{ $table->id }}, 'available')"
                            class="flex-1 px-3 py-1 text-xs font-semibold rounded {{ $table->status === 'available' ? 'bg-white text-gray-900' : ($table->status === 'occupied' ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-200 text-gray-900 hover:bg-gray-100') }}"
                        >
                            Available
                        </button>
                        <button
                            wire:click="updateStatus({{ $table->id }}, 'occupied')"
                            class="flex-1 px-3 py-1 text-xs font-semibold rounded {{ $table->status === 'occupied' ? 'bg-white text-gray-900' : ($table->status === 'occupied' ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-200 text-gray-900 hover:bg-gray-100') }}"
                        >
                            Occupied
                        </button>
                        <button
                            wire:click="updateStatus({{ $table->id }}, 'reserved')"
                            class="flex-1 px-3 py-1 text-xs font-semibold rounded {{ $table->status === 'reserved' ? 'bg-white text-gray-900' : ($table->status === 'occupied' ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-200 text-gray-900 hover:bg-gray-100') }}"
                        >
                            Reserved
                        </button>
                    </div>

                    <!-- Action Buttons -->
                    @if(!$table->qr_code)
                        <button
                            wire:click="generateQrCode({{ $table->id }})"
                            class="w-full px-4 py-2 text-sm font-semibold rounded transition-colors
                                {{ $table->status === 'occupied' ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-900 text-white hover:bg-gray-800' }}
                            "
                        >
                            Generate QR Code
                        </button>
                    @else
                        <div class="flex gap-2">
                            <button
                                wire:click="regenerateQrCode({{ $table->id }})"
                                class="flex-1 px-4 py-2 text-sm font-semibold rounded transition-colors
                                    {{ $table->status === 'occupied' ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-900 text-white hover:bg-gray-800' }}
                                "
                            >
                                Regenerate QR
                            </button>
                            <a
                                href="{{ asset('storage/' . $table->qr_code) }}"
                                download="table_{{ $table->name }}_qr.png"
                                class="flex-1 px-4 py-2 text-sm font-semibold rounded transition-colors text-center
                                    {{ $table->status === 'occupied' ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-green-600 text-white hover:bg-green-700' }}
                                "
                            >
                                Download QR
                            </a>
                        </div>
                    @endif

                    <div class="flex gap-2">
                        <button
                            wire:click="editTable({{ $table->id }})"
                            class="flex-1 px-4 py-2 text-sm font-semibold rounded transition-colors
                                {{ $table->status === 'occupied' ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-200 text-gray-900 hover:bg-gray-300' }}
                            "
                        >
                            Edit
                        </button>
                        <button
                            wire:click="confirmDelete({{ $table->id }})"
                            class="flex-1 px-4 py-2 bg-red-600 text-white text-sm font-semibold rounded hover:bg-red-700 transition-colors"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-lg shadow-sm p-8 text-center">
                <p class="text-gray-600">No tables found. Create your first table above.</p>
            </div>
        @endforelse
    </div>

    <!-- Delete Confirmation Modal -->
    @if($showDeleteConfirmation)
        <div class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Confirm Delete</h3>
                <p class="text-gray-600 mb-6">
                    Are you sure you want to delete this table? This action cannot be undone.
                </p>
                <div class="flex gap-4">
                    <button
                        wire:click="deleteTable"
                        class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
                    >
                        Delete
                    </button>
                    <button
                        wire:click="cancelDelete"
                        class="flex-1 px-4 py-2 bg-gray-200 text-gray-900 rounded-lg hover:bg-gray-300 transition-colors"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
