<?php

namespace App\Http\Controllers;

use App\Services\Payment\StripePaymentService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    protected StripePaymentService $stripePaymentService;

    public function __construct(StripePaymentService $stripePaymentService)
    {
        $this->stripePaymentService = $stripePaymentService;
    }

    /**
     * Handle incoming Stripe webhooks
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
        } catch (SignatureVerificationException $e) {
            Log::error('Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 400);
        } catch (Exception $e) {
            Log::error('Stripe webhook error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Webhook error'], 400);
        }

        // Handle the event
        try {
            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $this->handlePaymentIntentSucceeded($event->data->object);
                    break;

                case 'payment_intent.payment_failed':
                case 'payment_intent.failed':
                    $this->handlePaymentIntentFailed($event->data->object);
                    break;

                case 'payment_intent.processing':
                    $this->handlePaymentIntentProcessing($event->data->object);
                    break;

                case 'payment_intent.canceled':
                    $this->handlePaymentIntentCanceled($event->data->object);
                    break;

                default:
                    Log::info('Unhandled Stripe webhook event', [
                        'type' => $event->type,
                    ]);
            }

            return response()->json(['status' => 'success']);
        } catch (Exception $e) {
            Log::error('Failed to process Stripe webhook', [
                'event_type' => $event->type,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    /**
     * Handle successful payment intent
     *
     * @param  object  $paymentIntent
     * @return void
     */
    protected function handlePaymentIntentSucceeded($paymentIntent)
    {
        try {
            $payment = $this->stripePaymentService->confirmPayment($paymentIntent->id);

            Log::info('Payment intent succeeded and payment confirmed', [
                'payment_intent_id' => $paymentIntent->id,
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'amount' => $payment->amount,
            ]);

            // Update payment status to completed
            $payment->update(['status' => 'completed']);

            // Check if order is fully paid
            $order = $payment->order;
            $totalPaid = $order->payments()
                ->where('status', 'completed')
                ->sum('amount');

            if ($totalPaid >= $order->total && $order->status === 'served') {
                $order->update(['status' => 'completed']);
            }
        } catch (Exception $e) {
            Log::error('Error handling successful payment intent', [
                'payment_intent_id' => $paymentIntent->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle failed payment intent
     *
     * @param  object  $paymentIntent
     * @return void
     */
    protected function handlePaymentIntentFailed($paymentIntent)
    {
        try {
            $errorMessage = $paymentIntent->last_payment_error->message ?? 'Payment failed';

            $payment = $this->stripePaymentService->handleFailedPayment(
                $paymentIntent->id,
                $errorMessage
            );

            if ($payment) {
                Log::info('Payment intent failed and payment marked as failed', [
                    'payment_intent_id' => $paymentIntent->id,
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'error' => $errorMessage,
                ]);
            }
        } catch (Exception $e) {
            Log::error('Error handling failed payment intent', [
                'payment_intent_id' => $paymentIntent->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle processing payment intent
     *
     * @param  object  $paymentIntent
     * @return void
     */
    protected function handlePaymentIntentProcessing($paymentIntent)
    {
        Log::info('Stripe payment processing', [
            'payment_intent_id' => $paymentIntent->id,
        ]);

        // Update payment status to processing if needed
        // This is optional - you may want to track this state
    }

    /**
     * Handle canceled payment intent
     *
     * @param  object  $paymentIntent
     * @return void
     */
    protected function handlePaymentIntentCanceled($paymentIntent)
    {
        try {
            $payment = $this->stripePaymentService->handleFailedPayment(
                $paymentIntent->id,
                'Payment was canceled'
            );

            if ($payment) {
                $payment->update(['status' => 'canceled']);

                Log::info('Payment intent canceled', [
                    'payment_intent_id' => $paymentIntent->id,
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order_id,
                ]);
            }
        } catch (Exception $e) {
            Log::error('Error handling canceled payment intent', [
                'payment_intent_id' => $paymentIntent->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
