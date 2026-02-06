<?php

namespace App\Http\Controllers;

use App\Services\Payment\StripePaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StripePaymentController extends Controller
{
    protected StripePaymentService $stripeService;

    public function __construct(StripePaymentService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Create a Stripe PaymentIntent
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function createIntent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|integer|exists:orders,id',
            'amount' => 'required|numeric|min:0.5',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->stripeService->processPayment(
                $request->order_id,
                $request->amount
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Confirm a Stripe payment (client-side confirmation)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function confirm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_intent_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Retrieve the PaymentIntent from Stripe to verify status
            $paymentIntent = $this->stripeService->retrievePaymentIntent(
                $request->payment_intent_id
            );

            // If succeeded, confirm the payment
            if ($paymentIntent->status === 'succeeded') {
                $payment = $this->stripeService->confirmPayment($paymentIntent->id);

                return response()->json([
                    'success' => true,
                    'payment' => $payment,
                    'message' => 'Payment confirmed successfully',
                ]);
            }

            return response()->json([
                'success' => false,
                'status' => $paymentIntent->status,
                'message' => 'Payment not completed',
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
