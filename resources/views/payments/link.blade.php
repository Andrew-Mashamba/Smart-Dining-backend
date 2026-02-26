<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Smart Dining') }} - Payment</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @if(!empty($clientSecret) && !empty($stripePublicKey))
        <script src="https://js.stripe.com/v3/"></script>
    @endif
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-lg max-w-md w-full overflow-hidden">
        {{-- Header --}}
        <div class="bg-blue-600 text-white p-6 text-center">
            <h1 class="text-2xl font-bold">{{ \App\Models\Setting::get('business_name', config('app.name', 'Smart Dining')) }}</h1>
            <p class="text-blue-100 mt-1">Secure Payment</p>
        </div>

        <div class="p-6">
            @if($status === 'completed')
                {{-- Payment Already Completed --}}
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <h2 class="text-xl font-semibold text-gray-800">Payment Complete</h2>
                    <p class="text-gray-500 mt-2">{{ $message }}</p>
                    <p class="text-lg font-bold text-green-600 mt-4">TZS {{ number_format($payment->amount, 0) }}</p>
                </div>

            @elseif($status === 'expired')
                {{-- Payment Link Expired --}}
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h2 class="text-xl font-semibold text-gray-800">Link Expired</h2>
                    <p class="text-gray-500 mt-2">{{ $message }}</p>
                </div>

            @else
                {{-- Order Summary --}}
                @if($order)
                    <div class="mb-6">
                        <h2 class="text-lg font-semibold text-gray-800 mb-3">Order #{{ $order->order_number }}</h2>
                        <div class="space-y-2">
                            @foreach($order->items as $item)
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">{{ $item->menuItem->name }} x{{ $item->quantity }}</span>
                                    <span class="text-gray-800">TZS {{ number_format($item->subtotal, 0) }}</span>
                                </div>
                            @endforeach
                        </div>
                        <hr class="my-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Subtotal</span>
                            <span>TZS {{ number_format($order->subtotal, 0) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Tax</span>
                            <span>TZS {{ number_format($order->tax, 0) }}</span>
                        </div>
                        <div class="flex justify-between text-lg font-bold mt-2">
                            <span>Total</span>
                            <span class="text-blue-600">TZS {{ number_format($payment->amount, 0) }}</span>
                        </div>
                    </div>
                @endif

                {{-- Flash Messages --}}
                @if(session('success'))
                    <div class="bg-green-50 text-green-700 p-3 rounded-lg mb-4 text-sm">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="bg-red-50 text-red-700 p-3 rounded-lg mb-4 text-sm">
                        {{ session('error') }}
                    </div>
                @endif

                @if(!empty($clientSecret) && !empty($stripePublicKey))
                    {{-- Stripe Card Payment Form --}}
                    <form id="payment-form" action="{{ route('payment.link.process', $payment->token) }}" method="POST">
                        @csrf
                        <input type="hidden" name="payment_intent_id" id="payment-intent-id" value="">

                        <div id="card-element" class="border border-gray-300 rounded-lg p-3 mb-4">
                            {{-- Stripe Elements will be mounted here --}}
                        </div>

                        <div id="card-errors" class="text-red-500 text-sm mb-3" role="alert"></div>

                        <button id="submit-button" type="submit"
                                class="w-full bg-blue-600 text-white py-3 rounded-xl font-semibold text-lg hover:bg-blue-700 transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed">
                            <span id="button-text">Pay TZS {{ number_format($payment->amount, 0) }}</span>
                            <span id="spinner" class="hidden">Processing...</span>
                        </button>
                    </form>

                    <script>
                        const stripe = Stripe('{{ $stripePublicKey }}');
                        const elements = stripe.elements({clientSecret: '{{ $clientSecret }}'});

                        const paymentElement = elements.create('payment');
                        paymentElement.mount('#card-element');

                        const form = document.getElementById('payment-form');
                        const submitButton = document.getElementById('submit-button');

                        form.addEventListener('submit', async (e) => {
                            e.preventDefault();
                            submitButton.disabled = true;
                            document.getElementById('button-text').classList.add('hidden');
                            document.getElementById('spinner').classList.remove('hidden');

                            const {error, paymentIntent} = await stripe.confirmPayment({
                                elements,
                                confirmParams: {},
                                redirect: 'if_required',
                            });

                            if (error) {
                                document.getElementById('card-errors').textContent = error.message;
                                submitButton.disabled = false;
                                document.getElementById('button-text').classList.remove('hidden');
                                document.getElementById('spinner').classList.add('hidden');
                            } else if (paymentIntent && paymentIntent.status === 'succeeded') {
                                document.getElementById('payment-intent-id').value = paymentIntent.id;
                                form.submit();
                            }
                        });
                    </script>
                @else
                    {{-- Fallback: Simple payment confirmation (no Stripe configured) --}}
                    <form action="{{ route('payment.link.process', $payment->token) }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="w-full bg-blue-600 text-white py-3 rounded-xl font-semibold text-lg hover:bg-blue-700 transition-colors">
                            Pay TZS {{ number_format($payment->amount, 0) }}
                        </button>
                    </form>
                @endif

                <p class="text-xs text-gray-400 text-center mt-4">
                    Secure payment powered by {{ config('app.name', 'Smart Dining') }}
                </p>
            @endif
        </div>
    </div>
</body>
</html>
