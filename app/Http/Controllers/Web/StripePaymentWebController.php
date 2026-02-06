<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Payment\StripePaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StripePaymentWebController extends Controller
{
    protected StripePaymentService $stripeService;

    public function __construct(StripePaymentService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Show Stripe payment form
     *
     * @return \Illuminate\View\View
     */
    public function show(Order $order)
    {
        try {
            // Calculate remaining balance
            $totalPaid = $order->payments()->where('status', 'completed')->sum('amount');
            $remainingBalance = $order->total - $totalPaid;

            if ($remainingBalance <= 0) {
                return redirect()->route('orders.show', $order->id)
                    ->with('error', 'This order is already fully paid.');
            }

            // Create PaymentIntent
            $result = $this->stripeService->processPayment($order->id, $remainingBalance);

            return view('payment.stripe-form', [
                'order' => $order,
                'clientSecret' => $result['client_secret'],
                'paymentIntentId' => $result['payment_intent_id'],
                'amount' => $remainingBalance,
                'stripePublicKey' => config('services.stripe.public_key'),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to load Stripe payment form', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('orders.payment', $order->id)
                ->with('error', 'Failed to initialize payment gateway. Please try again or use a different payment method.');
        }
    }

    /**
     * Handle successful payment return
     *
     * @return \Illuminate\View\View
     */
    public function success(Request $request)
    {
        $paymentIntentId = $request->query('payment_intent');

        if (! $paymentIntentId) {
            return redirect()->route('orders')->with('error', 'Payment information not found.');
        }

        try {
            // Retrieve the PaymentIntent to verify status
            $paymentIntent = $this->stripeService->retrievePaymentIntent($paymentIntentId);

            if ($paymentIntent->status === 'succeeded') {
                return view('payment.stripe-success', [
                    'paymentIntent' => $paymentIntent,
                    'message' => 'Payment successful! Your order has been processed.',
                ]);
            } else {
                return view('payment.stripe-success', [
                    'paymentIntent' => $paymentIntent,
                    'message' => 'Payment is being processed. You will receive a confirmation shortly.',
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to verify payment status', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('orders')->with('error', 'Failed to verify payment status.');
        }
    }
}
