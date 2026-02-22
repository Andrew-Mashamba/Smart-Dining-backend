<?php

namespace App\Listeners;

use App\Events\WaiterRequested;
use App\Jobs\SendFcmNotification;
use Illuminate\Support\Facades\Log;

class SendWaiterRequestFcm
{
    public function handle(WaiterRequested $event): void
    {
        Log::info('Sending FCM notification for waiter request', [
            'table' => $event->table->name,
            'guest' => $event->guest->phone_number,
        ]);

        $data = [
            'title' => 'Guest Request',
            'body' => "Table {$event->table->name} is requesting a waiter",
            'type' => 'waiter_requested',
            'table_id' => (string) $event->table->id,
            'table_name' => $event->table->name,
            'guest_name' => $event->guest->name ?? 'Guest',
            'guest_phone' => $event->guest->phone_number,
            'timestamp' => now()->toISOString(),
        ];

        SendFcmNotification::dispatch('waiters', [], $data)
            ->onQueue('notifications');
    }
}
