<?php

namespace App\Console\Commands;

use App\Events\OrderCreated;
use App\Events\OrderStatusUpdated;
use App\Models\Order;
use Illuminate\Console\Command;

class TestBroadcast extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'broadcast:test {--event=order-created : The event to test (order-created or order-status-updated)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Laravel Reverb broadcasting by dispatching events';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $eventType = $this->option('event');

        // Get a test order (first order in database)
        $order = Order::with(['orderItems.menuItem', 'table', 'waiter'])->first();

        if (! $order) {
            $this->error('No orders found in database. Please create an order first.');

            return 1;
        }

        $this->info("Testing broadcast with Order #{$order->order_number}");

        if ($eventType === 'order-status-updated') {
            // Test OrderStatusUpdated event
            $oldStatus = $order->status;
            $newStatus = $oldStatus === 'pending' ? 'preparing' : 'pending';

            $this->info("Dispatching OrderStatusUpdated event (status: {$oldStatus} -> {$newStatus})...");
            event(new OrderStatusUpdated($order, $oldStatus, $newStatus));
            $this->info('✓ OrderStatusUpdated event dispatched successfully!');
        } else {
            // Test OrderCreated event (default)
            $this->info('Dispatching OrderCreated event...');
            event(new OrderCreated($order));
            $this->info('✓ OrderCreated event dispatched successfully!');
        }

        $this->newLine();
        $this->info('Event has been broadcast. Check the following channels:');
        $this->line('  - kitchen (if order has kitchen items)');
        $this->line('  - bar (if order has bar items)');
        $this->line('  - orders (all staff)');
        $this->line('  - waiter.{waiter_id} (assigned waiter)');
        $this->line('  - dashboard (managers)');
        $this->newLine();
        $this->info('To listen for events, ensure:');
        $this->line('  1. Reverb server is running: php artisan reverb:start');
        $this->line('  2. Frontend is listening with Laravel Echo');

        return 0;
    }
}
