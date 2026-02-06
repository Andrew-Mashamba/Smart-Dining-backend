<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Payment - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://js.stripe.com/v3/"></script>
    <style>
        #error-message {
            display: none;
            color: #dc2626;
            padding: 1rem;
            margin-top: 1rem;
            background-color: #fee;
            border: 1px solid #fcc;
            border-radius: 0.5rem;
        }
        #spinner {
            border: 2px solid #f3f4f6;
            border-top: 2px solid #111827;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Secure Payment</h1>
                <p class="text-gray-600 mt-2">Complete your payment securely with Stripe</p>
            </div>

            <!-- Order Summary Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Order Summary</h2>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Order Number:</span>
                        <span class="font-semibold text-gray-900">{{ $order->order_number }}</span>
                    </div>
                    @if($order->table)
                    <div class="flex justify-between">
                        <span class="text-gray-600">Table:</span>
                        <span class="font-semibold text-gray-900">{{ $order->table->name }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between pt-3 border-t border-gray-200">
                        <span class="text-lg font-bold text-gray-900">Amount Due:</span>
                        <span class="text-lg font-bold text-gray-900">${{ number_format($amount, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Payment Form -->
            <form id="payment-form" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Payment Details</h2>

                <!-- Stripe Elements will be mounted here -->
                <div id="payment-element" class="mb-4"></div>

                <!-- Error message container -->
                <div id="error-message"></div>

                <!-- Submit Button -->
                <button
                    id="submit-payment"
                    type="submit"
                    class="w-full mt-6 px-6 py-3 bg-gray-900 text-white rounded-lg hover:bg-gray-800 transition-colors font-semibold text-lg flex items-center justify-center"
                >
                    <span id="spinner" class="hidden mr-3"></span>
                    <span id="button-text">Pay ${{ number_format($amount, 2) }}</span>
                </button>

                <!-- Test Card Info -->
                <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                    <p class="text-xs text-blue-900 font-semibold mb-1">Test Mode - Use Test Card:</p>
                    <p class="text-xs text-blue-800">4242 4242 4242 4242</p>
                    <p class="text-xs text-blue-700">Any future expiry date, any 3-digit CVC</p>
                </div>
            </form>

            <!-- Back Link -->
            <div class="text-center mt-6">
                <a href="{{ route('orders.payment', $order->id) }}" class="text-gray-600 hover:text-gray-900 text-sm">
                    &larr; Back to payment options
                </a>
            </div>

            <!-- Security Badge -->
            <div class="text-center mt-6">
                <div class="flex items-center justify-center text-sm text-gray-600">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                    </svg>
                    Secured by Stripe
                </div>
            </div>
        </div>
    </div>

    <script>
        // Set global variables for Stripe
        window.stripePublicKey = '{{ $stripePublicKey }}';
        window.appName = '{{ config("app.name") }}';

        // Initialize Stripe
        const stripe = Stripe(window.stripePublicKey);
        let elements;
        let paymentElement;

        // Client secret from server
        const clientSecret = '{{ $clientSecret }}';

        // Initialize Stripe Elements
        async function initializeStripeElements() {
            elements = stripe.elements({ clientSecret });

            paymentElement = elements.create('payment', {
                layout: {
                    type: 'tabs',
                    defaultCollapsed: false,
                },
            });

            paymentElement.mount('#payment-element');

            paymentElement.on('change', (event) => {
                displayError(event.error ? event.error.message : '');
            });
        }

        // Handle form submission
        const form = document.getElementById('payment-form');
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            setLoading(true);

            const { error } = await stripe.confirmPayment({
                elements,
                confirmParams: {
                    return_url: '{{ route("payments.stripe.success") }}',
                },
            });

            if (error) {
                displayError(getErrorMessage(error));
                setLoading(false);
            }
        });

        // Display error message
        function displayError(message) {
            const errorElement = document.getElementById('error-message');
            if (errorElement) {
                errorElement.textContent = message;
                errorElement.style.display = message ? 'block' : 'none';
            }
        }

        // Set loading state
        function setLoading(isLoading) {
            const submitButton = document.getElementById('submit-payment');
            const spinner = document.getElementById('spinner');
            const buttonText = document.getElementById('button-text');

            if (submitButton) {
                submitButton.disabled = isLoading;
            }

            if (spinner) {
                spinner.classList.toggle('hidden', !isLoading);
            }

            if (buttonText) {
                buttonText.classList.toggle('hidden', isLoading);
            }
        }

        // Get user-friendly error message
        function getErrorMessage(error) {
            const errorMessages = {
                card_declined: 'Your card was declined. Please try another payment method.',
                expired_card: 'Your card has expired. Please use a different card.',
                incorrect_cvc: 'The security code is incorrect. Please check and try again.',
                processing_error: 'An error occurred while processing your card. Please try again.',
                incorrect_number: 'The card number is incorrect. Please check and try again.',
                insufficient_funds: 'Your card has insufficient funds. Please use a different payment method.',
            };

            return errorMessages[error.code] || error.message || 'An unexpected error occurred. Please try again.';
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', () => {
            initializeStripeElements();
        });
    </script>
</body>
</html>
