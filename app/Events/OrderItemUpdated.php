<?php

namespace App\Events;

use App\Models\OrderItem;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderItemUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public OrderItem $orderItem;

    /**
     * Create a new event instance.
     */
    public function __construct(OrderItem $orderItem)
    {
        $this->orderItem = $orderItem;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('orders'),
        ];

        // Broadcast to the appropriate prep area channel
        if ($this->orderItem->menuItem && $this->orderItem->menuItem->prep_area) {
            $prepArea = $this->orderItem->menuItem->prep_area;
            $channels[] = new PrivateChannel($prepArea);
        }

        return $channels;
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'item_id' => $this->orderItem->id,
            'order_id' => $this->orderItem->order_id,
            'prep_status' => $this->orderItem->prep_status,
            'menu_item_name' => $this->orderItem->menuItem->name ?? 'Unknown',
        ];
    }
}
