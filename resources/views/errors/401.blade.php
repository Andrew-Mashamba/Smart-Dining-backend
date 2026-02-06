<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>401 - Unauthorized</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center px-4">
    <div class="bg-white rounded-lg shadow-lg p-8 max-w-md w-full text-center">
        <!-- Error Code -->
        <div class="text-gray-900 text-6xl font-bold mb-4">
            401
        </div>

        <!-- Error Message -->
        <h1 class="text-gray-600 text-2xl font-semibold mb-4">
            Unauthorized
        </h1>

        <p class="text-gray-600 mb-8">
            You need to be authenticated to access this resource. Please log in to continue.
        </p>

        <!-- Action Buttons -->
        <div class="space-y-3">
            <a href="{{ route('login') }}" class="block w-full bg-gray-900 text-white font-medium py-3 px-6 rounded-lg hover:bg-gray-800 transition-colors">
                Go to Login
            </a>
            <a href="{{ url('/') }}" class="block w-full bg-white text-gray-900 font-medium py-3 px-6 rounded-lg border-2 border-gray-900 hover:bg-gray-50 transition-colors">
                Go to Homepage
            </a>
        </div>
    </div>
</body>
</html>
