@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <!-- Payment status message will be displayed here -->
        <div id="payment-message" class="mb-6"></div>

        <!-- Success state -->
        <div id="success-state" class="bg-white rounded-lg shadow-md p-8 text-center hidden">
            <div class="mb-4">
                <svg class="mx-auto h-16 w-16 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Payment Successful!</h1>
            <p class="text-gray-600 mb-6">Thank you for your payment. Your transaction has been completed.</p>

            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <div class="text-sm text-gray-600 space-y-2">
                    <div class="flex justify-between">
                        <span>Order ID:</span>
                        <span class="font-semibold" id="order-id"></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Transaction ID:</span>
                        <span class="font-semibold" id="transaction-id"></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Amount Paid:</span>
                        <span class="font-semibold" id="amount-paid"></span>
                    </div>
                </div>
            </div>

            <div class="flex gap-4 justify-center">
                <a href="{{ route('dashboard') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg transition duration-200">
                    Back to Dashboard
                </a>
                <a href="#" onclick="window.print()" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-6 rounded-lg transition duration-200">
                    Print Receipt
                </a>
            </div>
        </div>

        <!-- Processing state -->
        <div id="processing-state" class="bg-white rounded-lg shadow-md p-8 text-center hidden">
            <div class="mb-4">
                <svg class="animate-spin mx-auto h-16 w-16 text-blue-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Processing Payment...</h1>
            <p class="text-gray-600">Your payment is being processed. Please wait.</p>
        </div>

        <!-- Error state -->
        <div id="error-state" class="bg-white rounded-lg shadow-md p-8 text-center hidden">
            <div class="mb-4">
                <svg class="mx-auto h-16 w-16 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Payment Failed</h1>
            <p class="text-gray-600 mb-6" id="error-text">An error occurred while processing your payment.</p>

            <a href="{{ url()->previous() }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg transition duration-200">
                Try Again
            </a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Stripe.js -->
<script src="https://js.stripe.com/v3/"></script>
<script>
    window.stripePublicKey = '{{ config('services.stripe.public_key') }}';

    // Handle payment return and display appropriate status
    document.addEventListener('DOMContentLoaded', async () => {
        const urlParams = new URLSearchParams(window.location.search);
        const paymentIntentClientSecret = urlParams.get('payment_intent_client_secret');
        const paymentIntentId = urlParams.get('payment_intent');

        if (!paymentIntentClientSecret) {
            // No payment intent in URL, redirect to dashboard
            window.location.href = '{{ route('dashboard') }}';
            return;
        }

        // Initialize Stripe
        const stripe = Stripe(window.stripePublicKey);

        // Retrieve the PaymentIntent
        const { error, paymentIntent } = await stripe.retrievePaymentIntent(paymentIntentClientSecret);

        if (error) {
            showError(error.message);
            return;
        }

        // Handle different payment statuses
        switch (paymentIntent.status) {
            case 'succeeded':
                showSuccess(paymentIntent);
                // Optionally notify backend to confirm payment
                notifyBackend(paymentIntentId);
                break;

            case 'processing':
                showProcessing();
                // Poll for status update
                pollPaymentStatus(paymentIntentId);
                break;

            case 'requires_payment_method':
                showError('Your payment was not successful. Please try again.');
                break;

            default:
                showError('Something went wrong with your payment.');
        }
    });

    function showSuccess(paymentIntent) {
        document.getElementById('success-state').classList.remove('hidden');
        document.getElementById('processing-state').classList.add('hidden');
        document.getElementById('error-state').classList.add('hidden');

        // Populate payment details
        document.getElementById('transaction-id').textContent = paymentIntent.id;
        document.getElementById('amount-paid').textContent = '$' + (paymentIntent.amount / 100).toFixed(2);

        // Get order ID from metadata if available
        if (paymentIntent.metadata && paymentIntent.metadata.order_id) {
            document.getElementById('order-id').textContent = '#' + paymentIntent.metadata.order_id;
        }
    }

    function showProcessing() {
        document.getElementById('processing-state').classList.remove('hidden');
        document.getElementById('success-state').classList.add('hidden');
        document.getElementById('error-state').classList.add('hidden');
    }

    function showError(message) {
        document.getElementById('error-state').classList.remove('hidden');
        document.getElementById('success-state').classList.add('hidden');
        document.getElementById('processing-state').classList.add('hidden');
        document.getElementById('error-text').textContent = message;
    }

    // Notify backend of successful payment
    async function notifyBackend(paymentIntentId) {
        try {
            const response = await fetch('/api/payments/stripe/confirm', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    payment_intent_id: paymentIntentId,
                }),
            });

            const data = await response.json();
            console.log('Backend notified:', data);
        } catch (error) {
            console.error('Error notifying backend:', error);
        }
    }

    // Poll for payment status updates
    function pollPaymentStatus(paymentIntentId) {
        const interval = setInterval(async () => {
            const stripe = Stripe(window.stripePublicKey);
            const { paymentIntent } = await stripe.retrievePaymentIntent(
                new URLSearchParams(window.location.search).get('payment_intent_client_secret')
            );

            if (paymentIntent.status === 'succeeded') {
                clearInterval(interval);
                showSuccess(paymentIntent);
                notifyBackend(paymentIntentId);
            } else if (paymentIntent.status === 'requires_payment_method') {
                clearInterval(interval);
                showError('Your payment was not successful. Please try again.');
            }
        }, 2000); // Poll every 2 seconds

        // Stop polling after 2 minutes
        setTimeout(() => clearInterval(interval), 120000);
    }
</script>
@endpush
