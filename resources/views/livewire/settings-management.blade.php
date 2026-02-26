<div class="py-12" x-data="{ showPassword: {} }">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="flex items-center gap-2 mb-6">
                    <h2 class="text-2xl font-semibold text-gray-900">Application Settings</h2>
                    <x-help-tooltip text="Configure business information, integrations, payment methods, and notifications. Each section saves independently." position="right" />
                </div>

                {{-- Flash Messages --}}
                @if (session()->has('message'))
                    <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded">
                        {{ session('message') }}
                    </div>
                @endif

                @if (session()->has('error'))
                    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded">
                        {{ session('error') }}
                    </div>
                @endif

                {{-- Tab Navigation --}}
                <div class="border-b border-gray-200 mb-6">
                    <nav class="flex space-x-8" aria-label="Tabs">
                        @foreach ([
                            'general' => 'General',
                            'whatsapp' => 'WhatsApp',
                            'payments' => 'Payments',
                            'notifications' => 'Notifications',
                        ] as $tab => $label)
                            <button
                                wire:click="switchTab('{{ $tab }}')"
                                class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm transition-colors {{ $activeTab === $tab ? 'border-gray-900 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                            >
                                {{ $label }}
                            </button>
                        @endforeach
                    </nav>
                </div>

                {{-- ═══════════════════════════════════════════ --}}
                {{-- GENERAL TAB                                --}}
                {{-- ═══════════════════════════════════════════ --}}
                @if ($activeTab === 'general')
                    <form wire:submit.prevent="saveGeneral">
                        {{-- Business Information --}}
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Business Information</h3>
                            <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                                <div class="mb-4">
                                    <label for="business_name" class="block text-sm font-medium text-gray-900 mb-2">Business Name *</label>
                                    <input type="text" id="business_name" wire:model="business_name"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 text-gray-900">
                                    @error('business_name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <div class="mb-4">
                                    <label for="business_address" class="block text-sm font-medium text-gray-900 mb-2">Business Address *</label>
                                    <textarea id="business_address" wire:model="business_address" rows="3"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 text-gray-900"></textarea>
                                    @error('business_address') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="business_phone" class="block text-sm font-medium text-gray-900 mb-2">Phone Number</label>
                                        <input type="text" id="business_phone" wire:model="business_phone"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 text-gray-900">
                                        @error('business_phone') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label for="business_email" class="block text-sm font-medium text-gray-900 mb-2">Email Address</label>
                                        <input type="email" id="business_email" wire:model="business_email"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 text-gray-900">
                                        @error('business_email') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Tax & Pricing --}}
                        <div class="mb-8">
                            <div class="flex items-center gap-2 mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">Tax & Pricing</h3>
                                <x-help-tooltip text="Tax rate is automatically applied to all orders. Changes affect new orders immediately." position="right" />
                            </div>
                            <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                                <div>
                                    <label for="tax_rate" class="block text-sm font-medium text-gray-900 mb-2">Tax Rate (%) *</label>
                                    <input type="number" id="tax_rate" wire:model="tax_rate" step="0.01" min="0" max="100"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 text-gray-900">
                                    @error('tax_rate') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                    <p class="text-sm text-gray-500 mt-1">Enter tax rate as a percentage (e.g., 18 for 18%)</p>
                                </div>
                            </div>
                        </div>

                        {{-- Operations --}}
                        <div class="mb-8">
                            <div class="flex items-center gap-2 mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">Operations</h3>
                                <x-help-tooltip text="Set your restaurant's operating hours." position="right" />
                            </div>
                            <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="opening_hours" class="block text-sm font-medium text-gray-900 mb-2">Opening Hours</label>
                                        <input type="time" id="opening_hours" wire:model="opening_hours"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 text-gray-900">
                                    </div>
                                    <div>
                                        <label for="closing_hours" class="block text-sm font-medium text-gray-900 mb-2">Closing Hours</label>
                                        <input type="time" id="closing_hours" wire:model="closing_hours"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 text-gray-900">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="px-6 py-2 bg-gray-900 text-white rounded-md hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                                Save General Settings
                            </button>
                        </div>
                    </form>
                @endif

                {{-- ═══════════════════════════════════════════ --}}
                {{-- WHATSAPP TAB                               --}}
                {{-- ═══════════════════════════════════════════ --}}
                @if ($activeTab === 'whatsapp')
                    <form wire:submit.prevent="saveWhatsApp">
                        <div class="mb-8">
                            <div class="flex items-center gap-2 mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">WhatsApp Business API</h3>
                                <x-help-tooltip text="Configure your Meta WhatsApp Business API credentials. Get these from the Meta Developer Portal at developers.facebook.com." position="right" />
                            </div>
                            <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm space-y-4">
                                {{-- API URL --}}
                                <div>
                                    <label for="whatsapp_api_url" class="block text-sm font-medium text-gray-900 mb-2">API URL *</label>
                                    <input type="url" id="whatsapp_api_url" wire:model="whatsapp_api_url"
                                        placeholder="https://graph.facebook.com/v18.0"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 text-gray-900">
                                    @error('whatsapp_api_url') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                    <p class="text-sm text-gray-500 mt-1">Meta Graph API base URL (usually https://graph.facebook.com/v18.0)</p>
                                </div>

                                {{-- Access Token --}}
                                <div x-data="{ show: false }">
                                    <label for="whatsapp_access_token" class="block text-sm font-medium text-gray-900 mb-2">Access Token</label>
                                    <div class="relative">
                                        <input :type="show ? 'text' : 'password'" id="whatsapp_access_token" wire:model="whatsapp_access_token"
                                            placeholder="Your WhatsApp API access token"
                                            class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 text-gray-900">
                                        <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500 hover:text-gray-700">
                                            <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                            <svg x-show="show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                                        </button>
                                    </div>
                                    @error('whatsapp_access_token') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                </div>

                                {{-- Phone Number ID + Business Account ID --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="whatsapp_phone_number_id" class="block text-sm font-medium text-gray-900 mb-2">Phone Number ID</label>
                                        <input type="text" id="whatsapp_phone_number_id" wire:model="whatsapp_phone_number_id"
                                            placeholder="e.g. 123456789012345"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 text-gray-900">
                                        @error('whatsapp_phone_number_id') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                        <p class="text-sm text-gray-500 mt-1">From WhatsApp Business settings</p>
                                    </div>
                                    <div>
                                        <label for="whatsapp_business_account_id" class="block text-sm font-medium text-gray-900 mb-2">Business Account ID</label>
                                        <input type="text" id="whatsapp_business_account_id" wire:model="whatsapp_business_account_id"
                                            placeholder="e.g. 123456789012345"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 text-gray-900">
                                        @error('whatsapp_business_account_id') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                {{-- Webhook Secret --}}
                                <div x-data="{ show: false }">
                                    <label for="whatsapp_webhook_secret" class="block text-sm font-medium text-gray-900 mb-2">Webhook Verify Token</label>
                                    <div class="relative">
                                        <input :type="show ? 'text' : 'password'" id="whatsapp_webhook_secret" wire:model="whatsapp_webhook_secret"
                                            placeholder="Your webhook verification token"
                                            class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 text-gray-900">
                                        <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500 hover:text-gray-700">
                                            <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                            <svg x-show="show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                                        </button>
                                    </div>
                                    @error('whatsapp_webhook_secret') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                    <p class="text-sm text-gray-500 mt-1">The token you set when configuring the webhook in Meta Developer Portal</p>
                                </div>

                                {{-- Session Timeout --}}
                                <div>
                                    <label for="whatsapp_session_timeout" class="block text-sm font-medium text-gray-900 mb-2">Session Timeout (seconds) *</label>
                                    <input type="number" id="whatsapp_session_timeout" wire:model="whatsapp_session_timeout" min="300" max="86400" step="60"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 text-gray-900">
                                    @error('whatsapp_session_timeout') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                    <p class="text-sm text-gray-500 mt-1">How long before an inactive chatbot session resets (default: 3600 = 1 hour)</p>
                                </div>
                            </div>
                        </div>

                        {{-- AI Agent Toggle --}}
                        <div class="mb-8">
                            <div class="flex items-center gap-2 mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">AI Agent</h3>
                                <x-help-tooltip text="When enabled, an AI agent handles WhatsApp messages using natural language instead of the menu-driven flow." position="right" />
                            </div>
                            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 shadow-sm">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Enable AI Agent</p>
                                        <p class="text-sm text-gray-500 mt-1">Use AI-powered natural language conversations instead of menu-driven button flows. The AI can browse the menu, take orders, make reservations, and more.</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer ml-4">
                                        <input type="checkbox" wire:model="whatsapp_ai_enabled" class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-gray-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gray-900"></div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        {{-- Webhook Callback URL --}}
                        <div class="mb-8">
                            <div class="flex items-center gap-2 mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">Webhook Callback URL</h3>
                                <x-help-tooltip text="Use this URL in the Meta Developer Portal when configuring your WhatsApp webhook." position="right" />
                            </div>
                            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 shadow-sm">
                                <p class="text-sm text-gray-600 mb-3">Copy the URL below and paste it into your Meta Developer Portal under WhatsApp > Configuration > Webhook URL.</p>
                                <div x-data="{ copied: false }" class="flex items-center gap-2">
                                    <input type="text" readonly value="{{ $this->whatsappCallbackUrl }}"
                                        class="flex-1 px-3 py-2 border border-gray-300 rounded-md bg-white text-gray-900 font-mono text-sm select-all cursor-text">
                                    <button type="button"
                                        @click="navigator.clipboard.writeText('{{ $this->whatsappCallbackUrl }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                        class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md bg-white text-gray-700 hover:bg-gray-50 text-sm font-medium transition-colors"
                                        :class="copied && 'bg-green-50 border-green-300 text-green-700'">
                                        <svg x-show="!copied" class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                                        <svg x-show="copied" class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                        <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                                    </button>
                                </div>
                                <p class="text-xs text-gray-500 mt-2">Both <code class="bg-gray-200 px-1 rounded">GET</code> (verification) and <code class="bg-gray-200 px-1 rounded">POST</code> (messages) are handled at this URL.</p>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="px-6 py-2 bg-gray-900 text-white rounded-md hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                                Save WhatsApp Settings
                            </button>
                        </div>
                    </form>
                @endif

                {{-- ═══════════════════════════════════════════ --}}
                {{-- PAYMENTS TAB                               --}}
                {{-- ═══════════════════════════════════════════ --}}
                @if ($activeTab === 'payments')
                    <form wire:submit.prevent="savePayments">
                        {{-- ── MNO (Mobile Network Operators) ── --}}
                        <div class="mb-8">
                            <div class="flex items-center gap-2 mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">MNO Payments (Mobile Money)</h3>
                                <x-help-tooltip text="Configure Lipa Namba and QR Code for each mobile network operator. Customers can pay via USSD or by scanning a QR code." position="right" />
                            </div>

                            {{-- VodaCom (M-Pesa) --}}
                            <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm mb-4">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                                            <span class="text-red-600 font-bold text-sm">V</span>
                                        </div>
                                        <div>
                                            <label class="text-sm font-medium text-gray-900">VodaCom (M-Pesa)</label>
                                            <p class="text-sm text-gray-500">Accept payments via VodaCom M-Pesa</p>
                                        </div>
                                    </div>
                                    <button type="button" wire:click="$toggle('vodacom_enabled')"
                                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $vodacom_enabled ? 'bg-gray-900' : 'bg-gray-300' }}">
                                        <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $vodacom_enabled ? 'translate-x-6' : 'translate-x-1' }}"></span>
                                    </button>
                                </div>
                                @if ($vodacom_enabled)
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="vodacom_lipa_namba" class="block text-sm font-medium text-gray-900 mb-2">Lipa Namba</label>
                                        <input type="text" id="vodacom_lipa_namba" wire:model="vodacom_lipa_namba"
                                            placeholder="e.g. 5123456"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 text-gray-900">
                                        @error('vodacom_lipa_namba') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                        <p class="text-sm text-gray-500 mt-1">Business/Till number for M-Pesa payments</p>
                                    </div>
                                    <div>
                                        <label for="vodacom_qr_code" class="block text-sm font-medium text-gray-900 mb-2">QR Code Data</label>
                                        <input type="text" id="vodacom_qr_code" wire:model="vodacom_qr_code"
                                            placeholder="QR code content or URL"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 text-gray-900">
                                        @error('vodacom_qr_code') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                        <p class="text-sm text-gray-500 mt-1">Content encoded in the M-Pesa QR code</p>
                                    </div>
                                </div>
                                @endif
                            </div>

                            {{-- Yas --}}
                            <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm mb-4">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                            <span class="text-blue-600 font-bold text-sm">Y</span>
                                        </div>
                                        <div>
                                            <label class="text-sm font-medium text-gray-900">Yas</label>
                                            <p class="text-sm text-gray-500">Accept payments via Yas mobile money</p>
                                        </div>
                                    </div>
                                    <button type="button" wire:click="$toggle('yas_enabled')"
                                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $yas_enabled ? 'bg-gray-900' : 'bg-gray-300' }}">
                                        <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $yas_enabled ? 'translate-x-6' : 'translate-x-1' }}"></span>
                                    </button>
                                </div>
                                @if ($yas_enabled)
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="yas_lipa_namba" class="block text-sm font-medium text-gray-900 mb-2">Lipa Namba</label>
                                        <input type="text" id="yas_lipa_namba" wire:model="yas_lipa_namba"
                                            placeholder="e.g. 5123456"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 text-gray-900">
                                        @error('yas_lipa_namba') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                        <p class="text-sm text-gray-500 mt-1">Business/Till number for Yas payments</p>
                                    </div>
                                    <div>
                                        <label for="yas_qr_code" class="block text-sm font-medium text-gray-900 mb-2">QR Code Data</label>
                                        <input type="text" id="yas_qr_code" wire:model="yas_qr_code"
                                            placeholder="QR code content or URL"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 text-gray-900">
                                        @error('yas_qr_code') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                        <p class="text-sm text-gray-500 mt-1">Content encoded in the Yas QR code</p>
                                    </div>
                                </div>
                                @endif
                            </div>

                            {{-- AirTel --}}
                            <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                                            <span class="text-orange-600 font-bold text-sm">A</span>
                                        </div>
                                        <div>
                                            <label class="text-sm font-medium text-gray-900">AirTel Money</label>
                                            <p class="text-sm text-gray-500">Accept payments via AirTel Money</p>
                                        </div>
                                    </div>
                                    <button type="button" wire:click="$toggle('airtel_enabled')"
                                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $airtel_enabled ? 'bg-gray-900' : 'bg-gray-300' }}">
                                        <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $airtel_enabled ? 'translate-x-6' : 'translate-x-1' }}"></span>
                                    </button>
                                </div>
                                @if ($airtel_enabled)
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="airtel_lipa_namba" class="block text-sm font-medium text-gray-900 mb-2">Lipa Namba</label>
                                        <input type="text" id="airtel_lipa_namba" wire:model="airtel_lipa_namba"
                                            placeholder="e.g. 5123456"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 text-gray-900">
                                        @error('airtel_lipa_namba') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                        <p class="text-sm text-gray-500 mt-1">Business/Till number for AirTel payments</p>
                                    </div>
                                    <div>
                                        <label for="airtel_qr_code" class="block text-sm font-medium text-gray-900 mb-2">QR Code Data</label>
                                        <input type="text" id="airtel_qr_code" wire:model="airtel_qr_code"
                                            placeholder="QR code content or URL"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 text-gray-900">
                                        @error('airtel_qr_code') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                        <p class="text-sm text-gray-500 mt-1">Content encoded in the AirTel QR code</p>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>

                        {{-- ── Bank Payment ── --}}
                        <div class="mb-8">
                            <div class="flex items-center gap-2 mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">Bank Payment</h3>
                                <x-help-tooltip text="Configure bank Lipa Namba and QR Code for direct bank transfers." position="right" />
                            </div>
                            <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm space-y-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                        </div>
                                        <div>
                                            <label class="text-sm font-medium text-gray-900">Enable Bank Payment</label>
                                            <p class="text-sm text-gray-500">Accept payments via bank transfer</p>
                                        </div>
                                    </div>
                                    <button type="button" wire:click="$toggle('bank_enabled')"
                                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $bank_enabled ? 'bg-gray-900' : 'bg-gray-300' }}">
                                        <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $bank_enabled ? 'translate-x-6' : 'translate-x-1' }}"></span>
                                    </button>
                                </div>

                                @if ($bank_enabled)
                                {{-- Bank Details --}}
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label for="bank_name" class="block text-sm font-medium text-gray-900 mb-2">Bank Name</label>
                                        <input type="text" id="bank_name" wire:model="bank_name"
                                            placeholder="e.g. CRDB Bank"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 text-gray-900">
                                        @error('bank_name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label for="bank_account_name" class="block text-sm font-medium text-gray-900 mb-2">Account Name</label>
                                        <input type="text" id="bank_account_name" wire:model="bank_account_name"
                                            placeholder="e.g. My Restaurant Ltd"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 text-gray-900">
                                        @error('bank_account_name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label for="bank_account_number" class="block text-sm font-medium text-gray-900 mb-2">Account Number</label>
                                        <input type="text" id="bank_account_number" wire:model="bank_account_number"
                                            placeholder="e.g. 0150123456789"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 text-gray-900">
                                        @error('bank_account_number') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                {{-- Lipa Namba + QR Code --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="bank_lipa_namba" class="block text-sm font-medium text-gray-900 mb-2">Lipa Namba</label>
                                        <input type="text" id="bank_lipa_namba" wire:model="bank_lipa_namba"
                                            placeholder="Bank Lipa Namba / Pay Bill"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 text-gray-900">
                                        @error('bank_lipa_namba') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                        <p class="text-sm text-gray-500 mt-1">Bank Lipa Namba for direct payment</p>
                                    </div>
                                    <div>
                                        <label for="bank_qr_code" class="block text-sm font-medium text-gray-900 mb-2">QR Code Data</label>
                                        <input type="text" id="bank_qr_code" wire:model="bank_qr_code"
                                            placeholder="QR code content or URL"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 text-gray-900">
                                        @error('bank_qr_code') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                        <p class="text-sm text-gray-500 mt-1">Content encoded in the bank payment QR code</p>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>

                        {{-- ── Card Processor (VISA / MasterCard) ── --}}
                        <div class="mb-8">
                            <div class="flex items-center gap-2 mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">Card Processor (VISA / MasterCard)</h3>
                                <x-help-tooltip text="Configure your card payment aggregator to accept VISA and MasterCard payments." position="right" />
                            </div>
                            <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm space-y-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                                        </div>
                                        <div>
                                            <label class="text-sm font-medium text-gray-900">Enable Card Payments</label>
                                            <p class="text-sm text-gray-500">Accept VISA and MasterCard via aggregator</p>
                                        </div>
                                    </div>
                                    <button type="button" wire:click="$toggle('card_enabled')"
                                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $card_enabled ? 'bg-gray-900' : 'bg-gray-300' }}">
                                        <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $card_enabled ? 'translate-x-6' : 'translate-x-1' }}"></span>
                                    </button>
                                </div>

                                @if ($card_enabled)
                                {{-- Processor Bank --}}
                                <div>
                                    <label for="card_processor_name" class="block text-sm font-medium text-gray-900 mb-2">Processor Bank</label>
                                    <select id="card_processor_name" wire:model="card_processor_name"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 text-gray-900">
                                        <option value="">Select bank...</option>
                                        <option value="NBC">NBC Bank</option>
                                        <option value="TCB">TCB Bank</option>
                                    </select>
                                    @error('card_processor_name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                </div>

                                {{-- Merchant ID + API Key --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="card_merchant_id" class="block text-sm font-medium text-gray-900 mb-2">Merchant ID</label>
                                        <input type="text" id="card_merchant_id" wire:model="card_merchant_id"
                                            placeholder="Your merchant ID"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 text-gray-900">
                                        @error('card_merchant_id') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    <div x-data="{ show: false }">
                                        <label for="card_api_key" class="block text-sm font-medium text-gray-900 mb-2">API Key</label>
                                        <div class="relative">
                                            <input :type="show ? 'text' : 'password'" id="card_api_key" wire:model="card_api_key"
                                                placeholder="Your API key"
                                                class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 text-gray-900">
                                            <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500 hover:text-gray-700">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                            </button>
                                        </div>
                                        @error('card_api_key') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                {{-- API Secret --}}
                                <div x-data="{ show: false }">
                                    <label for="card_api_secret" class="block text-sm font-medium text-gray-900 mb-2">API Secret</label>
                                    <div class="relative">
                                        <input :type="show ? 'text' : 'password'" id="card_api_secret" wire:model="card_api_secret"
                                            placeholder="Your API secret"
                                            class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 text-gray-900">
                                        <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500 hover:text-gray-700">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                        </button>
                                    </div>
                                    @error('card_api_secret') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                </div>
                                @endif
                            </div>
                        </div>

                        {{-- ── Zima PayByLink ── --}}
                        <div class="mb-8">
                            <div class="flex items-center gap-2 mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">Zima PayByLink</h3>
                                <x-help-tooltip text="Configure Zima PayByLink to generate payment links that can be sent to customers via WhatsApp or SMS." position="right" />
                            </div>
                            <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm space-y-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                            <span class="text-purple-600 font-bold text-sm">Z</span>
                                        </div>
                                        <div>
                                            <label class="text-sm font-medium text-gray-900">Enable Zima PayByLink</label>
                                            <p class="text-sm text-gray-500">Generate payment links for customers</p>
                                        </div>
                                    </div>
                                    <button type="button" wire:click="$toggle('zima_enabled')"
                                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $zima_enabled ? 'bg-gray-900' : 'bg-gray-300' }}">
                                        <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $zima_enabled ? 'translate-x-6' : 'translate-x-1' }}"></span>
                                    </button>
                                </div>

                                @if ($zima_enabled)
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="zima_merchant_id" class="block text-sm font-medium text-gray-900 mb-2">Merchant ID</label>
                                        <input type="text" id="zima_merchant_id" wire:model="zima_merchant_id"
                                            placeholder="Your Zima Merchant ID"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 text-gray-900">
                                        @error('zima_merchant_id') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    <div x-data="{ show: false }">
                                        <label for="zima_api_key" class="block text-sm font-medium text-gray-900 mb-2">API Key</label>
                                        <div class="relative">
                                            <input :type="show ? 'text' : 'password'" id="zima_api_key" wire:model="zima_api_key"
                                                placeholder="Your Zima API key"
                                                class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 text-gray-900">
                                            <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500 hover:text-gray-700">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                            </button>
                                        </div>
                                        @error('zima_api_key') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                {{-- Callback URL --}}
                                <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                                    <label class="block text-sm font-medium text-gray-900 mb-2">Callback URL</label>
                                    <div x-data="{ copied: false }" class="flex items-center gap-2">
                                        <input type="text" readonly value="{{ $this->zimaCallbackUrl }}"
                                            class="flex-1 px-3 py-2 border border-gray-300 rounded-md bg-white text-gray-900 font-mono text-sm select-all cursor-text">
                                        <button type="button"
                                            @click="navigator.clipboard.writeText('{{ $this->zimaCallbackUrl }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                            class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md bg-white text-gray-700 hover:bg-gray-50 text-sm font-medium transition-colors"
                                            :class="copied && 'bg-green-50 border-green-300 text-green-700'">
                                            <svg x-show="!copied" class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                                            <svg x-show="copied" class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                            <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                                        </button>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-2">Configure this URL in your Zima merchant dashboard for payment notifications</p>
                                </div>
                                @endif
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="px-6 py-2 bg-gray-900 text-white rounded-md hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                                Save Payment Settings
                            </button>
                        </div>
                    </form>
                @endif

                {{-- ═══════════════════════════════════════════ --}}
                {{-- NOTIFICATIONS TAB                          --}}
                {{-- ═══════════════════════════════════════════ --}}
                @if ($activeTab === 'notifications')
                    <form wire:submit.prevent="saveNotifications">
                        <div class="mb-8">
                            <div class="flex items-center gap-2 mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">Firebase Cloud Messaging (FCM)</h3>
                                <x-help-tooltip text="Configure Firebase for push notifications to staff devices. Download your service account JSON from the Firebase Console." position="right" />
                            </div>
                            <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm space-y-4">
                                {{-- Project ID --}}
                                <div>
                                    <label for="firebase_project_id" class="block text-sm font-medium text-gray-900 mb-2">Firebase Project ID</label>
                                    <input type="text" id="firebase_project_id" wire:model="firebase_project_id"
                                        placeholder="my-restaurant-app"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 text-gray-900">
                                    @error('firebase_project_id') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                </div>

                                {{-- Service Account JSON --}}
                                <div>
                                    <label for="firebase_credentials_json" class="block text-sm font-medium text-gray-900 mb-2">Service Account JSON</label>
                                    <textarea id="firebase_credentials_json" wire:model="firebase_credentials_json" rows="8"
                                        placeholder='Paste your Firebase service account JSON here...'
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 text-gray-900 font-mono text-sm"></textarea>
                                    @error('firebase_credentials_json') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                    <p class="text-sm text-gray-500 mt-1">Download from Firebase Console > Project Settings > Service Accounts > Generate New Private Key</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="px-6 py-2 bg-gray-900 text-white rounded-md hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                                Save Notification Settings
                            </button>
                        </div>
                    </form>
                @endif

            </div>
        </div>
    </div>
</div>
