<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="flex items-center gap-2 mb-6">
                    <h2 class="text-2xl font-semibold text-gray-900">Application Settings</h2>
                    <x-help-tooltip text="Configure business information, tax rates, payment methods, and other system settings. Changes are saved immediately and affect all operations." position="right" />
                </div>

                @if (session()->has('message'))
                    <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded">
                        {{ session('message') }}
                    </div>
                @endif

                <form wire:submit.prevent="save">
                    <!-- Business Information -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Business Information</h3>
                        <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                            <div class="mb-4">
                                <label for="business_name" class="block text-sm font-medium text-gray-900 mb-2">
                                    Business Name *
                                </label>
                                <input type="text" id="business_name" wire:model="business_name"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 text-gray-900">
                                @error('business_name')
                                    <span class="text-red-600 text-sm">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="business_address" class="block text-sm font-medium text-gray-900 mb-2">
                                    Business Address *
                                </label>
                                <textarea id="business_address" wire:model="business_address" rows="3"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 text-gray-900"></textarea>
                                @error('business_address')
                                    <span class="text-red-600 text-sm">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="business_phone" class="block text-sm font-medium text-gray-900 mb-2">
                                    Phone Number
                                </label>
                                <input type="text" id="business_phone" wire:model="business_phone"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 text-gray-900">
                                @error('business_phone')
                                    <span class="text-red-600 text-sm">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="mb-0">
                                <label for="business_email" class="block text-sm font-medium text-gray-900 mb-2">
                                    Email Address
                                </label>
                                <input type="email" id="business_email" wire:model="business_email"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 text-gray-900">
                                @error('business_email')
                                    <span class="text-red-600 text-sm">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Tax & Pricing -->
                    <div class="mb-8">
                        <div class="flex items-center gap-2 mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Tax & Pricing</h3>
                            <x-help-tooltip text="Tax rate is automatically applied to all orders. Changes affect new orders immediately." position="right" />
                        </div>
                        <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                            <div class="mb-0">
                                <div class="flex items-center gap-2 mb-2">
                                    <label for="tax_rate" class="block text-sm font-medium text-gray-900">
                                        Tax Rate (%) *
                                    </label>
                                    <x-help-tooltip text="Enter the tax rate as a decimal percentage (e.g., 8.5 for 8.5%). This will be applied to all menu items." position="right" />
                                </div>
                                <input type="number" id="tax_rate" wire:model="tax_rate" step="0.01" min="0" max="100"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 text-gray-900">
                                @error('tax_rate')
                                    <span class="text-red-600 text-sm">{{ $message }}</span>
                                @enderror
                                <p class="text-sm text-gray-600 mt-1">Enter tax rate as a percentage (e.g., 8.5 for 8.5%)</p>
                            </div>
                        </div>
                    </div>

                    <!-- Operations -->
                    <div class="mb-8">
                        <div class="flex items-center gap-2 mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Operations</h3>
                            <x-help-tooltip text="Set your restaurant's operating hours. These times are displayed to guests and used for reporting." position="right" />
                        </div>
                        <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="opening_hours" class="block text-sm font-medium text-gray-900 mb-2">
                                        Opening Hours
                                    </label>
                                    <input type="time" id="opening_hours" wire:model="opening_hours"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 text-gray-900">
                                    @error('opening_hours')
                                        <span class="text-red-600 text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <label for="closing_hours" class="block text-sm font-medium text-gray-900 mb-2">
                                        Closing Hours
                                    </label>
                                    <input type="time" id="closing_hours" wire:model="closing_hours"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 text-gray-900">
                                    @error('closing_hours')
                                        <span class="text-red-600 text-sm">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Save Button -->
                    <div class="flex justify-end">
                        <button type="submit"
                            class="px-6 py-2 bg-gray-900 text-white rounded-md hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                            Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
