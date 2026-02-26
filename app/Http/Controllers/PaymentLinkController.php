<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Setting;
use App\Services\Payment\PaymentService;
use App\Services\Payment\StripePaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentLinkController extends Controller
{
    protected PaymentService $paymentService;

    protected StripePaymentService $stripeService;

    public function __construct(PaymentService $paymentService, StripePaymentService $stripeService)
    {
        $this->paymentService = $paymentService;
        $this->stripeService = $stripeService;
    }

    /**
     * Show the payment page for a given token.
     */
    public function show(string $token)
    {
        $payment = Payment::where('token', $token)
            ->with('order.items.menuItem')
            ->first();

        if (! $payment) {
            abort(404, 'Payment link not found');
        }

        if ($payment->status === 'completed') {
            return view('payments.link', [
                'payment' => $payment,
                'order' => $payment->order,
                'status' => 'completed',
                'message' => 'This payment has already been completed.',
                'clientSecret' => null,
                'stripePublicKey' => null,
            ]);
        }

        if ($payment->token_expires_at && $payment->token_expires_at->isPast()) {
            return view('payments.link', [
                'payment' => $payment,
                'order' => $payment->order,
                'status' => 'expired',
                'message' => 'This payment link has expired. Please request a new one.',
                'clientSecret' => null,
                'stripePublicKey' => null,
            ]);
        }

        // Create Stripe PaymentIntent for card payment
        $clientSecret = null;
        $stripePublicKey = Setting::get('stripe_public_key', config('services.stripe.public_key'));

        if ($stripePublicKey && Setting::get('stripe_secret_key', config('services.stripe.secret'))) {
            try {
                $result = $this->stripeService->processPayment(
                    $payment->order_id,
                    $payment->amount
                );
                $clientSecret = $result['client_secret'];

                // Store the PaymentIntent ID on the payment record
                $payment->update([
                    'gateway_response' => array_merge(
                        $payment->gateway_response ?? [],
                        ['stripe_payment_intent_id' => $result['payment_intent_id']]
                    ),
                ]);
            } catch (\Exception $e) {
                Log::warning('Stripe PaymentIntent creation failed for payment link', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return view('payments.link', [
            'payment' => $payment,
            'order' => $payment->order,
            'status' => 'pending',
            'message' => null,
            'clientSecret' => $clientSecret,
            'stripePublicKey' => $stripePublicKey,
        ]);
    }

    /**
     * Process payment from the payment link page.
     */
    public function process(string $token, Request $request)
    {
        $payment = Payment::where('token', $token)->first();

        if (! $payment || $payment->status === 'completed') {
            return redirect()->route('payment.link.show', $token)
                ->with('error', 'This payment has already been processed.');
        }

        if ($payment->token_expires_at && $payment->token_expires_at->isPast()) {
            return redirect()->route('payment.link.show', $token)
                ->with('error', 'This payment link has expired.');
        }

        try {
            $paymentIntentId = $request->input('payment_intent_id');

            if ($paymentIntentId) {
                // Verify payment with Stripe
                $paymentIntent = $this->stripeService->retrievePaymentIntent($paymentIntentId);

                if ($paymentIntent->status === 'succeeded') {
                    $payment->update([
                        'transaction_id' => $paymentIntentId,
                        'gateway_response' => array_merge(
                            $payment->gateway_response ?? [],
                            [
                                'stripe_status' => $paymentIntent->status,
                                'stripe_payment_method' => $paymentIntent->payment_method,
                            ]
                        ),
                    ]);
                    $this->paymentService->confirmPayment($payment);
                } else {
                    return redirect()->route('payment.link.show', $token)
                        ->with('error', 'Payment has not been completed. Please try again.');
                }
            } else {
                // Fallback: direct confirmation (for environments without Stripe configured)
                $this->paymentService->confirmPayment($payment);
            }

            // Notify guest via WhatsApp if order has a guest
            $order = $payment->order;
            $guest = $order?->guest;
            if ($guest) {
                try {
                    $whatsappService = app(\App\Services\WhatsApp\WhatsAppService::class);
                    $whatsappService->sendTextMessage(
                        $guest->phone_number,
                        "Payment received!\n\nAmount: TZS ".number_format($payment->amount, 0)."\nOrder: #{$order->order_number}\n\nThank you for dining at " . Setting::get('business_name', config('app.name', 'Smart Dining')) . "!"
                    );
                } catch (\Exception $e) {
                    Log::warning('Failed to send WhatsApp receipt after link payment', [
                        'payment_id' => $payment->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('Payment link processed', [
                'payment_id' => $payment->id,
                'token' => $token,
                'stripe' => (bool) $paymentIntentId,
            ]);

            return redirect()->route('payment.link.show', $token)
                ->with('success', 'Payment completed successfully!');
        } catch (\Exception $e) {
            Log::error('Payment link processing failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('payment.link.show', $token)
                ->with('error', 'Payment failed. Please try again.');
        }
    }
}
