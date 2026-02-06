@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">Complete Your Payment</h1>

        <!-- Order Summary -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Order Summary</h2>
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span>Order ID:</span>
                    <span class="font-semibold">#{{ $order->id }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Table:</span>
                    <span>{{ $order->table->name }}</span>
                </div>
                <div class="flex justify-between text-lg font-bold border-t pt-2 mt-2">
                    <span>Total Amount:</span>
                    <span>${{ number_format($order->total_amount, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Payment Form -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">Payment Information</h2>

            <!-- Error message container -->
            <div id="error-message" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 hidden"></div>

            <!-- Payment form -->
            <form id="payment-form"
                  data-client-secret="{{ $clientSecret }}"
                  data-return-url="{{ route('payments.stripe.success') }}">
                @csrf

                <!-- Stripe Payment Element will be inserted here -->
                <div id="payment-element" class="mb-6">
                    <!-- Stripe.js injects the Payment Element here -->
                </div>

                <!-- Submit button -->
                <button
                    id="submit-payment"
                    type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition duration-200 flex items-center justify-center">
                    <div id="spinner" class="hidden">
                        <svg class="animate-spin h-5 w-5 mr-3" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    <span id="button-text">Pay ${{ number_format($order->total_amount, 2) }}</span>
                </button>
            </form>

            <!-- Test card information for development -->
            @if(config('app.env') !== 'production')
            <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded">
                <p class="text-sm font-semibold text-yellow-800 mb-2">Test Mode - Use Test Card:</p>
                <div class="text-sm text-yellow-700">
                    <p>Card Number: <code class="bg-yellow-100 px-2 py-1 rounded">4242 4242 4242 4242</code></p>
                    <p class="mt-1">Expiry: Any future date</p>
                    <p class="mt-1">CVC: Any 3 digits</p>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Stripe.js -->
<script src="https://js.stripe.com/v3/"></script>
<script>
    // Set Stripe public key from config
    window.stripePublicKey = '{{ config('stripe.public_key') }}';
    window.appName = '{{ config('app.name') }}';
</script>
@vite(['resources/js/stripe.js'])
@endpush
