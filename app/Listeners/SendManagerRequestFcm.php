<?php

namespace App\Listeners;

use App\Events\ManagerRequested;
use App\Jobs\SendFcmNotification;
use App\Models\Staff;
use Illuminate\Support\Facades\Log;

class SendManagerRequestFcm
{
    public function handle(ManagerRequested $event): void
    {
        Log::info('Sending FCM notification for manager request', [
            'table' => $event->table->name,
            'guest' => $event->guest->phone_number,
        ]);

        $managerIds = Staff::where('role', 'manager')
            ->where('status', 'active')
            ->pluck('id')
            ->toArray();

        if (empty($managerIds)) {
            Log::warning('No active managers found for FCM notification');

            return;
        }

        $data = [
            'title' => 'Manager Request',
            'body' => "Table {$event->table->name} is requesting a manager",
            'type' => 'manager_requested',
            'table_id' => (string) $event->table->id,
            'table_name' => $event->table->name,
            'guest_name' => $event->guest->name ?? 'Guest',
            'guest_phone' => $event->guest->phone_number,
            'reason' => $event->reason ?? '',
            'timestamp' => now()->toISOString(),
        ];

        SendFcmNotification::dispatch('staff_members', $managerIds, $data)
            ->onQueue('notifications');
    }
}
