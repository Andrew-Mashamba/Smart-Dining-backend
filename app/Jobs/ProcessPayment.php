<?php

namespace App\Jobs;

use App\Events\PaymentReceived;
use App\Models\Payment;
use App\Services\Payment\PaymentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessPayment implements ShouldQueue
{
    use Queueable;

    public Payment $payment;

    /**
     * Create a new job instance.
     */
    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
    }

    /**
     * Execute the job.
     */
    public function handle(PaymentService $paymentService): void
    {
        try {
            // Process payment through payment gateway
            // This is where integration with Pesapal, M-Pesa, etc. would happen

            if ($this->payment->payment_method === 'mobile_money') {
                // Simulate M-Pesa/Tigopesa processing
                \Log::info('Processing mobile money payment', [
                    'payment_id' => $this->payment->id,
                    'amount' => $this->payment->amount,
                ]);

                // In production, make API call to mobile money provider
                // For now, auto-confirm after delay
                sleep(2);
            }

            // Confirm the payment
            $paymentService->confirmPayment($this->payment);

            // Dispatch event
            event(new PaymentReceived($this->payment));

            \Log::info('Payment processed successfully', [
                'payment_id' => $this->payment->id,
                'order_id' => $this->payment->order_id,
            ]);
        } catch (\Exception $e) {
            \Log::error('Payment processing failed', [
                'payment_id' => $this->payment->id,
                'error' => $e->getMessage(),
            ]);

            $this->payment->update(['status' => 'failed']);

            throw $e;
        }
    }
}
