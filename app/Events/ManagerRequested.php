<?php

namespace App\Events;

use App\Models\Guest;
use App\Models\Table;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ManagerRequested implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Table $table;

    public Guest $guest;

    public ?string $reason;

    public function __construct(Table $table, Guest $guest, ?string $reason = null)
    {
        $this->table = $table;
        $this->guest = $guest;
        $this->reason = $reason;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('notifications'),
            new Channel('managers'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'type' => 'manager_requested',
            'table_id' => $this->table->id,
            'table_name' => $this->table->name,
            'guest_name' => $this->guest->name ?? 'Guest',
            'guest_phone' => $this->guest->phone_number,
            'reason' => $this->reason,
            'timestamp' => now()->toISOString(),
        ];
    }
}
