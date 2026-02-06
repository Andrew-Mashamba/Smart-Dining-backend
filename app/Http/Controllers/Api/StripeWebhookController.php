<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StripePaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    protected StripePaymentService $stripeService;

    public function __construct(StripePaymentService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Handle Stripe webhook events
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        try {
            // Verify webhook signature
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                $webhookSecret
            );
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            Log::error('Stripe webhook invalid payload', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            // Invalid signature
            Log::error('Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Log webhook event
        Log::info('Stripe webhook received', [
            'event_type' => $event->type,
            'event_id' => $event->id,
        ]);

        // Handle the event based on type
        try {
            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $this->handlePaymentIntentSucceeded($event->data->object);
                    break;

                case 'payment_intent.payment_failed':
                case 'payment_intent.failed':
                    $this->handlePaymentIntentFailed($event->data->object);
                    break;

                case 'payment_intent.canceled':
                    $this->handlePaymentIntentCanceled($event->data->object);
                    break;

                default:
                    // Unexpected event type
                    Log::info('Unhandled Stripe webhook event', [
                        'event_type' => $event->type,
                    ]);
            }
        } catch (\Exception $e) {
            Log::error('Error processing Stripe webhook', [
                'event_type' => $event->type,
                'event_id' => $event->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }

        return response()->json(['status' => 'success'], 200);
    }

    /**
     * Handle successful payment intent
     *
     * @param  object  $paymentIntent
     */
    protected function handlePaymentIntentSucceeded($paymentIntent): void
    {
        Log::info('Processing payment_intent.succeeded', [
            'payment_intent_id' => $paymentIntent->id,
            'amount' => $paymentIntent->amount,
        ]);

        try {
            // Convert Stripe PaymentIntent object to array
            $stripeResponse = json_decode(json_encode($paymentIntent), true);

            // Confirm payment in database
            $payment = $this->stripeService->confirmPayment(
                $paymentIntent->id,
                $stripeResponse
            );

            Log::info('Payment confirmed successfully', [
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process payment_intent.succeeded', [
                'payment_intent_id' => $paymentIntent->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle failed payment intent
     *
     * @param  object  $paymentIntent
     */
    protected function handlePaymentIntentFailed($paymentIntent): void
    {
        Log::info('Processing payment_intent.payment_failed', [
            'payment_intent_id' => $paymentIntent->id,
            'amount' => $paymentIntent->amount,
        ]);

        try {
            // Convert Stripe PaymentIntent object to array
            $stripeResponse = json_decode(json_encode($paymentIntent), true);

            // Mark payment as failed in database
            $payment = $this->stripeService->failPayment(
                $paymentIntent->id,
                $stripeResponse
            );

            Log::info('Payment marked as failed', [
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process payment_intent.payment_failed', [
                'payment_intent_id' => $paymentIntent->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle canceled payment intent
     *
     * @param  object  $paymentIntent
     */
    protected function handlePaymentIntentCanceled($paymentIntent): void
    {
        Log::info('Processing payment_intent.canceled', [
            'payment_intent_id' => $paymentIntent->id,
            'amount' => $paymentIntent->amount,
        ]);

        try {
            // Convert Stripe PaymentIntent object to array
            $stripeResponse = json_decode(json_encode($paymentIntent), true);

            // Mark payment as failed in database
            $payment = $this->stripeService->failPayment(
                $paymentIntent->id,
                $stripeResponse
            );

            Log::info('Payment marked as canceled', [
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process payment_intent.canceled', [
                'payment_intent_id' => $paymentIntent->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
