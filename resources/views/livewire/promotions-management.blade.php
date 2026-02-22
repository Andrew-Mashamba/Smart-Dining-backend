<div class="p-6">
    {{-- Flash Messages --}}
    @if(session()->has('success'))
        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800">{{ session('success') }}</div>
    @endif
    @if(session()->has('error'))
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800">{{ session('error') }}</div>
    @endif

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Promotions</h1>
            <p class="text-sm text-gray-500 mt-1">Manage specials, happy hours, and discounts</p>
        </div>
        <button wire:click="addPromotion" class="px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 transition-colors">
            Add Promotion
        </button>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</div>
            <div class="text-sm text-gray-500">Total Promotions</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="text-2xl font-bold text-green-600">{{ $stats['active'] }}</div>
            <div class="text-sm text-gray-500">Active Now</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="text-2xl font-bold text-purple-600">{{ $stats['happy_hours'] }}</div>
            <div class="text-sm text-gray-500">Happy Hours</div>
        </div>
    </div>

    {{-- Search & Filters --}}
    <div class="flex gap-3 mb-6">
        <div class="flex-1">
            <input type="text" wire:model.live="search" placeholder="Search promotions..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-gray-900">
        </div>
        <select wire:model.live="filterType" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900">
            <option value="">All Types</option>
            <option value="daily_special">Daily Special</option>
            <option value="happy_hour">Happy Hour</option>
            <option value="discount">Discount</option>
            <option value="combo">Combo</option>
            <option value="general">General</option>
        </select>
        <select wire:model.live="filterStatus" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900">
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="expired">Expired</option>
        </select>
    </div>

    {{-- Promotions List --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        @forelse($promotions as $promo)
            <div class="flex items-center justify-between p-4 border-b border-gray-100 hover:bg-gray-50 transition-colors">
                <div class="flex-1">
                    <div class="flex items-center gap-3">
                        <h3 class="font-semibold text-gray-900">{{ $promo->title }}</h3>
                        @php $status = $promo->status; @endphp
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium
                            {{ $status === 'Active' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $status === 'Scheduled' ? 'bg-blue-100 text-blue-800' : '' }}
                            {{ $status === 'Expired' ? 'bg-gray-100 text-gray-600' : '' }}
                            {{ $status === 'Inactive' ? 'bg-red-100 text-red-700' : '' }}
                        ">{{ $status }}</span>
                        <span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600">{{ str_replace('_', ' ', ucfirst($promo->type)) }}</span>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ $promo->description ?? 'No description' }}
                        @if($promo->discount_value)
                            &mdash; {{ $promo->discount_type === 'percentage' ? $promo->discount_value . '%' : 'TZS ' . number_format($promo->discount_value) }} off
                        @endif
                    </p>
                    <div class="flex gap-4 mt-1 text-xs text-gray-400">
                        @if($promo->day_of_week)
                            <span>{{ ucfirst($promo->day_of_week) }}s</span>
                        @endif
                        @if($promo->start_time && $promo->end_time)
                            <span>{{ substr($promo->start_time, 0, 5) }} - {{ substr($promo->end_time, 0, 5) }}</span>
                        @endif
                        @if($promo->start_date || $promo->end_date)
                            <span>{{ $promo->start_date ? $promo->start_date->format('M j') : 'Start' }} - {{ $promo->end_date ? $promo->end_date->format('M j, Y') : 'Ongoing' }}</span>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button wire:click="toggleActive({{ $promo->id }})" class="p-2 rounded-lg hover:bg-gray-100 transition-colors" title="Toggle Active">
                        @if($promo->is_active)
                            <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        @else
                            <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                        @endif
                    </button>
                    <button wire:click="editPromotion({{ $promo->id }})" class="p-2 rounded-lg hover:bg-gray-100 transition-colors" title="Edit">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button wire:click="confirmDelete({{ $promo->id }})" class="p-2 rounded-lg hover:bg-red-50 transition-colors" title="Delete">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </div>
        @empty
            <div class="p-8 text-center text-gray-400">
                <p>No promotions found.</p>
            </div>
        @endforelse
    </div>

    {{-- Add/Edit Modal --}}
    @if($showModal)
        <div class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-bold text-gray-900">{{ $editMode ? 'Edit Promotion' : 'Add Promotion' }}</h2>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                        <input type="text" wire:model="title" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900" placeholder="e.g., Happy Hour Drinks">
                        @error('title') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea wire:model="description" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900" placeholder="Describe the promotion..."></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                            <select wire:model="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900">
                                <option value="general">General</option>
                                <option value="daily_special">Daily Special</option>
                                <option value="happy_hour">Happy Hour</option>
                                <option value="discount">Discount</option>
                                <option value="combo">Combo Deal</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Day of Week</label>
                            <select wire:model="day_of_week" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900">
                                <option value="">Every Day</option>
                                <option value="monday">Monday</option>
                                <option value="tuesday">Tuesday</option>
                                <option value="wednesday">Wednesday</option>
                                <option value="thursday">Thursday</option>
                                <option value="friday">Friday</option>
                                <option value="saturday">Saturday</option>
                                <option value="sunday">Sunday</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Discount Type</label>
                            <select wire:model="discount_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900">
                                <option value="">None</option>
                                <option value="percentage">Percentage (%)</option>
                                <option value="fixed_amount">Fixed Amount (TZS)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Discount Value</label>
                            <input type="number" step="0.01" wire:model="discount_value" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900" placeholder="0">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Start Time</label>
                            <input type="time" wire:model="start_time" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">End Time</label>
                            <input type="time" wire:model="end_time" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                            <input type="date" wire:model="start_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                            <input type="date" wire:model="end_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900">
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" wire:model="is_active" id="is_active" class="rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                        <label for="is_active" class="text-sm text-gray-700">Active</label>
                    </div>
                </div>
                <div class="p-6 border-t border-gray-200 flex justify-end gap-3">
                    <button wire:click="closeModal" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">Cancel</button>
                    <button wire:click="savePromotion" class="px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 transition-colors">
                        {{ $editMode ? 'Update' : 'Create' }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    @if($showDeleteModal)
        <div class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm">
                <div class="p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Delete Promotion</h3>
                    <p class="text-sm text-gray-500">Are you sure? This action cannot be undone.</p>
                </div>
                <div class="p-6 border-t border-gray-200 flex justify-end gap-3">
                    <button wire:click="$set('showDeleteModal', false)" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                    <button wire:click="deletePromotion" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Delete</button>
                </div>
            </div>
        </div>
    @endif
</div>
