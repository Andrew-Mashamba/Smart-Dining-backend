<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Bar Display System' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-100" x-data="{ fullscreen: false }">
    <div class="min-h-screen">
        <!-- Simple Header for Bar Display -->
        <header class="bg-white shadow-sm border-b border-gray-200" x-show="!fullscreen">
            <div class="px-4 py-3 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Bar Display System</h1>
                    <p class="text-sm text-gray-600">Real-time drink order management</p>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-sm text-gray-600">
                        {{ now()->format('l, M d, Y - H:i') }}
                    </div>
                    <button
                        @click="fullscreen = !fullscreen"
                        class="px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 transition-colors"
                    >
                        <span x-show="!fullscreen">Enter Fullscreen</span>
                        <span x-show="fullscreen">Exit Fullscreen</span>
                    </button>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-gray-200 text-gray-900 rounded-lg hover:bg-gray-300 transition-colors">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <!-- Main Content (Fullscreen capable) -->
        <main :class="fullscreen ? 'fixed inset-0 z-50 bg-gray-100 overflow-auto' : 'p-6'">
            <!-- Fullscreen Header -->
            <div x-show="fullscreen" class="bg-white shadow-sm border-b border-gray-200 px-6 py-3 flex items-center justify-between mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Bar Display</h1>
                <div class="flex items-center space-x-4">
                    <div class="text-sm text-gray-600">
                        {{ now()->format('H:i') }}
                    </div>
                    <button
                        @click="fullscreen = false"
                        class="px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 transition-colors"
                    >
                        Exit Fullscreen
                    </button>
                </div>
            </div>

            <!-- Flash Messages -->
            @if (session('success') || session('message'))
            <div class="mb-6 bg-white border border-gray-300 text-gray-900 px-4 py-3 rounded-xl shadow-sm">
                {{ session('success') ?? session('message') }}
            </div>
            @endif

            @if (session('error'))
            <div class="mb-6 bg-white border border-gray-300 text-gray-900 px-4 py-3 rounded-xl shadow-sm">
                {{ session('error') }}
            </div>
            @endif

            <!-- Page Content -->
            {{ $slot }}
        </main>
    </div>

    @livewireScripts
</body>
</html>
