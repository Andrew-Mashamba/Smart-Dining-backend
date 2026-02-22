<?php

namespace App\Events;

use App\Models\Guest;
use App\Models\Table;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WaiterRequested implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Table $table;

    public Guest $guest;

    public function __construct(Table $table, Guest $guest)
    {
        $this->table = $table;
        $this->guest = $guest;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('notifications'),
            new Channel('waiters'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'type' => 'waiter_requested',
            'table_id' => $this->table->id,
            'table_name' => $this->table->name,
            'guest_name' => $this->guest->name ?? 'Guest',
            'guest_phone' => $this->guest->phone_number,
            'timestamp' => now()->toISOString(),
        ];
    }
}
