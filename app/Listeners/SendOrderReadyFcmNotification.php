<?php

namespace App\Listeners;

use App\Events\OrderStatusUpdated;
use App\Jobs\SendFcmNotification;
use Illuminate\Support\Facades\Log;

class SendOrderReadyFcmNotification
{
    public function handle(OrderStatusUpdated $event): void
    {
        Log::info('FCM Listener [OrderReady]: OrderStatusUpdated event received', [
            'order_id' => $event->order->id,
            'old_status' => $event->oldStatus,
            'new_status' => $event->newStatus,
        ]);

        if ($event->newStatus !== 'ready') {
            Log::info('FCM Listener [OrderReady]: Skipped — status is not ready', [
                'new_status' => $event->newStatus,
            ]);

            return;
        }

        $order = $event->order;

        if (! $order->waiter_id) {
            Log::info('FCM Listener [OrderReady]: Skipped — no waiter assigned', [
                'order_id' => $order->id,
            ]);

            return;
        }

        $data = [
            'type' => 'order_ready',
            'order_id' => (string) $order->id,
            'order_number' => $order->order_number ?? '',
            'table_name' => $order->table->name ?? 'N/A',
            'previous_status' => $event->oldStatus,
            'new_status' => 'ready',
            'timestamp' => now()->toISOString(),
        ];

        Log::info('FCM Listener [OrderReady]: Dispatching FCM job', [
            'waiter_id' => $order->waiter_id,
            'order_number' => $data['order_number'],
        ]);

        SendFcmNotification::dispatch('staff', [$order->waiter_id], $data)
            ->onQueue('notifications');
    }
}
