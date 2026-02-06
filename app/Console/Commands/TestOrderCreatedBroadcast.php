<?php

namespace App\Console\Commands;

use App\Events\OrderCreated;
use App\Models\Order;
use Illuminate\Console\Command;

class TestOrderCreatedBroadcast extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:order-broadcast {order_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test OrderCreated event broadcasting via Reverb';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $orderId = $this->argument('order_id');

        if ($orderId) {
            $order = Order::with(['table', 'orderItems'])->find($orderId);

            if (! $order) {
                $this->error("Order with ID {$orderId} not found.");

                return 1;
            }
        } else {
            // Get the latest order or create a test one
            $order = Order::with(['table', 'orderItems'])->latest()->first();

            if (! $order) {
                $this->error('No orders found in the database. Please create an order first.');

                return 1;
            }
        }

        $this->info("Broadcasting OrderCreated event for Order #{$order->order_number}...");
        $this->info("Table: {$order->table->name}");
        $this->info("Status: {$order->status}");
        $this->info("Items: {$order->orderItems->count()}");

        // Dispatch the event
        event(new OrderCreated($order));

        $this->info("\n✓ Event dispatched successfully!");
        $this->info("Channels: orders, kitchen, bar, waiter.{$order->waiter_id}");
        $this->info("\nMake sure Reverb server is running: php artisan reverb:start");

        return 0;
    }
}
