<?php

namespace App\Http\Controllers\Api;

use App\Events\PaymentReceived;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Setting;
use App\Services\Payment\PaymentService;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MpesaWebhookController extends Controller
{
    protected PaymentService $paymentService;

    protected WhatsAppService $whatsappService;

    public function __construct(PaymentService $paymentService, WhatsAppService $whatsappService)
    {
        $this->paymentService = $paymentService;
        $this->whatsappService = $whatsappService;
    }

    /**
     * Handle M-Pesa STK Push callback.
     */
    public function handle(Request $request): JsonResponse
    {
        $body = $request->input('Body.stkCallback', $request->all());

        Log::info('M-Pesa callback received', ['body' => $body]);

        $resultCode = $body['ResultCode'] ?? null;
        $checkoutRequestId = $body['CheckoutRequestID'] ?? null;

        if (! $checkoutRequestId) {
            Log::warning('M-Pesa callback missing CheckoutRequestID');

            return $this->respondSuccess();
        }

        // Find the payment by the checkout request ID stored as transaction_id
        $payment = Payment::where('transaction_id', $checkoutRequestId)->first();

        if (! $payment) {
            Log::warning('M-Pesa callback: payment not found', [
                'checkout_request_id' => $checkoutRequestId,
            ]);

            return $this->respondSuccess();
        }

        if ($resultCode == 0) {
            // Payment successful
            $metadata = $this->extractCallbackMetadata($body['CallbackMetadata'] ?? []);

            // Update gateway response first (before confirmPayment changes status)
            $payment->update([
                'gateway_response' => array_merge(
                    $payment->gateway_response ?? [],
                    [
                        'mpesa_receipt' => $metadata['MpesaReceiptNumber'] ?? null,
                        'transaction_date' => $metadata['TransactionDate'] ?? null,
                        'phone_number' => $metadata['PhoneNumber'] ?? null,
                    ]
                ),
            ]);

            // Confirm payment (sets status to completed and checks order payment status)
            $this->paymentService->confirmPayment($payment);

            // Notify guest via WhatsApp
            $order = $payment->order;
            if ($order) {

                // Notify guest via WhatsApp
                $guest = $order->guest;
                if ($guest) {
                    $receipt = $metadata['MpesaReceiptNumber'] ?? 'N/A';
                    $this->whatsappService->sendTextMessage(
                        $guest->phone_number,
                        "Payment received!\n\nAmount: TZS ".number_format($payment->amount, 0)."\nM-Pesa Receipt: {$receipt}\nOrder: #{$order->order_number}\n\nThank you for dining at " . Setting::get('business_name', config('app.name', 'Smart Dining')) . "!"
                    );
                }

                event(new PaymentReceived($payment));
            }

            Log::info('M-Pesa payment confirmed', [
                'payment_id' => $payment->id,
                'receipt' => $metadata['MpesaReceiptNumber'] ?? null,
            ]);
        } else {
            // Payment failed
            $payment->update([
                'status' => 'failed',
                'gateway_response' => array_merge(
                    $payment->gateway_response ?? [],
                    [
                        'result_code' => $resultCode,
                        'result_desc' => $body['ResultDesc'] ?? 'Payment failed',
                    ]
                ),
            ]);

            // Notify guest
            $guest = $payment->order?->guest;
            if ($guest) {
                $this->whatsappService->sendTextMessage(
                    $guest->phone_number,
                    "Your M-Pesa payment was not completed.\nReason: ".($body['ResultDesc'] ?? 'Unknown')."\n\nPlease try again or choose another payment method. Type 'pay' to retry."
                );
            }

            Log::info('M-Pesa payment failed', [
                'payment_id' => $payment->id,
                'result_code' => $resultCode,
                'result_desc' => $body['ResultDesc'] ?? null,
            ]);
        }

        return $this->respondSuccess();
    }

    /**
     * Extract metadata from M-Pesa callback.
     */
    protected function extractCallbackMetadata(array $callbackMetadata): array
    {
        $metadata = [];
        $items = $callbackMetadata['Item'] ?? [];

        foreach ($items as $item) {
            if (isset($item['Name'], $item['Value'])) {
                $metadata[$item['Name']] = $item['Value'];
            }
        }

        return $metadata;
    }

    /**
     * Always respond with success to M-Pesa.
     */
    protected function respondSuccess(): JsonResponse
    {
        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'Accepted',
        ]);
    }
}
