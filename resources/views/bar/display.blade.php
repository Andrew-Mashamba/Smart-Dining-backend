<x-layouts.app title="Bar Display System">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Bar Display System</h1>
            <p class="text-gray-600">Drink Orders Queue</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Pending Items -->
            <div class="bg-purple-50 rounded-lg shadow-lg p-4">
                <h2 class="text-xl font-bold text-purple-800 mb-4">Pending ({{ $pendingItems->count() }})</h2>
                <div class="space-y-4">
                    @forelse($pendingItems as $item)
                    <div class="bg-white rounded-lg p-4 shadow">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <span class="text-lg font-bold text-gray-900">{{ $item->menuItem->name }}</span>
                                <span class="ml-2 bg-purple-100 text-purple-800 text-xs font-semibold px-2 py-1 rounded">x{{ $item->quantity }}</span>
                            </div>
                            <span class="text-sm text-gray-500">#{{ $item->order_id }}</span>
                        </div>
                        <p class="text-sm text-gray-600 mb-2">Table: {{ $item->order->table->name }}</p>
                        @if($item->special_instructions)
                        <p class="text-sm text-red-600 font-semibold mb-2">Note: {{ $item->special_instructions }}</p>
                        @endif
                        <p class="text-xs text-gray-500 mb-3">{{ $item->created_at->diffForHumans() }}</p>
                        <form action="{{ route('bar.mark-received', $item->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Start Preparing
                            </button>
                        </form>
                    </div>
                    @empty
                    <p class="text-gray-500 text-center py-4">No pending items</p>
                    @endforelse
                </div>
            </div>

            <!-- Preparing Items -->
            <div class="bg-blue-50 rounded-lg shadow-lg p-4">
                <h2 class="text-xl font-bold text-blue-800 mb-4">Preparing ({{ $preparingItems->count() }})</h2>
                <div class="space-y-4">
                    @forelse($preparingItems as $item)
                    <div class="bg-white rounded-lg p-4 shadow border-l-4 border-blue-500">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <span class="text-lg font-bold text-gray-900">{{ $item->menuItem->name }}</span>
                                <span class="ml-2 bg-blue-100 text-blue-800 text-xs font-semibold px-2 py-1 rounded">x{{ $item->quantity }}</span>
                            </div>
                            <span class="text-sm text-gray-500">#{{ $item->order_id }}</span>
                        </div>
                        <p class="text-sm text-gray-600 mb-2">Table: {{ $item->order->table->name }}</p>
                        @if($item->special_instructions)
                        <p class="text-sm text-red-600 font-semibold mb-2">Note: {{ $item->special_instructions }}</p>
                        @endif
                        <p class="text-xs text-gray-500 mb-2">Bartender: {{ $item->preparedBy->name ?? 'N/A' }}</p>
                        <p class="text-xs text-gray-500 mb-3">Started: {{ $item->updated_at->diffForHumans() }}</p>
                        <form action="{{ route('bar.mark-done', $item->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                Mark as Ready
                            </button>
                        </form>
                    </div>
                    @empty
                    <p class="text-gray-500 text-center py-4">No items being prepared</p>
                    @endforelse
                </div>
            </div>

            <!-- Ready Items -->
            <div class="bg-green-50 rounded-lg shadow-lg p-4">
                <h2 class="text-xl font-bold text-green-800 mb-4">Ready ({{ $readyItems->count() }})</h2>
                <div class="space-y-4">
                    @forelse($readyItems as $item)
                    <div class="bg-white rounded-lg p-4 shadow border-l-4 border-green-500">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <span class="text-lg font-bold text-gray-900">{{ $item->menuItem->name }}</span>
                                <span class="ml-2 bg-green-100 text-green-800 text-xs font-semibold px-2 py-1 rounded">x{{ $item->quantity }}</span>
                            </div>
                            <span class="text-sm text-gray-500">#{{ $item->order_id }}</span>
                        </div>
                        <p class="text-sm text-gray-600 mb-2">Table: {{ $item->order->table->name }}</p>
                        <p class="text-xs text-gray-500">Ready: {{ $item->updated_at->diffForHumans() }}</p>
                    </div>
                    @empty
                    <p class="text-gray-500 text-center py-4">No ready items</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh every 5 seconds
        setTimeout(function() {
            window.location.reload();
        }, 5000);
    </script>
</x-layouts.app>
