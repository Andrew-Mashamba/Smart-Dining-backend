<div class="relative" x-data="{ open: @entangle('showDropdown') }">
    <!-- Notification Bell Button -->
    <button
        wire:click="toggleDropdown"
        @click.away="open = false"
        class="relative p-2 rounded-lg hover:bg-gray-50 transition-colors"
        aria-label="Notifications"
    >
        <!-- Bell Icon (monochrome design) -->
        <svg class="w-6 h-6 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
        </svg>

        <!-- Unread Badge -->
        @if($unreadCount > 0)
        <span class="absolute top-1 right-1 flex h-5 w-5 items-center justify-center rounded-full bg-gray-900 text-xs font-medium text-white">
            {{ $unreadCount > 9 ? '9+' : $unreadCount }}
        </span>
        @endif
    </button>

    <!-- Dropdown Menu -->
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 transform scale-95"
        x-transition:enter-end="opacity-100 transform scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 transform scale-100"
        x-transition:leave-end="opacity-0 transform scale-95"
        class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-sm border border-gray-200 py-2 z-50"
        style="display: none;"
    >
        <!-- Header -->
        <div class="px-4 py-2 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-sm font-medium text-gray-900">Notifications</h3>
            @if($unreadCount > 0)
            <button
                wire:click="markAllAsRead"
                class="text-xs text-gray-600 hover:text-gray-900 transition-colors"
            >
                Mark all read
            </button>
            @endif
        </div>

        <!-- Notifications List -->
        <div class="max-h-96 overflow-y-auto">
            @forelse($notifications as $notification)
            <div
                class="px-4 py-3 hover:bg-gray-50 transition-colors border-b border-gray-100 last:border-b-0"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <!-- Notification Icon -->
                        <div class="flex items-center gap-2 mb-1">
                            <svg class="w-4 h-4 text-gray-900 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                            </svg>
                            <span class="text-xs font-medium text-gray-900">Low Stock Alert</span>
                        </div>

                        <!-- Menu Item Name -->
                        <p class="text-sm text-gray-900 font-medium mb-1">
                            {{ $notification->data['menu_item_name'] }}
                        </p>

                        <!-- Stock Info -->
                        <p class="text-xs text-gray-600">
                            Current stock: <span class="font-medium text-gray-900">{{ $notification->data['current_stock'] }} {{ $notification->data['unit'] }}</span>
                        </p>

                        <!-- Timestamp -->
                        <p class="text-xs text-gray-500 mt-1">
                            {{ $notification->created_at->diffForHumans() }}
                        </p>
                    </div>

                    <!-- Mark as Read Button -->
                    <button
                        wire:click="markAsRead('{{ $notification->id }}')"
                        class="flex-shrink-0 p-1 rounded hover:bg-gray-100 transition-colors"
                        title="Mark as read"
                    >
                        <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            @empty
            <div class="px-4 py-8 text-center">
                <svg class="w-12 h-12 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                </svg>
                <p class="text-sm text-gray-600">No new notifications</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
