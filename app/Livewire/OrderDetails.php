<?php

namespace App\Livewire;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Tip;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class OrderDetails extends Component
{
    public $orderId;

    public $order;

    // Modal states
    public $showPaymentModal = false;

    public $showTipModal = false;

    public $showCancelConfirmation = false;

    // Payment form data
    public $payment_method = '';

    public $payment_amount = '';

    // Tip form data
    public $tip_amount = '';

    public $tip_method = '';

    // Status workflow transitions
    protected $allowedTransitions = [
        'pending' => ['preparing', 'cancelled'],
        'preparing' => ['ready', 'cancelled'],
        'ready' => ['delivered'],
        'delivered' => ['paid'],
        'paid' => [],
        'cancelled' => [],
    ];

    /**
     * Mount component with order ID
     */
    public function mount($orderId)
    {
        $this->orderId = $orderId;
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
            'payments',
            'tip',
        ])->findOrFail($this->orderId);
    }

    /**
     * Get allowed status transitions for current order
     */
    public function getAllowedTransitions()
    {
        return $this->allowedTransitions[$this->order->status] ?? [];
    }

    /**
     * Update order status with workflow validation
     */
    public function updateStatus($newStatus)
    {
        $currentStatus = $this->order->status;

        // Check if transition is allowed
        if (! in_array($newStatus, $this->getAllowedTransitions())) {
            session()->flash('error', 'Invalid status transition from '.$currentStatus.' to '.$newStatus);

            return;
        }

        // Update the status
        $this->order->updateStatus($newStatus);
        $this->loadOrder();

        session()->flash('success', 'Order status updated to '.$newStatus);
    }

    /**
     * Open payment modal
     */
    public function openPaymentModal()
    {
        $this->showPaymentModal = true;
        // Pre-fill with remaining balance
        $totalPaid = $this->order->payments()->where('status', 'completed')->sum('amount');
        $this->payment_amount = $this->order->total - $totalPaid;
    }

    /**
     * Close payment modal
     */
    public function closePaymentModal()
    {
        $this->showPaymentModal = false;
        $this->reset(['payment_method', 'payment_amount']);
    }

    /**
     * Add payment to order
     */
    public function addPayment()
    {
        $this->validate([
            'payment_method' => 'required|in:cash,card,mobile_money,bank_transfer',
            'payment_amount' => 'required|numeric|min:0.01',
        ]);

        try {
            DB::beginTransaction();

            // Create payment
            Payment::create([
                'order_id' => $this->order->id,
                'payment_method' => $this->payment_method,
                'amount' => $this->payment_amount,
                'status' => 'completed',
            ]);

            // Check if order is fully paid
            $totalPaid = $this->order->payments()->where('status', 'completed')->sum('amount');
            if ($totalPaid >= $this->order->total) {
                $this->order->updateStatus('paid');
            }

            DB::commit();

            $this->loadOrder();
            $this->closePaymentModal();
            session()->flash('success', 'Payment added successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Failed to add payment: '.$e->getMessage());
        }
    }

    /**
     * Open tip modal
     */
    public function openTipModal()
    {
        $this->showTipModal = true;
    }

    /**
     * Close tip modal
     */
    public function closeTipModal()
    {
        $this->showTipModal = false;
        $this->reset(['tip_amount', 'tip_method']);
    }

    /**
     * Add tip to order
     */
    public function addTip()
    {
        $this->validate([
            'tip_amount' => 'required|numeric|min:0.01',
            'tip_method' => 'required|in:cash,card,mobile_money',
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
            $this->closeTipModal();
            session()->flash('success', 'Tip added successfully');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to add tip: '.$e->getMessage());
        }
    }

    /**
     * Open cancel confirmation dialog
     */
    public function openCancelConfirmation()
    {
        if (! $this->order->isPending()) {
            session()->flash('error', 'Only pending orders can be cancelled');

            return;
        }

        $this->showCancelConfirmation = true;
    }

    /**
     * Close cancel confirmation dialog
     */
    public function closeCancelConfirmation()
    {
        $this->showCancelConfirmation = false;
    }

    /**
     * Cancel the order
     */
    public function cancelOrder()
    {
        if (! $this->order->isPending()) {
            session()->flash('error', 'Only pending orders can be cancelled');
            $this->closeCancelConfirmation();

            return;
        }

        $this->order->updateStatus('cancelled');
        $this->loadOrder();
        $this->closeCancelConfirmation();
        session()->flash('success', 'Order cancelled successfully');
    }

    /**
     * Generate and download receipt PDF
     */
    public function printReceipt()
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
     * Get payment status with styling
     */
    public function getPaymentStatus()
    {
        $totalPaid = $this->order->payments()->where('status', 'completed')->sum('amount');

        if ($totalPaid >= $this->order->total) {
            return ['label' => 'Paid', 'class' => 'bg-gray-900 text-white'];
        } elseif ($totalPaid > 0) {
            return ['label' => 'Partially Paid', 'class' => 'bg-gray-400 text-white'];
        } else {
            return ['label' => 'Unpaid', 'class' => 'bg-gray-200 text-gray-900'];
        }
    }

    /**
     * Get status badge styling
     */
    public function getStatusBadgeClass($status)
    {
        return match ($status) {
            'pending' => 'bg-gray-200 text-gray-900',
            'preparing' => 'bg-gray-400 text-white',
            'ready' => 'bg-gray-600 text-white',
            'delivered' => 'bg-gray-700 text-white',
            'paid' => 'bg-gray-900 text-white',
            'cancelled' => 'bg-gray-300 text-gray-900',
            default => 'bg-gray-100 text-gray-900',
        };
    }

    /**
     * Get prep status badge styling
     */
    public function getPrepStatusBadgeClass($status)
    {
        return match ($status) {
            'pending' => 'bg-gray-200 text-gray-900',
            'preparing' => 'bg-gray-400 text-white',
            'ready' => 'bg-gray-900 text-white',
            default => 'bg-gray-100 text-gray-900',
        };
    }

    /**
     * Render the component
     */
    public function render()
    {
        return view('livewire.order-details', [
            'allowedTransitions' => $this->getAllowedTransitions(),
            'paymentStatus' => $this->getPaymentStatus(),
        ])->layout('layouts.app-layout');
    }
}
