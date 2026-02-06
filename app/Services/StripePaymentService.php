<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Exception;
use Illuminate\Support\Facades\Log;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class StripePaymentService
{
    public function __construct()
    {
        // Set Stripe API key
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Process payment through Stripe
     * Creates a PaymentIntent for the given order
     *
     * @return array Contains client_secret and payment_intent_id
     *
     * @throws Exception
     */
    public function processPayment(int $orderId, float $amount): array
    {
        try {
            $order = Order::findOrFail($orderId);

            // Create Stripe PaymentIntent
            $paymentIntent = PaymentIntent::create([
                'amount' => (int) ($amount * 100), // Convert to cents
                'currency' => config('services.stripe.currency', 'usd'),
                'metadata' => [
                    'order_id' => $orderId,
                ],
                'description' => "Payment for Order #{$orderId}",
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            // Create pending Payment record
            $payment = Payment::create([
                'order_id' => $orderId,
                'payment_method' => 'gateway',
                'amount' => $amount,
                'status' => 'pending',
                'transaction_id' => $paymentIntent->id,
                'gateway_response' => [
                    'payment_intent_id' => $paymentIntent->id,
                    'status' => $paymentIntent->status,
                    'created_at' => now()->toIso8601String(),
                ],
            ]);

            Log::info('Stripe PaymentIntent created', [
                'order_id' => $orderId,
                'payment_id' => $payment->id,
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $amount,
            ]);

            return [
                'success' => true,
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
                'payment_id' => $payment->id,
            ];
        } catch (Exception $e) {
            Log::error('Stripe payment creation failed', [
                'order_id' => $orderId,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            throw new Exception('Failed to create payment: '.$e->getMessage());
        }
    }

    /**
     * Confirm payment success and update Payment record
     *
     * @param  array  $stripeResponse  Full Stripe response
     *
     * @throws Exception
     */
    public function confirmPayment(string $paymentIntentId, array $stripeResponse): Payment
    {
        try {
            $payment = Payment::where('transaction_id', $paymentIntentId)->firstOrFail();

            $payment->update([
                'status' => 'completed',
                'gateway_response' => array_merge(
                    $payment->gateway_response ?? [],
                    [
                        'completed_at' => now()->toIso8601String(),
                        'stripe_response' => $stripeResponse,
                    ]
                ),
            ]);

            Log::info('Stripe payment confirmed', [
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'payment_intent_id' => $paymentIntentId,
            ]);

            // Check if order is fully paid
            $this->checkOrderPaymentStatus($payment->order);

            return $payment;
        } catch (Exception $e) {
            Log::error('Failed to confirm Stripe payment', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Mark payment as failed
     *
     * @throws Exception
     */
    public function failPayment(string $paymentIntentId, array $stripeResponse): Payment
    {
        try {
            $payment = Payment::where('transaction_id', $paymentIntentId)->firstOrFail();

            $payment->update([
                'status' => 'failed',
                'gateway_response' => array_merge(
                    $payment->gateway_response ?? [],
                    [
                        'failed_at' => now()->toIso8601String(),
                        'stripe_response' => $stripeResponse,
                    ]
                ),
            ]);

            Log::warning('Stripe payment failed', [
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'payment_intent_id' => $paymentIntentId,
            ]);

            return $payment;
        } catch (Exception $e) {
            Log::error('Failed to mark Stripe payment as failed', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Retrieve PaymentIntent from Stripe
     *
     * @throws Exception
     */
    public function retrievePaymentIntent(string $paymentIntentId): PaymentIntent
    {
        try {
            return PaymentIntent::retrieve($paymentIntentId);
        } catch (Exception $e) {
            Log::error('Failed to retrieve PaymentIntent', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage(),
            ]);

            throw new Exception('Failed to retrieve payment information');
        }
    }

    /**
     * Get user-friendly error message for Stripe errors
     */
    public function getErrorMessage(string $errorCode): string
    {
        $messages = [
            'card_declined' => 'Your card was declined. Please try another payment method.',
            'expired_card' => 'Your card has expired. Please use a different card.',
            'incorrect_cvc' => 'The security code is incorrect. Please check and try again.',
            'processing_error' => 'An error occurred while processing your card. Please try again.',
            'incorrect_number' => 'The card number is incorrect. Please check and try again.',
            'insufficient_funds' => 'Your card has insufficient funds. Please use a different payment method.',
            'invalid_expiry_month' => 'The expiration month is invalid.',
            'invalid_expiry_year' => 'The expiration year is invalid.',
            'authentication_required' => 'Authentication is required. Please try again.',
        ];

        return $messages[$errorCode] ?? 'An error occurred while processing your payment. Please try again.';
    }

    /**
     * Check if order is fully paid and update status
     */
    protected function checkOrderPaymentStatus(Order $order): void
    {
        $totalPaid = $order->payments()
            ->where('status', 'completed')
            ->sum('amount');

        if ($totalPaid >= $order->total && $order->status === 'served') {
            $order->update(['status' => 'completed']);
        }
    }
}
