<div
    x-data="{
        audioContext: null,
        initAudio() {
            // Initialize Web Audio API for notification sound
            if (!this.audioContext) {
                this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
            }
        },
        playAlert() {
            this.initAudio();
            // Create a simple beep sound using Web Audio API
            const oscillator = this.audioContext.createOscillator();
            const gainNode = this.audioContext.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(this.audioContext.destination);

            oscillator.frequency.value = 800; // Frequency in Hz
            oscillator.type = 'sine';

            gainNode.gain.setValueAtTime(0.3, this.audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, this.audioContext.currentTime + 0.5);

            oscillator.start(this.audioContext.currentTime);
            oscillator.stop(this.audioContext.currentTime + 0.5);
        }
    }"
    x-init="
        @this.on('new-order-alert', () => {
            playAlert();
            // Flash animation for new orders
            const cards = document.querySelectorAll('.order-card');
            if (cards.length > 0) {
                cards[0].classList.add('animate-pulse');
                setTimeout(() => cards[0].classList.remove('animate-pulse'), 2000);
            }
        });
    "
    wire:poll.5s
    class="bar-display"
>
    <!-- Bar Display Header Info -->
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <div>
                <h2 class="text-3xl font-bold text-gray-900">Active Drink Orders</h2>
                <p class="text-gray-600 mt-1">{{ $orders->count() }} orders in queue</p>
            </div>
            <x-help-tooltip text="View all drink orders in real-time. Mark items as 'Preparing' when you start making them, and 'Completed' when drinks are ready for service. The display auto-refreshes every 5 seconds." position="right" />
        </div>
        <div class="text-right">
            <div class="text-sm text-gray-600">Auto-refresh every 5 seconds</div>
            <div class="text-xs text-gray-500 mt-1">Last updated: {{ now()->format('H:i:s') }}</div>
        </div>
    </div>

    @if($orders->isEmpty())
        <!-- No Orders Message -->
        <div class="bg-white rounded-xl shadow-sm p-12 text-center">
            <svg class="mx-auto h-24 w-24 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <h3 class="mt-4 text-2xl font-semibold text-gray-900">All Caught Up!</h3>
            <p class="mt-2 text-gray-600">No pending drink orders at the moment.</p>
        </div>
    @else
        <!-- Orders Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($orders as $order)
                <div
                    class="order-card bg-white rounded-xl shadow-sm border-2 {{ $order['is_high_priority'] ? 'border-gray-900 bg-gray-50' : 'border-gray-200' }} p-6 transition-all hover:shadow-lg"
                    wire:key="order-{{ $order['order_id'] }}"
                >
                    <!-- Order Header -->
                    <div class="flex items-start justify-between mb-4 pb-4 border-b border-gray-200">
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900">{{ $order['order_number'] }}</h3>
                            <p class="text-lg text-gray-600 mt-1">Table: {{ $order['table_name'] }}</p>
                        </div>
                        <div class="text-right">
                            <div class="inline-flex items-center px-3 py-1 rounded-full {{ $order['is_high_priority'] ? 'bg-gray-900 text-white' : 'bg-gray-200 text-gray-700' }}">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="text-sm font-semibold">{{ $order['elapsed_time'] }}</span>
                            </div>
                            @if($order['is_high_priority'])
                                <p class="text-xs text-gray-900 font-bold mt-1">PRIORITY</p>
                            @endif
                        </div>
                    </div>

                    <!-- Order Items -->
                    <div class="space-y-4">
                        @foreach($order['items'] as $item)
                            <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                                <!-- Item Details -->
                                <div class="mb-3">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <h4 class="text-lg font-bold text-gray-900">
                                                {{ $item['quantity'] }}x {{ $item['menu_item_name'] }}
                                            </h4>
                                            @if($item['special_instructions'])
                                                <p class="text-sm text-gray-600 mt-1 italic">
                                                    Note: {{ $item['special_instructions'] }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Status Buttons -->
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        wire:click="updateItemStatus({{ $item['id'] }}, 'received')"
                                        class="flex-1 px-4 py-2 text-sm font-semibold rounded-lg transition-colors {{ $item['prep_status'] === 'received' ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
                                        {{ $item['prep_status'] === 'ready' ? 'disabled' : '' }}
                                    >
                                        Received
                                    </button>

                                    <button
                                        wire:click="updateItemStatus({{ $item['id'] }}, 'preparing')"
                                        class="flex-1 px-4 py-2 text-sm font-semibold rounded-lg transition-colors {{ $item['prep_status'] === 'preparing' ? 'bg-gray-900 text-white' : 'bg-gray-200 text-gray-800 hover:bg-gray-300' }}"
                                        {{ $item['prep_status'] === 'ready' ? 'disabled' : '' }}
                                    >
                                        Preparing
                                    </button>

                                    <button
                                        wire:click="updateItemStatus({{ $item['id'] }}, 'ready')"
                                        class="flex-1 px-4 py-2 text-sm font-semibold rounded-lg transition-colors {{ $item['prep_status'] === 'ready' ? 'bg-gray-900 text-white' : 'bg-gray-300 text-gray-900 hover:bg-gray-400' }}"
                                    >
                                        Ready
                                    </button>
                                </div>

                                <!-- Current Status Indicator -->
                                <div class="mt-2 text-center">
                                    <span class="text-xs font-medium text-gray-600">
                                        Status:
                                        <span class="font-bold text-gray-900 uppercase">{{ $item['prep_status'] }}</span>
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Order Footer -->
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <p class="text-xs text-gray-600 text-center">
                            Order placed at {{ $order['created_at']->format('H:i') }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Loading Indicator -->
    <div wire:loading class="fixed bottom-4 right-4 bg-gray-900 text-white px-4 py-2 rounded-lg shadow-lg">
        <div class="flex items-center">
            <svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Updating...</span>
        </div>
    </div>
</div>

<style>
    /* Ensure large text for visibility */
    .bar-display h3 {
        font-size: 1.5rem;
    }

    .bar-display h4 {
        font-size: 1.125rem;
    }

    /* Smooth transitions for status updates */
    .order-card {
        transition: all 0.3s ease-in-out;
    }

    /* Print-friendly styles if needed */
    @media print {
        .order-card {
            page-break-inside: avoid;
        }
    }
</style>
