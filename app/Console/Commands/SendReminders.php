<?php

namespace App\Console\Commands;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminder notifications for pending orders';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for orders needing reminders...');

        // Send reminders for orders that are ready but not picked up for 15+ minutes
        $readyOrders = Order::where('status', 'ready')
            ->where('updated_at', '<', Carbon::now()->subMinutes(15))
            ->where('updated_at', '>', Carbon::now()->subMinutes(45))
            ->with('user')
            ->get();

        $remindersSent = 0;
        foreach ($readyOrders as $order) {
            if ($order->user) {
                try {
                    $order->user->notify(new \App\Notifications\OrderReadyReminder($order));
                    $remindersSent++;
                    $this->info("Sent pickup reminder for order #{$order->id}");
                } catch (\Exception $e) {
                    $this->error("Failed to send reminder for order #{$order->id}: {$e->getMessage()}");
                }
            }
        }

        // Send reminders to kitchen staff for orders that have been preparing for too long
        $delayedOrders = Order::where('status', 'preparing')
            ->where('updated_at', '<', Carbon::now()->subMinutes(20))
            ->with('user')
            ->get();

        $kitchenAlerts = 0;
        foreach ($delayedOrders as $order) {
            try {
                // Notify kitchen staff
                \Illuminate\Support\Facades\Notification::route('mail', config('mail.from.address'))
                    ->notify(new \App\Notifications\DelayedOrderAlert($order));

                $kitchenAlerts++;
                $this->warn("Sent delayed order alert for order #{$order->id}");
            } catch (\Exception $e) {
                $this->error("Failed to send kitchen alert for order #{$order->id}: {$e->getMessage()}");
            }
        }

        $totalSent = $remindersSent + $kitchenAlerts;

        if ($totalSent === 0) {
            $this->info('No reminders needed at this time.');
        } else {
            $this->info("Sent {$remindersSent} customer reminders and {$kitchenAlerts} kitchen alerts.");
        }

        return 0;
    }
}
