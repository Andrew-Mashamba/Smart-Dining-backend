<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Models\InventoryTransaction;
use App\Models\Staff;
use App\Notifications\LowStockAlert;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class DeductInventoryStock implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(OrderCreated $event): void
    {
        $order = $event->order;

        try {
            DB::beginTransaction();

            // Process each order item
            foreach ($order->orderItems as $orderItem) {
                $menuItem = $orderItem->menuItem;

                // Deduct stock quantity
                $menuItem->decrement('stock_quantity', $orderItem->quantity);

                // Create inventory transaction (negative quantity for sale)
                InventoryTransaction::create([
                    'menu_item_id' => $menuItem->id,
                    'transaction_type' => 'sale',
                    'quantity' => -$orderItem->quantity,
                    'unit' => $menuItem->unit,
                    'reference_id' => $order->id,
                    'notes' => "Order #{$order->order_number}",
                    'created_by' => $order->waiter_id,
                ]);

                // Reload the menu item to get updated stock_quantity
                $menuItem->refresh();

                // Check if stock is low after deduction
                if ($menuItem->stock_quantity < $menuItem->low_stock_threshold) {
                    // Send notification to all managers
                    $managers = Staff::where('role', 'manager')
                        ->where('status', 'active')
                        ->get();

                    Notification::send($managers, new LowStockAlert($menuItem));

                    Log::info("Low stock alert sent for menu item: {$menuItem->name}", [
                        'menu_item_id' => $menuItem->id,
                        'current_stock' => $menuItem->stock_quantity,
                        'threshold' => $menuItem->low_stock_threshold,
                    ]);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to deduct inventory stock', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
