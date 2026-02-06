<div class="min-h-screen flex flex-col justify-center items-center px-4 py-12 bg-gradient-to-b from-gray-50 to-gray-100">
    <!-- Logo Section -->
    <div class="mb-8">
        {{ $logo }}
    </div>

    <!-- Card -->
    <div class="w-full sm:max-w-md">
        <div class="bg-white shadow-xl rounded-2xl overflow-hidden">
            {{ $slot }}
        </div>

        <!-- Footer -->
        <p class="text-center text-xs text-gray-400 mt-6">
            &copy; {{ date('Y') }} SeaCliff POS. All rights reserved.
        </p>
    </div>
</div>
