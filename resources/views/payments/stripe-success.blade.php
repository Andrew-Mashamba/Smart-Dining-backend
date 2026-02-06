@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <!-- Payment Status Message -->
        <div id="payment-message" class="mb-6"></div>

        <!-- Success Card (default) -->
        <div id="success-card" class="bg-white rounded-lg shadow-md p-8 text-center">
            <div class="mb-4">
                <svg class="w-16 h-16 mx-auto text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-4">Payment Successful!</h1>
            <p class="text-gray-600 mb-6">Your payment has been processed successfully.</p>

            <div class="space-y-4">
                <a href="{{ route('orders.index') }}" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition duration-200">
                    View Orders
                </a>
                <a href="{{ route('home') }}" class="inline-block ml-4 bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-3 px-6 rounded-lg transition duration-200">
                    Return Home
                </a>
            </div>
        </div>

        <!-- Processing Card (hidden by default) -->
        <div id="processing-card" class="bg-white rounded-lg shadow-md p-8 text-center hidden">
            <div class="mb-4">
                <svg class="animate-spin w-16 h-16 mx-auto text-blue-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-4">Processing Payment</h1>
            <p class="text-gray-600 mb-6">Your payment is being processed. Please wait...</p>
        </div>

        <!-- Error Card (hidden by default) -->
        <div id="error-card" class="bg-white rounded-lg shadow-md p-8 text-center hidden">
            <div class="mb-4">
                <svg class="w-16 h-16 mx-auto text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-4">Payment Failed</h1>
            <p class="text-gray-600 mb-6" id="error-message-text">There was an issue processing your payment. Please try again.</p>

            <div class="space-y-4">
                <a href="{{ url()->previous() }}" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition duration-200">
                    Try Again
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Stripe.js -->
<script src="https://js.stripe.com/v3/"></script>
<script>
    window.stripePublicKey = '{{ config('services.stripe.public_key') }}';
</script>
<script type="module">
    import { handlePaymentReturn } from '@/stripe.js';

    // Check payment status on page load
    document.addEventListener('DOMContentLoaded', async () => {
        const result = await handlePaymentReturn();

        if (result) {
            const successCard = document.getElementById('success-card');
            const processingCard = document.getElementById('processing-card');
            const errorCard = document.getElementById('error-card');

            // Hide all cards first
            successCard.classList.add('hidden');
            processingCard.classList.add('hidden');
            errorCard.classList.add('hidden');

            // Show appropriate card based on status
            if (result.status === 'succeeded') {
                successCard.classList.remove('hidden');
            } else if (result.status === 'processing') {
                processingCard.classList.remove('hidden');
            } else {
                errorCard.classList.remove('hidden');
                document.getElementById('error-message-text').textContent = result.message;
            }
        }
    });
</script>
@endpush
