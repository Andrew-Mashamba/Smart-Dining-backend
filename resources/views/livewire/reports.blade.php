<div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Reports Heading --}}
        <div class="mb-6">
            <div class="flex items-center gap-2">
                <h1 class="text-lg font-bold text-gray-900">Reports</h1>
                <x-help-tooltip text="Access various reports to analyze your business performance. View sales analytics, inventory levels, and staff performance metrics. Export reports to PDF or Excel for offline analysis." position="right" />
            </div>
            <p class="text-gray-600 mt-1">Access comprehensive business intelligence and analytics</p>
        </div>

        {{-- Report Type Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            {{-- Sales Reports Card --}}
            <a href="{{ route('reports.sales') }}" class="bg-white shadow-sm rounded-lg p-6 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-gray-100 p-3 rounded-lg">
                        <svg class="w-6 h-6 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </div>
                <h3 class="text-gray-900 font-semibold text-lg mb-2">Sales Reports</h3>
                <p class="text-gray-600 text-sm">View revenue analytics, category breakdowns, and top selling items</p>
            </a>

            {{-- Inventory Reports Card --}}
            <div class="bg-white shadow-sm rounded-lg p-6 opacity-60">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-gray-100 p-3 rounded-lg">
                        <svg class="w-6 h-6 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                </div>
                <h3 class="text-gray-900 font-semibold text-lg mb-2">Inventory Reports</h3>
                <p class="text-gray-600 text-sm">Track stock levels, reorder points, and inventory movement</p>
                <span class="inline-block mt-2 text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">Coming Soon</span>
            </div>

            {{-- Staff Performance Reports Card --}}
            <div class="bg-white shadow-sm rounded-lg p-6 opacity-60">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-gray-100 p-3 rounded-lg">
                        <svg class="w-6 h-6 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                </div>
                <h3 class="text-gray-900 font-semibold text-lg mb-2">Staff Performance</h3>
                <p class="text-gray-600 text-sm">Analyze employee performance, tips, and productivity metrics</p>
                <span class="inline-block mt-2 text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">Coming Soon</span>
            </div>
        </div>

        {{-- Success Message --}}
        @if (session()->has('message'))
            <div class="mb-6 bg-white border border-gray-200 rounded-lg p-4">
                <p class="text-gray-600">{{ session('message') }}</p>
            </div>
        @endif

        {{-- Report Filters --}}
        <div class="bg-white shadow-sm rounded-lg p-6 mb-6">
            <h2 class="text-gray-900 font-semibold mb-4">Report Parameters</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                {{-- Date Range Selector --}}
                <div>
                    <label for="start_date" class="block text-gray-600 text-sm mb-2">Start Date</label>
                    <input
                        type="date"
                        id="start_date"
                        wire:model="start_date"
                        class="w-full px-4 py-2 border border-gray-200 rounded-lg text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                    >
                    @error('start_date')
                        <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label for="end_date" class="block text-gray-600 text-sm mb-2">End Date</label>
                    <input
                        type="date"
                        id="end_date"
                        wire:model="end_date"
                        class="w-full px-4 py-2 border border-gray-200 rounded-lg text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                    >
                    @error('end_date')
                        <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Report Type Dropdown --}}
                <div>
                    <label for="report_type" class="block text-gray-600 text-sm mb-2">Report Type</label>
                    <select
                        id="report_type"
                        wire:model="report_type"
                        class="w-full px-4 py-2 border border-gray-200 rounded-lg text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                    >
                        <option value="sales">Sales</option>
                        <option value="inventory">Inventory</option>
                        <option value="staff_performance">Staff Performance</option>
                    </select>
                    @error('report_type')
                        <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            {{-- Generate Button --}}
            <div>
                <button
                    wire:click="generateReport"
                    class="bg-white text-gray-900 border border-gray-200 px-6 py-2 rounded-lg hover:bg-gray-50 transition-colors font-medium"
                >
                    Generate Report
                </button>
            </div>
        </div>

        {{-- Placeholder Chart Area --}}
        <div class="bg-gray-50 rounded-xl p-6">
            <div class="flex items-center justify-center h-64">
                <p class="text-gray-400 text-lg">Chart will appear here</p>
            </div>
        </div>
    </div>
</div>
