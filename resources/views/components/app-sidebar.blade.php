<!-- Sidebar for Desktop and Mobile -->
<aside
    class="fixed lg:static inset-y-0 left-0 z-40 w-64 bg-gray-50 border-r border-gray-200 transform transition-transform duration-300 ease-in-out lg:translate-x-0"
    :class="{ '-translate-x-full': !sidebarOpen, 'translate-x-0': sidebarOpen }"
>
    <div class="flex flex-col h-full">
        <!-- Mobile Header -->
        <div class="lg:hidden flex items-center justify-between p-4 border-b border-gray-200">
            <h2 class="text-lg font-bold text-gray-900">Menu</h2>
            <button
                @click="sidebarOpen = false"
                class="p-2 rounded-lg hover:bg-gray-100 transition-colors"
                aria-label="Close menu"
            >
                <svg class="w-5 h-5 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 px-4 py-6 overflow-y-auto">
            @auth
                @php
                    $role = auth()->user()->role;
                    $currentRoute = request()->route()->getName();
                @endphp

                <!-- Manager/Admin Navigation -->
                @if($role === 'admin' || $role === 'manager')
                    <div class="space-y-1">
                        <a
                            href="{{ route('dashboard') }}"
                            class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all {{ request()->routeIs('dashboard') ? 'bg-white shadow-sm text-gray-900 font-bold' : 'text-gray-600 hover:bg-white hover:shadow-sm hover:text-gray-900' }}"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                            </svg>
                            <span>Dashboard</span>
                        </a>

                        <a
                            href="{{ route('staff') }}"
                            class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all {{ request()->routeIs('staff') ? 'bg-white shadow-sm text-gray-900 font-bold' : 'text-gray-600 hover:bg-white hover:shadow-sm hover:text-gray-900' }}"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            <span>Staff</span>
                        </a>

                        <a
                            href="{{ route('reports') }}"
                            class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all {{ request()->routeIs('reports') ? 'bg-white shadow-sm text-gray-900 font-bold' : 'text-gray-600 hover:bg-white hover:shadow-sm hover:text-gray-900' }}"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            <span>Reports</span>
                        </a>

                        <a
                            href="{{ route('menu') }}"
                            class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all {{ request()->routeIs('menu') ? 'bg-white shadow-sm text-gray-900 font-bold' : 'text-gray-600 hover:bg-white hover:shadow-sm hover:text-gray-900' }}"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                            </svg>
                            <span>Menu</span>
                        </a>

                        <a
                            href="{{ route('inventory') }}"
                            class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all {{ request()->routeIs('inventory') ? 'bg-white shadow-sm text-gray-900 font-bold' : 'text-gray-600 hover:bg-white hover:shadow-sm hover:text-gray-900' }}"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                            <span>Inventory</span>
                        </a>

                        <a
                            href="{{ route('tables') }}"
                            class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all {{ request()->routeIs('tables') ? 'bg-white shadow-sm text-gray-900 font-bold' : 'text-gray-600 hover:bg-white hover:shadow-sm hover:text-gray-900' }}"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14v6m-3-3h6M6 10h2a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2zm10 0h2a2 2 0 002-2V6a2 2 0 00-2-2h-2a2 2 0 00-2 2v2a2 2 0 002 2zM6 20h2a2 2 0 002-2v-2a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2z"></path>
                            </svg>
                            <span>Tables</span>
                        </a>

                        <a
                            href="{{ route('guests') }}"
                            class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all {{ request()->routeIs('guests') ? 'bg-white shadow-sm text-gray-900 font-bold' : 'text-gray-600 hover:bg-white hover:shadow-sm hover:text-gray-900' }}"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            <span>Guests</span>
                        </a>

                        <a
                            href="{{ route('orders') }}"
                            class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all {{ request()->routeIs('orders') ? 'bg-white shadow-sm text-gray-900 font-bold' : 'text-gray-600 hover:bg-white hover:shadow-sm hover:text-gray-900' }}"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                            </svg>
                            <span>Orders</span>
                        </a>

                        <a
                            href="{{ route('orders.create') }}"
                            class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all {{ request()->routeIs('orders.create') ? 'bg-white shadow-sm text-gray-900 font-bold' : 'text-gray-600 hover:bg-white hover:shadow-sm hover:text-gray-900' }}"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            <span>New Order</span>
                        </a>

                        <a
                            href="{{ route('kitchen') }}"
                            class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all {{ request()->routeIs('kitchen') ? 'bg-white shadow-sm text-gray-900 font-bold' : 'text-gray-600 hover:bg-white hover:shadow-sm hover:text-gray-900' }}"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                            </svg>
                            <span>Kitchen Display</span>
                        </a>

                        <a
                            href="{{ route('settings') }}"
                            class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all {{ request()->routeIs('settings') ? 'bg-white shadow-sm text-gray-900 font-bold' : 'text-gray-600 hover:bg-white hover:shadow-sm hover:text-gray-900' }}"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <span>Settings</span>
                        </a>

                        <a
                            href="{{ route('help.index') }}"
                            class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all {{ request()->routeIs('help.*') ? 'bg-white shadow-sm text-gray-900 font-bold' : 'text-gray-600 hover:bg-white hover:shadow-sm hover:text-gray-900' }}"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Help & Docs</span>
                        </a>
                    </div>
                @endif

                <!-- Chef Navigation -->
                @if($role === 'chef')
                    <div class="space-y-1">
                        <a
                            href="{{ route('kitchen') }}"
                            class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all {{ request()->routeIs('kitchen') ? 'bg-white shadow-sm text-gray-900 font-bold' : 'text-gray-600 hover:bg-white hover:shadow-sm hover:text-gray-900' }}"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                            </svg>
                            <span>Kitchen Display</span>
                        </a>

                        <a
                            href="#"
                            class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-gray-600 hover:bg-white hover:shadow-sm hover:text-gray-900"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Order History</span>
                        </a>

                        <a
                            href="{{ route('help.index') }}"
                            class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all {{ request()->routeIs('help.*') ? 'bg-white shadow-sm text-gray-900 font-bold' : 'text-gray-600 hover:bg-white hover:shadow-sm hover:text-gray-900' }}"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Help & Docs</span>
                        </a>
                    </div>
                @endif

                <!-- Bartender Navigation -->
                @if($role === 'bartender')
                    <div class="space-y-1">
                        <a
                            href="{{ route('bar.display') }}"
                            class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all {{ str_starts_with($currentRoute, 'bar.') ? 'bg-white shadow-sm text-gray-900 font-bold' : 'text-gray-600 hover:bg-white hover:shadow-sm hover:text-gray-900' }}"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            <span>Bar Display</span>
                        </a>

                        <a
                            href="#"
                            class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-gray-600 hover:bg-white hover:shadow-sm hover:text-gray-900"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Order History</span>
                        </a>

                        <a
                            href="{{ route('help.index') }}"
                            class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all {{ request()->routeIs('help.*') ? 'bg-white shadow-sm text-gray-900 font-bold' : 'text-gray-600 hover:bg-white hover:shadow-sm hover:text-gray-900' }}"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Help & Docs</span>
                        </a>
                    </div>
                @endif

                <!-- Waiter Navigation -->
                @if($role === 'waiter')
                    <div class="space-y-1">
                        <a
                            href="{{ route('orders.create') }}"
                            class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all {{ request()->routeIs('orders.create') ? 'bg-white shadow-sm text-gray-900 font-bold' : 'text-gray-600 hover:bg-white hover:shadow-sm hover:text-gray-900' }}"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            <span>New Order</span>
                        </a>

                        <a
                            href="{{ route('orders') }}"
                            class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all {{ request()->routeIs('orders') ? 'bg-white shadow-sm text-gray-900 font-bold' : 'text-gray-600 hover:bg-white hover:shadow-sm hover:text-gray-900' }}"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                            </svg>
                            <span>Orders</span>
                        </a>

                        <a
                            href="#"
                            class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-gray-600 hover:bg-white hover:shadow-sm hover:text-gray-900"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14v6m-3-3h6M6 10h2a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2zm10 0h2a2 2 0 002-2V6a2 2 0 00-2-2h-2a2 2 0 00-2 2v2a2 2 0 002 2zM6 20h2a2 2 0 002-2v-2a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2z"></path>
                            </svg>
                            <span>Tables</span>
                        </a>

                        <a
                            href="{{ route('help.index') }}"
                            class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all {{ request()->routeIs('help.*') ? 'bg-white shadow-sm text-gray-900 font-bold' : 'text-gray-600 hover:bg-white hover:shadow-sm hover:text-gray-900' }}"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Help & Docs</span>
                        </a>
                    </div>
                @endif
            @endauth
        </nav>
    </div>
</aside>

<!-- Mobile Overlay -->
<div
    x-show="sidebarOpen"
    @click="sidebarOpen = false"
    class="fixed inset-0 bg-gray-900 bg-opacity-50 z-30 lg:hidden"
    x-transition:enter="transition-opacity ease-linear duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity ease-linear duration-300"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    style="display: none;"
></div>
