<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guest Order - {{ $table->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8">
            <div class="text-center mb-6">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Welcome!</h1>
                <p class="text-lg text-gray-600">{{ $table->name }}</p>
                <p class="text-sm text-gray-500">{{ $table->location }}</p>
            </div>

            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <p class="text-green-800 text-center">
                    <svg class="w-12 h-12 mx-auto mb-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    QR Code Scanned Successfully!
                </p>
            </div>

            <div class="text-center text-gray-600 mb-6">
                <p class="mb-2">Session Token:</p>
                <p class="font-mono text-xs bg-gray-100 p-2 rounded break-all">{{ $session->session_token }}</p>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 text-center">
                <svg class="w-16 h-16 mx-auto mb-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h2 class="text-xl font-bold text-gray-900 mb-2">Guest Ordering Coming Soon!</h2>
                <p class="text-gray-700 mb-4">
                    The guest self-service ordering feature is currently under development.
                </p>
                <p class="text-sm text-gray-600">
                    For now, please use the QR code to let our staff know you're ready to order, or call a waiter for assistance.
                </p>
            </div>

            <div class="mt-6 text-center">
                <p class="text-sm text-gray-500">
                    Session started: {{ $session->started_at->format('M d, Y H:i A') }}
                </p>
            </div>
        </div>
    </div>
</body>
</html>
