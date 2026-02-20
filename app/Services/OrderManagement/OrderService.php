<?php

namespace App\Services\OrderManagement;

use App\Events\OrderCreated;
use App\Events\OrderStatusUpdated;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Models\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /**
     * Create a new order
     */
    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $order = Order::create([
                'guest_id' => $data['guest_id'],
                'table_id' => $data['table_id'],
                'waiter_id' => $data['waiter_id'],
                'session_id' => $data['session_id'] ?? null,
                'order_source' => $data['order_source'] ?? 'pos',
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
                'special_instructions' => $data['special_instructions'] ?? null,
                'subtotal' => 0,
                'tax' => 0,
                'total' => 0,
            ]);

            if (isset($data['items']) && is_array($data['items'])) {
                $this->addItems($order, $data['items']);
            }

            // Update table status to occupied
            if ($order->table_id) {
                Table::where('id', $order->table_id)->update(['status' => 'occupied']);
            }

            // Dispatch OrderCreated event
            event(new OrderCreated($order->fresh()));

            return $order->fresh();
        });
    }

    /**
     * Add items to an order
     */
    public function addItems(Order $order, array $items): void
    {
        // Stock/inventory is managed elsewhere; no validation or deduction for POS orders.
        foreach ($items as $item) {
            $menuItem = MenuItem::findOrFail($item['menu_item_id']);

            $orderItem = OrderItem::create([
                'order_id' => $order->id,
                'menu_item_id' => $menuItem->id,
                'quantity' => $item['quantity'],
                'unit_price' => $menuItem->price,
                'subtotal' => $menuItem->price * $item['quantity'],
                'prep_status' => 'pending',
                'special_instructions' => $item['special_instructions'] ?? null,
            ]);
        }

        $this->calculateTotals($order);
    }

    /**
     * Remove an item from an order
     */
    public function removeItem(OrderItem $orderItem): void
    {
        $order = $orderItem->order;

        if (in_array($orderItem->prep_status, ['preparing', 'ready', 'served'])) {
            throw new \Exception('Cannot remove item that is already being prepared or served');
        }

        $orderItem->delete();
        $this->calculateTotals($order);
    }

    /**
     * Update order status
     */
    public function updateOrderStatus(Order $order, string $status): void
    {
        $validTransitions = $this->getValidStatusTransitions($order->status);

        if (! in_array($status, $validTransitions)) {
            throw new \Exception("Cannot transition from {$order->status} to {$status}");
        }

        $oldStatus = $order->status;
        $order->update(['status' => $status]);

        // Dispatch OrderStatusUpdated event
        event(new OrderStatusUpdated($order, $oldStatus, $status));
    }

    /**
     * Calculate order totals
     */
    public function calculateTotals(Order $order): array
    {
        $subtotal = $order->items()->sum('subtotal');

        // Get tax rate from settings, default to 18% if not set
        $taxRate = Setting::get('tax_rate', 18) / 100;
        $tax = $subtotal * $taxRate;
        $total = $subtotal + $tax;

        $order->update([
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
        ]);

        return [
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
        ];
    }

    /**
     * Cancel an order
     */
    public function cancelOrder(Order $order, string $reason): void
    {
        if (in_array($order->status, ['completed', 'cancelled'])) {
            throw new \Exception('Cannot cancel a completed or already cancelled order');
        }

        $order->update([
            'status' => 'cancelled',
            'notes' => ($order->notes ?? '')."\nCancellation reason: ".$reason,
        ]);

        // Note: Items remain in their current prep_status as the migration
        // doesn't support 'cancelled' status for order items
        // Items can be identified as cancelled through the order's status
    }

    /**
     * Get valid status transitions for current status
     */
    protected function getValidStatusTransitions(string $currentStatus): array
    {
        $transitions = [
            'pending' => ['confirmed', 'cancelled'],
            'confirmed' => ['preparing', 'cancelled'],
            'preparing' => ['ready', 'cancelled'],
            'ready' => ['served'],
            'served' => ['completed'],
            'completed' => [],
            'cancelled' => [],
        ];

        return $transitions[$currentStatus] ?? [];
    }

    /**
     * Get order summary with items
     */
    public function getOrderSummary(Order $order): array
    {
        $order->load(['items.menuItem', 'guest', 'table', 'waiter']);

        return [
            'order_id' => $order->id,
            'guest' => [
                'name' => $order->guest->name,
                'phone' => $order->guest->phone_number,
            ],
            'table' => $order->table->name,
            'waiter' => $order->waiter->name,
            'status' => $order->status,
            'items' => $order->items->map(function ($item) {
                return [
                    'name' => $item->menuItem->name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $item->subtotal,
                    'status' => $item->status,
                    'special_instructions' => $item->special_instructions,
                ];
            }),
            'totals' => [
                'subtotal' => $order->subtotal,
                'tax' => $order->tax,
                'service_charge' => $order->service_charge,
                'total_amount' => $order->total_amount,
            ],
            'created_at' => $order->created_at,
        ];
    }

    /**
     * Get orders by status
     */
    public function getOrdersByStatus(string $status): Collection
    {
        return Order::where('status', $status)
            ->with(['items.menuItem', 'guest', 'table', 'waiter'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
