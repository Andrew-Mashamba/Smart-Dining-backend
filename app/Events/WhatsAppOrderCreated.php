<?php

namespace App\Events;

use App\Models\Guest;
use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WhatsAppOrderCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Order $order;

    public Guest $guest;

    public function __construct(Order $order, Guest $guest)
    {
        $this->order = $order;
        $this->guest = $guest;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('orders'),
            new Channel('kitchen'),
            new Channel('bar'),
            new Channel('notifications'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'type' => 'whatsapp_order',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'table' => $this->order->table?->name,
            'guest_name' => $this->guest->name ?? 'WhatsApp Guest',
            'guest_phone' => $this->guest->phone_number,
            'items_count' => $this->order->orderItems->count(),
            'total' => $this->order->total,
            'source' => 'whatsapp',
            'timestamp' => now()->toISOString(),
        ];
    }
}
