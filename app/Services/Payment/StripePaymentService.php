<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class StripePaymentService
{
    protected StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    /**
     * Process payment via Stripe PaymentIntent
     *
     * @throws \Exception
     */
    public function processPayment(int $orderId, float $amount): array
    {
        try {
            $order = Order::findOrFail($orderId);

            // Create PaymentIntent
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => $this->convertToSmallestUnit($amount),
                'currency' => config('services.stripe.currency', 'usd'),
                'metadata' => [
                    'order_id' => $orderId,
                    'order_number' => $order->order_number ?? "ORDER-{$orderId}",
                ],
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            Log::info('Stripe PaymentIntent created', [
                'payment_intent_id' => $paymentIntent->id,
                'order_id' => $orderId,
                'amount' => $amount,
            ]);

            return [
                'success' => true,
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $amount,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe PaymentIntent creation failed', [
                'order_id' => $orderId,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception($this->getUserFriendlyErrorMessage($e));
        }
    }

    /**
     * Confirm payment after successful Stripe charge
     *
     * @throws \Exception
     */
    public function confirmPayment(string $paymentIntentId): Payment
    {
        try {
            $paymentIntent = $this->stripe->paymentIntents->retrieve($paymentIntentId);

            $orderId = $paymentIntent->metadata->order_id ?? null;
            if (! $orderId) {
                throw new \Exception('Order ID not found in payment metadata');
            }

            $order = Order::findOrFail($orderId);

            // Create or update Payment record
            $payment = Payment::updateOrCreate(
                ['transaction_id' => $paymentIntentId],
                [
                    'order_id' => $orderId,
                    'amount' => $this->convertFromSmallestUnit($paymentIntent->amount),
                    'payment_method' => 'gateway',
                    'status' => $paymentIntent->status === 'succeeded' ? 'completed' : 'pending',
                    'gateway_response' => json_encode([
                        'id' => $paymentIntent->id,
                        'status' => $paymentIntent->status,
                        'amount' => $paymentIntent->amount,
                        'currency' => $paymentIntent->currency,
                        'payment_method' => $paymentIntent->payment_method,
                        'created' => $paymentIntent->created,
                    ]),
                ]
            );

            Log::info('Payment confirmed via Stripe', [
                'payment_id' => $payment->id,
                'payment_intent_id' => $paymentIntentId,
                'order_id' => $orderId,
                'status' => $payment->status,
            ]);

            return $payment;
        } catch (ApiErrorException $e) {
            Log::error('Stripe payment confirmation failed', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception($this->getUserFriendlyErrorMessage($e));
        }
    }

    /**
     * Handle failed payment
     */
    public function handleFailedPayment(string $paymentIntentId, string $errorMessage): ?Payment
    {
        try {
            $paymentIntent = $this->stripe->paymentIntents->retrieve($paymentIntentId);

            $orderId = $paymentIntent->metadata->order_id ?? null;
            if (! $orderId) {
                return null;
            }

            $payment = Payment::updateOrCreate(
                ['transaction_id' => $paymentIntentId],
                [
                    'order_id' => $orderId,
                    'amount' => $this->convertFromSmallestUnit($paymentIntent->amount),
                    'payment_method' => 'gateway',
                    'status' => 'failed',
                    'gateway_response' => json_encode([
                        'id' => $paymentIntent->id,
                        'status' => $paymentIntent->status,
                        'amount' => $paymentIntent->amount,
                        'currency' => $paymentIntent->currency,
                        'error_message' => $errorMessage,
                        'last_payment_error' => $paymentIntent->last_payment_error,
                    ]),
                ]
            );

            Log::warning('Payment failed via Stripe', [
                'payment_id' => $payment->id,
                'payment_intent_id' => $paymentIntentId,
                'order_id' => $orderId,
                'error' => $errorMessage,
            ]);

            return $payment;
        } catch (\Exception $e) {
            Log::error('Error handling failed payment', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Convert amount to smallest currency unit (cents for USD)
     */
    protected function convertToSmallestUnit(float $amount): int
    {
        return (int) round($amount * 100);
    }

    /**
     * Convert amount from smallest currency unit
     */
    protected function convertFromSmallestUnit(int $amount): float
    {
        return round($amount / 100, 2);
    }

    /**
     * Get user-friendly error message from Stripe exception
     */
    protected function getUserFriendlyErrorMessage(ApiErrorException $exception): string
    {
        $stripeCode = $exception->getStripeCode();

        return match ($stripeCode) {
            'card_declined' => 'Your card was declined. Please try a different payment method.',
            'expired_card' => 'Your card has expired. Please use a different card.',
            'incorrect_cvc' => 'The security code (CVC) is incorrect. Please check and try again.',
            'processing_error' => 'An error occurred while processing your card. Please try again.',
            'insufficient_funds' => 'Your card has insufficient funds. Please use a different payment method.',
            'invalid_number' => 'The card number is invalid. Please check and try again.',
            default => 'Payment failed. Please check your card details and try again.',
        };
    }

    /**
     * Retrieve PaymentIntent from Stripe
     *
     * @return \Stripe\PaymentIntent
     *
     * @throws ApiErrorException
     */
    public function retrievePaymentIntent(string $paymentIntentId)
    {
        return $this->stripe->paymentIntents->retrieve($paymentIntentId);
    }
}
