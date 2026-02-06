<?php

namespace App\Console\Commands;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateOrderStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:update-statuses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update order statuses based on payment confirmations and time';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for pending payment confirmations...');

        // Auto-cancel orders that have been pending for more than 30 minutes
        $pendingOrders = Order::where('status', 'pending')
            ->where('created_at', '<', Carbon::now()->subMinutes(30))
            ->get();

        $cancelledCount = 0;
        foreach ($pendingOrders as $order) {
            $order->update(['status' => 'cancelled']);
            $cancelledCount++;

            // Notify customer about cancellation
            if ($order->user) {
                $order->user->notify(new \App\Notifications\OrderCancelled($order));
            }
        }

        if ($cancelledCount > 0) {
            $this->info("Cancelled {$cancelledCount} orders that were pending for too long.");
        }

        // Mark orders as ready for pickup if preparation time has elapsed
        $preparingOrders = Order::where('status', 'preparing')
            ->where('updated_at', '<', Carbon::now()->subMinutes(15))
            ->get();

        $readyCount = 0;
        foreach ($preparingOrders as $order) {
            $order->update(['status' => 'ready']);
            $readyCount++;

            // Notify customer that order is ready
            if ($order->user) {
                $order->user->notify(new \App\Notifications\OrderReady($order));
            }
        }

        if ($readyCount > 0) {
            $this->info("Marked {$readyCount} orders as ready for pickup.");
        }

        // Mark orders as completed if they were picked up (ready status for over 1 hour)
        $readyOrders = Order::where('status', 'ready')
            ->where('updated_at', '<', Carbon::now()->subHours(1))
            ->get();

        $completedCount = 0;
        foreach ($readyOrders as $order) {
            $order->update(['status' => 'completed']);
            $completedCount++;
        }

        if ($completedCount > 0) {
            $this->info("Marked {$completedCount} orders as completed.");
        }

        $totalProcessed = $cancelledCount + $readyCount + $completedCount;

        if ($totalProcessed === 0) {
            $this->info('No orders needed status updates.');
        } else {
            $this->info("Total orders processed: {$totalProcessed}");
        }

        return 0;
    }
}
