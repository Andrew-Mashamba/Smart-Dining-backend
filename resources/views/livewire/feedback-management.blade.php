<div class="p-6">
    {{-- Flash Messages --}}
    @if(session()->has('success'))
        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800">{{ session('success') }}</div>
    @endif
    @if(session()->has('error'))
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800">{{ session('error') }}</div>
    @endif

    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Guest Feedback</h1>
        <p class="text-sm text-gray-500 mt-1">View and respond to guest reviews and feedback</p>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</div>
            <div class="text-sm text-gray-500">Total Feedback</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="text-2xl font-bold text-yellow-500 flex items-center gap-1">
                {{ $stats['avg_rating'] }}
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
            </div>
            <div class="text-sm text-gray-500">Average Rating</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="text-2xl font-bold text-orange-500">{{ $stats['unresponded'] }}</div>
            <div class="text-sm text-gray-500">Awaiting Response</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="space-y-1">
                @foreach($stats['distribution'] as $star => $data)
                    <div class="flex items-center gap-2 text-xs">
                        <span class="w-3 text-right">{{ $star }}</span>
                        <svg class="w-3 h-3 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        <div class="flex-1 bg-gray-200 rounded-full h-1.5">
                            <div class="bg-yellow-400 h-1.5 rounded-full" style="width: {{ $data['percent'] }}%"></div>
                        </div>
                        <span class="w-6 text-gray-400">{{ $data['count'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Search & Filters --}}
    <div class="flex gap-3 mb-6">
        <div class="flex-1">
            <input type="text" wire:model.live="search" placeholder="Search by guest name or phone..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-gray-900">
        </div>
        <select wire:model.live="filterRating" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900">
            <option value="">All Ratings</option>
            <option value="5">5 Stars</option>
            <option value="4">4 Stars</option>
            <option value="3">3 Stars</option>
            <option value="2">2 Stars</option>
            <option value="1">1 Star</option>
        </select>
        <select wire:model.live="filterStatus" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900">
            <option value="">All</option>
            <option value="unresponded">Awaiting Response</option>
            <option value="responded">Responded</option>
        </select>
    </div>

    {{-- Feedback List --}}
    <div class="space-y-3">
        @forelse($feedbackList as $fb)
            <div wire:click="viewFeedback({{ $fb->id }})" class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition-shadow cursor-pointer">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-1">
                            <span class="font-semibold text-gray-900">{{ $fb->guest->name ?? 'Guest' }}</span>
                            <span class="text-xs text-gray-400">{{ $fb->guest->phone_number ?? '' }}</span>
                            @if($fb->order)
                                <span class="text-xs text-gray-400">Order #{{ $fb->order->order_number }}</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-1 mb-2">
                            @for($i = 1; $i <= 5; $i++)
                                <svg class="w-4 h-4 {{ $i <= $fb->rating ? 'text-yellow-400' : 'text-gray-200' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            @endfor
                        </div>
                        @if($fb->comment)
                            <p class="text-sm text-gray-600">{{ Str::limit($fb->comment, 150) }}</p>
                        @endif
                    </div>
                    <div class="text-right">
                        <div class="text-xs text-gray-400">{{ $fb->created_at->diffForHumans() }}</div>
                        @if($fb->staff_response)
                            <span class="inline-block mt-1 px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-700">Responded</span>
                        @else
                            <span class="inline-block mt-1 px-2 py-0.5 text-xs rounded-full bg-orange-100 text-orange-700">Pending</span>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400">
                <p>No feedback received yet.</p>
            </div>
        @endforelse
    </div>

    {{-- Detail + Respond Modal --}}
    @if($showDetailModal && $selectedFeedback)
        <div class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-bold text-gray-900">Feedback Details</h2>
                        <button wire:click="closeDetailModal" class="p-1 rounded-lg hover:bg-gray-100">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                <div class="p-6 space-y-4">
                    {{-- Guest Info --}}
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                        <div>
                            <div class="font-semibold text-gray-900">{{ $selectedFeedback->guest->name ?? 'Guest' }}</div>
                            <div class="text-xs text-gray-500">{{ $selectedFeedback->guest->phone_number ?? '' }}</div>
                        </div>
                    </div>

                    {{-- Rating --}}
                    <div>
                        <div class="text-sm text-gray-500 mb-1">Rating</div>
                        <div class="flex items-center gap-1">
                            @for($i = 1; $i <= 5; $i++)
                                <svg class="w-6 h-6 {{ $i <= $selectedFeedback->rating ? 'text-yellow-400' : 'text-gray-200' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            @endfor
                            <span class="ml-2 text-sm text-gray-500">{{ $selectedFeedback->rating }}/5</span>
                        </div>
                    </div>

                    {{-- Comment --}}
                    @if($selectedFeedback->comment)
                        <div>
                            <div class="text-sm text-gray-500 mb-1">Comment</div>
                            <div class="text-gray-900 bg-gray-50 rounded-lg p-3">{{ $selectedFeedback->comment }}</div>
                        </div>
                    @endif

                    {{-- Order Info --}}
                    @if($selectedFeedback->order)
                        <div class="flex gap-4 text-sm text-gray-500">
                            <span>Order: #{{ $selectedFeedback->order->order_number }}</span>
                            <span>Total: TZS {{ number_format($selectedFeedback->order->total) }}</span>
                        </div>
                    @endif

                    <div class="text-xs text-gray-400">
                        Received {{ $selectedFeedback->created_at->format('M j, Y g:i A') }} via {{ ucfirst($selectedFeedback->source) }}
                    </div>

                    <hr class="border-gray-200">

                    {{-- Staff Response --}}
                    <div>
                        <div class="text-sm font-medium text-gray-700 mb-2">Staff Response</div>
                        @if($selectedFeedback->staff_response && !$staffResponse)
                            <div class="bg-blue-50 rounded-lg p-3">
                                <p class="text-sm text-gray-900">{{ $selectedFeedback->staff_response }}</p>
                                <p class="text-xs text-gray-400 mt-2">
                                    By {{ $selectedFeedback->responder->name ?? 'Staff' }}
                                    on {{ $selectedFeedback->responded_at?->format('M j, Y g:i A') }}
                                </p>
                            </div>
                        @endif
                        <textarea wire:model="staffResponse" rows="3" class="w-full mt-2 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900" placeholder="Write a response to this feedback..."></textarea>
                        @error('staffResponse') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="p-6 border-t border-gray-200 flex justify-end gap-3">
                    <button wire:click="closeDetailModal" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">Close</button>
                    <button wire:click="respondToFeedback" class="px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 transition-colors">
                        {{ $selectedFeedback->staff_response ? 'Update Response' : 'Send Response' }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
