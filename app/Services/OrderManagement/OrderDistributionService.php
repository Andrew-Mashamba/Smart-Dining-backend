<?php

namespace App\Services\OrderManagement;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Staff;
use Illuminate\Support\Collection;

class OrderDistributionService
{
    /**
     * Distribute order items to appropriate preparation areas
     */
    public function distributeOrder(Order $order): array
    {
        $order->load('items.menuItem');

        $kitchenItems = $order->items->filter(function ($item) {
            return $item->menuItem->prep_area === 'kitchen';
        });

        $barItems = $order->items->filter(function ($item) {
            return $item->menuItem->prep_area === 'bar';
        });

        $distribution = [];

        if ($kitchenItems->isNotEmpty()) {
            $this->notifyKitchen($kitchenItems);
            $distribution['kitchen'] = $kitchenItems->count();
        }

        if ($barItems->isNotEmpty()) {
            $this->notifyBar($barItems);
            $distribution['bar'] = $barItems->count();
        }

        return $distribution;
    }

    /**
     * Notify kitchen about new food orders
     */
    public function notifyKitchen(Collection $foodItems): void
    {
        // Mark items as confirmed for kitchen
        $foodItems->each(function ($item) {
            $item->update(['prep_status' => 'confirmed']);
        });

        // In a real implementation, this would:
        // - Send notification to kitchen display system
        // - Trigger real-time updates via websockets
        // - Log to kitchen order queue
        \Log::info('Kitchen notified of new orders', [
            'items' => $foodItems->pluck('id')->toArray(),
            'count' => $foodItems->count(),
        ]);
    }

    /**
     * Notify bar about new drink orders
     */
    public function notifyBar(Collection $drinkItems): void
    {
        // Mark items as confirmed for bar
        $drinkItems->each(function ($item) {
            $item->update(['prep_status' => 'confirmed']);
        });

        // In a real implementation, this would:
        // - Send notification to bar display system
        // - Trigger real-time updates via websockets
        // - Log to bar order queue
        \Log::info('Bar notified of new orders', [
            'items' => $drinkItems->pluck('id')->toArray(),
            'count' => $drinkItems->count(),
        ]);
    }

    /**
     * Mark an order item as received by kitchen/bar staff
     */
    public function markItemReceived(OrderItem $item, Staff $staff): void
    {
        if (! in_array($staff->role, ['chef', 'bartender'])) {
            throw new \Exception('Only chefs and bartenders can mark items as received');
        }

        $expectedArea = $staff->role === 'chef' ? 'kitchen' : 'bar';
        $actualArea = $item->menuItem->prep_area;

        if ($expectedArea !== $actualArea) {
            throw new \Exception("This item should be prepared in the {$actualArea}, not by a {$staff->role}");
        }

        $item->update([
            'prep_status' => 'preparing',
            'prepared_by' => $staff->id,
        ]);
    }

    /**
     * Mark an order item as ready
     */
    public function markItemReady(OrderItem $item): void
    {
        if ($item->prep_status !== 'preparing') {
            throw new \Exception('Item must be in preparing status to mark as ready');
        }

        $item->update(['prep_status' => 'ready']);

        // Check if all items in the order are ready
        $this->checkOrderReadiness($item->order);
    }

    /**
     * Check if all items in an order are ready
     */
    protected function checkOrderReadiness(Order $order): void
    {
        $allItemsReady = $order->items()
            ->whereNotIn('prep_status', ['cancelled'])
            ->get()
            ->every(function ($item) {
                return $item->prep_status === 'ready';
            });

        if ($allItemsReady && $order->status === 'preparing') {
            $order->update(['status' => 'ready']);

            // Notify waiter that order is ready
            \Log::info('Order ready for service', [
                'order_id' => $order->id,
                'waiter_id' => $order->waiter_id,
            ]);
        }
    }

    /**
     * Get pending items for a specific preparation area
     */
    public function getPendingItems(string $prepArea): Collection
    {
        return OrderItem::whereHas('menuItem', function ($query) use ($prepArea) {
            $query->where('prep_area', $prepArea);
        })
            ->whereIn('prep_status', ['confirmed', 'preparing'])
            ->with(['order.table', 'menuItem'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get items being prepared by a specific staff member
     */
    public function getItemsByStaff(Staff $staff): Collection
    {
        return OrderItem::where('prepared_by', $staff->id)
            ->whereIn('prep_status', ['preparing', 'ready'])
            ->with(['order.table', 'menuItem'])
            ->get();
    }
}
