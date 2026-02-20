<?php

namespace App\Listeners;

use App\Events\OrderItemUpdated;
use App\Jobs\SendFcmNotification;
use Illuminate\Support\Facades\Log;

class SendPrepStatusFcmNotification
{
    public function handle(OrderItemUpdated $event): void
    {
        $orderItem = $event->orderItem;
        $order = $orderItem->order;

        Log::info('FCM Listener [PrepStatus]: OrderItemUpdated event received', [
            'order_item_id' => $orderItem->id,
            'prep_status' => $orderItem->prep_status,
            'order_id' => $order?->id,
            'waiter_id' => $order?->waiter_id,
        ]);

        if (! $order || ! $order->waiter_id) {
            Log::info('FCM Listener [PrepStatus]: Skipped — no order or no waiter assigned');

            return;
        }

        $data = [
            'type' => 'order_item_status_changed',
            'order_id' => (string) $order->id,
            'order_number' => $order->order_number ?? '',
            'order_item_id' => (string) $orderItem->id,
            'menu_item_name' => $orderItem->menuItem->name ?? 'Unknown',
            'prep_status' => $orderItem->prep_status,
            'table_name' => $order->table->name ?? 'N/A',
            'timestamp' => now()->toISOString(),
        ];

        Log::info('FCM Listener [PrepStatus]: Dispatching FCM job', [
            'waiter_id' => $order->waiter_id,
            'menu_item' => $data['menu_item_name'],
            'prep_status' => $data['prep_status'],
        ]);

        SendFcmNotification::dispatch('staff', [$order->waiter_id], $data)
            ->onQueue('notifications');
    }
}
