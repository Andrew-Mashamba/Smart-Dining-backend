<?php

namespace App\Livewire;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Tip;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ProcessPayment extends Component
{
    public $orderId;

    public $order;

    // Payment form data
    public $payment_method = '';

    public $amount = '';

    // Tip form data
    public $show_tip_section = false;

    public $tip_amount = '';

    public $tip_method = 'cash';

    // Payment tracking
    public $payments = [];

    public $totalPaid = 0;

    public $remainingBalance = 0;

    /**
     * Mount component with order ID
     */
    public function mount(Order $order)
    {
        $this->orderId = $order->id;
        $this->loadOrder();
    }

    /**
     * Load order with all relationships
     */
    public function loadOrder()
    {
        $this->order = Order::with([
            'orderItems.menuItem',
            'table',
            'waiter',
            'guest',
            'payments' => function ($query) {
                $query->where('status', 'completed');
            },
            'tip',
        ])->findOrFail($this->orderId);

        // Calculate payment tracking
        $this->payments = $this->order->payments;
        $this->totalPaid = $this->payments->sum('amount');
        $this->remainingBalance = $this->order->total - $this->totalPaid;

        // Pre-fill amount with remaining balance
        if ($this->remainingBalance > 0) {
            $this->amount = number_format($this->remainingBalance, 2, '.', '');
        }
    }

    /**
     * Process payment and update order status
     */
    public function processPayment()
    {
        // Validate payment method and amount
        $this->validate([
            'payment_method' => 'required|in:cash,card,mobile,gateway',
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
                function ($attribute, $value, $fail) {
                    // Allow partial payments but not overpayment
                    if ($value > $this->remainingBalance) {
                        $fail('Payment amount cannot exceed remaining balance of $'.number_format($this->remainingBalance, 2));
                    }
                },
            ],
        ], [
            'payment_method.required' => 'Please select a payment method.',
            'payment_method.in' => 'Invalid payment method selected.',
            'amount.required' => 'Please enter a payment amount.',
            'amount.numeric' => 'Amount must be a valid number.',
            'amount.min' => 'Amount must be at least $0.01',
        ]);

        // If gateway payment, redirect to Stripe payment page
        if ($this->payment_method === 'gateway') {
            return redirect()->route('payments.stripe.form', $this->order->id);
        }

        try {
            DB::beginTransaction();

            // Create payment record
            $payment = Payment::create([
                'order_id' => $this->order->id,
                'payment_method' => $this->payment_method,
                'amount' => $this->amount,
                'status' => 'completed',
            ]);

            // Recalculate totals
            $this->loadOrder();

            // Check if order is fully paid
            if ($this->totalPaid >= $this->order->total) {
                $this->order->updateStatus('paid');
            }

            DB::commit();

            // Show tip section after successful payment
            $this->show_tip_section = true;

            session()->flash('success', 'Payment of $'.number_format($this->amount, 2).' processed successfully.');

            // Reset payment form
            $this->reset(['payment_method', 'amount']);

            // If fully paid, reload to show tip section
            if ($this->totalPaid >= $this->order->total) {
                $this->amount = 0;
            } else {
                // Pre-fill with new remaining balance
                $this->amount = number_format($this->remainingBalance, 2, '.', '');
            }

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Failed to process payment: '.$e->getMessage());
        }
    }

    /**
     * Process tip for the order
     */
    public function processTip()
    {
        // Validate tip amount and method
        $this->validate([
            'tip_amount' => 'required|numeric|min:0.01',
            'tip_method' => 'required|in:cash,card,mobile',
        ], [
            'tip_amount.required' => 'Please enter a tip amount.',
            'tip_amount.numeric' => 'Tip amount must be a valid number.',
            'tip_amount.min' => 'Tip amount must be at least $0.01',
            'tip_method.required' => 'Please select a tip method.',
            'tip_method.in' => 'Invalid tip method selected.',
        ]);

        try {
            // Create or update tip
            Tip::updateOrCreate(
                ['order_id' => $this->order->id],
                [
                    'waiter_id' => $this->order->waiter_id,
                    'amount' => $this->tip_amount,
                    'tip_method' => $this->tip_method,
                ]
            );

            $this->loadOrder();
            session()->flash('success', 'Tip of $'.number_format($this->tip_amount, 2).' added successfully.');

            // Reset tip form
            $this->reset(['tip_amount', 'tip_method']);
            $this->tip_method = 'cash';

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to add tip: '.$e->getMessage());
        }
    }

    /**
     * Skip tip and redirect
     */
    public function skipTip()
    {
        if ($this->totalPaid >= $this->order->total) {
            return redirect()->route('orders')->with('success', 'Payment completed successfully.');
        }

        session()->flash('info', 'You can add a tip later from the order details page.');
    }

    /**
     * Generate and download receipt PDF
     */
    public function downloadReceipt()
    {
        $order = $this->order;

        // Generate PDF
        $pdf = Pdf::loadView('pdf.receipt', compact('order'));

        // Download the PDF
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'receipt-'.$this->order->order_number.'.pdf');
    }

    /**
     * Get payment method display name
     */
    public function getPaymentMethodName($method)
    {
        return match ($method) {
            'cash' => 'Cash',
            'card' => 'Credit/Debit Card',
            'mobile' => 'Mobile Payment',
            'gateway' => 'Payment Gateway',
            default => ucfirst($method),
        };
    }

    /**
     * Calculate suggested tip amounts (10%, 15%, 20%)
     */
    public function getSuggestedTips()
    {
        return [
            '10' => number_format($this->order->total * 0.10, 2),
            '15' => number_format($this->order->total * 0.15, 2),
            '20' => number_format($this->order->total * 0.20, 2),
        ];
    }

    /**
     * Set tip amount from suggested percentage
     */
    public function setTipAmount($percentage)
    {
        $this->tip_amount = number_format($this->order->total * ($percentage / 100), 2, '.', '');
    }

    /**
     * Render the component
     */
    public function render()
    {
        return view('livewire.process-payment', [
            'suggestedTips' => $this->getSuggestedTips(),
        ])->layout('layouts.app-layout');
    }
}
