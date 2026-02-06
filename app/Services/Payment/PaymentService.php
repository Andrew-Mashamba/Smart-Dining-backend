<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentService
{
    /**
     * Generate bill for an order
     */
    public function generateBill(Order $order): array
    {
        $order->load(['items.menuItem', 'guest', 'table', 'waiter', 'payments']);

        $totalPaid = $order->payments()
            ->where('status', 'completed')
            ->sum('amount');

        return [
            'order_id' => $order->id,
            'guest' => [
                'name' => $order->guest->name,
                'phone' => $order->guest->phone_number,
            ],
            'table' => $order->table->name,
            'waiter' => $order->waiter->name,
            'items' => $order->items->map(function ($item) {
                return [
                    'name' => $item->menuItem->name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $item->subtotal,
                ];
            }),
            'breakdown' => [
                'subtotal' => $order->subtotal,
                'tax' => $order->tax,
                'total' => $order->total,
            ],
            'payment_info' => [
                'total_paid' => $totalPaid,
                'balance_due' => max(0, $order->total - $totalPaid),
            ],
            'created_at' => $order->created_at,
        ];
    }

    /**
     * Process a payment for an order
     */
    public function processPayment(Order $order, array $paymentData): Payment
    {
        return DB::transaction(function () use ($order, $paymentData) {
            $payment = Payment::create([
                'order_id' => $order->id,
                'amount' => $paymentData['amount'],
                'payment_method' => $paymentData['payment_method'],
                'status' => 'pending',
                'transaction_id' => $this->generateTransactionId(),
                'payment_details' => $paymentData['details'] ?? null,
            ]);

            // Process payment based on method
            switch ($paymentData['payment_method']) {
                case 'cash':
                    $this->processCashPayment($payment, $paymentData);
                    break;
                case 'card':
                    $this->processCardPayment($payment, $paymentData);
                    break;
                case 'mobile_money':
                    $this->processMobileMoneyPayment($payment, $paymentData);
                    break;
                default:
                    throw new \Exception('Invalid payment method');
            }

            return $payment->fresh();
        });
    }

    /**
     * Confirm a payment
     */
    public function confirmPayment(Payment $payment): void
    {
        if ($payment->status === 'completed') {
            return;
        }

        $payment->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // Check if order is fully paid
        $this->checkOrderPaymentStatus($payment->order);

        \Log::info('Payment confirmed', [
            'payment_id' => $payment->id,
            'order_id' => $payment->order_id,
            'amount' => $payment->amount,
        ]);
    }

    /**
     * Refund a payment
     */
    public function refundPayment(Payment $payment, string $reason): void
    {
        if ($payment->status !== 'completed') {
            throw new \Exception('Can only refund completed payments');
        }

        $payment->update([
            'status' => 'refunded',
            'payment_details' => array_merge(
                $payment->payment_details ?? [],
                ['refund_reason' => $reason, 'refunded_at' => now()]
            ),
        ]);

        \Log::info('Payment refunded', [
            'payment_id' => $payment->id,
            'amount' => $payment->amount,
            'reason' => $reason,
        ]);
    }

    /**
     * Process cash payment
     */
    protected function processCashPayment(Payment $payment, array $data): void
    {
        // Cash payments are immediately confirmed
        $payment->update([
            'status' => 'completed',
            'completed_at' => now(),
            'payment_details' => [
                'tendered' => $data['tendered'] ?? $data['amount'],
                'change' => ($data['tendered'] ?? $data['amount']) - $data['amount'],
            ],
        ]);

        // Check if order is fully paid and update status
        $this->checkOrderPaymentStatus($payment->order);
    }

    /**
     * Process card payment
     */
    protected function processCardPayment(Payment $payment, array $data): void
    {
        // In a real implementation, this would integrate with a payment gateway
        // For now, we'll simulate a successful card payment by directly confirming
        // Auto-confirm for development (in production, wait for gateway callback)
        $this->confirmPayment($payment);
    }

    /**
     * Process mobile money payment
     */
    protected function processMobileMoneyPayment(Payment $payment, array $data): void
    {
        // In a real implementation, this would integrate with M-Pesa/Tigopesa API
        // For now, we'll keep as pending until confirmed
        $payment->update([
            'payment_details' => [
                'phone_number' => $data['phone_number'] ?? null,
                'provider' => $data['provider'] ?? 'mpesa',
            ],
        ]);
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

    /**
     * Generate a unique transaction ID
     */
    protected function generateTransactionId(): string
    {
        return 'TXN-'.strtoupper(Str::random(12));
    }

    /**
     * Get payment history for an order
     */
    public function getPaymentHistory(Order $order): array
    {
        return $order->payments->map(function ($payment) {
            return [
                'payment_id' => $payment->id,
                'amount' => $payment->amount,
                'payment_method' => $payment->payment_method,
                'status' => $payment->status,
                'transaction_id' => $payment->transaction_id,
                'created_at' => $payment->created_at,
                'completed_at' => $payment->completed_at,
            ];
        })->toArray();
    }

    /**
     * Split payment across multiple methods
     */
    public function splitPayment(Order $order, array $payments): array
    {
        $totalAmount = collect($payments)->sum('amount');

        if ($totalAmount != $order->total) {
            throw new \Exception('Split payment total must equal order total');
        }

        $processedPayments = [];

        foreach ($payments as $paymentData) {
            $processedPayments[] = $this->processPayment($order, $paymentData);
        }

        return $processedPayments;
    }
}
