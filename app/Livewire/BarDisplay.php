<?php

namespace App\Livewire;

use App\Events\OrderItemUpdated;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

class BarDisplay extends Component
{
    /**
     * Track if fullscreen mode is enabled (managed by Alpine.js)
     */
    public $fullscreenMode = false;

    /**
     * Listen to OrderCreated event from Echo channel
     * This will trigger when a new order is created with bar items
     */
    #[On('echo:bar,OrderCreated')]
    public function handleNewOrder($data)
    {
        // Refresh the component to show new order
        $this->dispatch('new-order-alert');
    }

    /**
     * Listen to OrderItemUpdated event
     */
    #[On('echo:bar,OrderItemUpdated')]
    public function handleOrderItemUpdate($data)
    {
        // Auto-refresh when items are updated
        $this->render();
    }

    /**
     * Update item preparation status
     *
     * @param  int  $itemId  The order item ID
     * @param  string  $status  The new status (received, preparing, ready)
     */
    public function updateItemStatus($itemId, $status)
    {
        $validStatuses = ['pending', 'received', 'preparing', 'ready'];

        if (! in_array($status, $validStatuses)) {
            session()->flash('error', 'Invalid status');

            return;
        }

        $orderItem = OrderItem::with(['menuItem', 'order.table'])->findOrFail($itemId);
        $orderItem->prep_status = $status;
        $orderItem->save();

        // Broadcast the update to other bar displays
        broadcast(new OrderItemUpdated($orderItem))->toOthers();

        session()->flash('message', 'Item status updated to '.$status);
    }

    /**
     * Get all pending bar orders grouped by order_id
     * Orders with items that have prep_area in ['bar', 'both'] and
     * prep_status in ['pending', 'received', 'preparing']
     */
    protected function getBarOrders()
    {
        // Get all order items for bar that are not ready yet with optimized eager loading
        $orderItems = OrderItem::with([
            'order:id,order_number,table_id,created_at',
            'order.table:id,name',
            'menuItem:id,name,prep_area',
        ])
            ->whereHas('menuItem', function ($query) {
                $query->whereIn('prep_area', ['bar', 'both']);
            })
            ->whereIn('prep_status', ['pending', 'received', 'preparing'])
            ->get();

        // Group by order_id
        $groupedOrders = $orderItems->groupBy('order_id')->map(function ($items, $orderId) {
            $order = $items->first()->order;

            // Calculate elapsed time since order was created
            $createdAt = Carbon::parse($order->created_at);
            $elapsedMinutes = $createdAt->diffInMinutes(now());

            // Determine if order is high priority (older than 10 minutes)
            $isHighPriority = $elapsedMinutes > 10;

            return [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'table_name' => $order->table ? $order->table->name : 'N/A',
                'created_at' => $createdAt,
                'elapsed_time' => $this->formatElapsedTime($elapsedMinutes),
                'elapsed_minutes' => $elapsedMinutes,
                'is_high_priority' => $isHighPriority,
                'items' => $items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'menu_item_name' => $item->menuItem->name,
                        'quantity' => $item->quantity,
                        'prep_status' => $item->prep_status,
                        'special_instructions' => $item->special_instructions,
                    ];
                })->toArray(),
            ];
        });

        // Sort by elapsed time (oldest first)
        return $groupedOrders->sortByDesc('elapsed_minutes')->values();
    }

    /**
     * Format elapsed time in human-readable format
     *
     * @param  int  $minutes
     * @return string
     */
    protected function formatElapsedTime($minutes)
    {
        if ($minutes < 60) {
            return $minutes.' min';
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        return $hours.'h '.$remainingMinutes.'m';
    }

    /**
     * Get status button color classes
     *
     * @param  string  $status
     * @return string
     */
    public function getStatusButtonClass($status)
    {
        return match ($status) {
            'pending' => 'bg-gray-100 text-gray-700 hover:bg-gray-200',
            'received' => 'bg-gray-200 text-gray-800 hover:bg-gray-300',
            'preparing' => 'bg-gray-300 text-gray-900 hover:bg-gray-400',
            'ready' => 'bg-gray-900 text-white hover:bg-gray-800',
            default => 'bg-gray-100 text-gray-700',
        };
    }

    /**
     * Render the component
     */
    public function render()
    {
        $orders = $this->getBarOrders();

        return view('livewire.bar-display', [
            'orders' => $orders,
        ])->layout('layouts.bar-layout');
    }
}
