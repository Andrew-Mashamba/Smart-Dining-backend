<?php

namespace App\Services;

use App\Events\OrderStatusUpdated;
use App\Exceptions\OrderWorkflowException;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\OrderStatusLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderWorkflowService
{
    /**
     * Valid status transitions map.
     * Each key is a current status with an array of allowed next statuses.
     */
    protected array $validTransitions = [
        'pending' => ['preparing'],
        'preparing' => ['ready'],
        'ready' => ['delivered'],
        'delivered' => ['paid'],
    ];

    /**
     * Update order status with validation and business rules.
     *
     * @throws OrderWorkflowException
     */
    public function updateStatus(int $orderId, string $newStatus): Order
    {
        return DB::transaction(function () use ($orderId, $newStatus) {
            // Load order with necessary relationships
            $order = Order::with(['orderItems', 'payments', 'table'])->find($orderId);

            if (! $order) {
                throw OrderWorkflowException::orderNotFound($orderId);
            }

            $oldStatus = $order->status;

            // Validate transition is allowed
            $this->validateTransition($oldStatus, $newStatus);

            // Apply business rules based on target status
            match ($newStatus) {
                'ready' => $this->validateOrderItemsReady($order),
                'paid' => $this->validatePaymentComplete($order),
                default => null,
            };

            // Update order status
            $order->status = $newStatus;
            $order->save();

            // Handle table status when order is paid
            if ($newStatus === 'paid') {
                $this->updateTableStatusIfNeeded($order);
            }

            // Create audit log
            $this->createAuditLog($order, $oldStatus, $newStatus);

            // Broadcast event for real-time notifications
            event(new OrderStatusUpdated($order, $oldStatus, $newStatus));

            return $order->fresh();
        });
    }

    /**
     * Validate that the status transition is allowed.
     *
     * @throws OrderWorkflowException
     */
    protected function validateTransition(string $currentStatus, string $newStatus): void
    {
        // Check if current status has any valid transitions
        if (! isset($this->validTransitions[$currentStatus])) {
            throw OrderWorkflowException::invalidTransition($currentStatus, $newStatus);
        }

        // Check if new status is in the list of valid next statuses
        if (! in_array($newStatus, $this->validTransitions[$currentStatus])) {
            throw OrderWorkflowException::invalidTransition($currentStatus, $newStatus);
        }
    }

    /**
     * Validate that all order items are ready before marking order as ready.
     *
     * @throws OrderWorkflowException
     */
    protected function validateOrderItemsReady(Order $order): void
    {
        $notReadyCount = $order->orderItems()
            ->where('prep_status', '!=', 'ready')
            ->count();

        if ($notReadyCount > 0) {
            throw OrderWorkflowException::itemsNotReady($order->id);
        }
    }

    /**
     * Validate that payment is complete before marking order as paid.
     *
     * @throws OrderWorkflowException
     */
    protected function validatePaymentComplete(Order $order): void
    {
        $totalPaid = $order->payments()
            ->where('status', 'completed')
            ->sum('amount');

        if ($totalPaid < $order->total) {
            throw OrderWorkflowException::insufficientPayment(
                $order->id,
                (float) $totalPaid,
                (float) $order->total
            );
        }
    }

    /**
     * Update table status to available if no other active orders exist.
     */
    protected function updateTableStatusIfNeeded(Order $order): void
    {
        if (! $order->table) {
            return;
        }

        // Check if there are any other active orders for this table
        $activeOrdersCount = Order::where('table_id', $order->table_id)
            ->where('id', '!=', $order->id)
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->count();

        // If no other active orders, mark table as available
        if ($activeOrdersCount === 0) {
            $order->table->markAsAvailable();
        }
    }

    /**
     * Create an audit log entry for the status change.
     */
    protected function createAuditLog(Order $order, string $oldStatus, string $newStatus): void
    {
        // Create OrderStatusLog entry for status change tracking
        OrderStatusLog::create([
            'order_id' => $order->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'user_id' => Auth::id(),
        ]);

        // Also create general audit log entry
        AuditLog::create([
            'auditable_type' => Order::class,
            'auditable_id' => $order->id,
            'event' => 'status_changed',
            'old_values' => ['status' => $oldStatus],
            'new_values' => ['status' => $newStatus],
            'user_id' => Auth::id(),
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Get all valid transitions from a given status.
     */
    public function getValidTransitions(string $status): array
    {
        return $this->validTransitions[$status] ?? [];
    }

    /**
     * Check if a transition is valid.
     */
    public function isValidTransition(string $currentStatus, string $newStatus): bool
    {
        return in_array($newStatus, $this->getValidTransitions($currentStatus));
    }
}
