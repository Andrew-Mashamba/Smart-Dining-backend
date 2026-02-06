<?php

namespace App\Console\Commands;

use App\Events\OrderCreated;
use App\Models\Order;
use Illuminate\Console\Command;

class TestOrderBroadcast extends Command
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
    protected $description = 'Test broadcasting by dispatching an OrderCreated event';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $orderId = $this->argument('order_id');

        if ($orderId) {
            $order = Order::with(['table', 'items'])->find($orderId);

            if (! $order) {
                $this->error("Order with ID {$orderId} not found!");

                return 1;
            }
        } else {
            // Get the first available order
            $order = Order::with(['table', 'items'])->first();

            if (! $order) {
                $this->error('No orders found in database! Create an order first.');

                return 1;
            }
        }

        $this->info("Dispatching OrderCreated event for Order ID: {$order->id}");
        $this->info("Table: {$order->table->name}");
        $this->info("Status: {$order->status}");
        $this->info("Items: {$order->items->count()}");

        // Dispatch the event
        event(new OrderCreated($order));

        $this->info('✓ Event dispatched successfully!');
        $this->info('✓ Check the Reverb server logs and connected clients for the broadcast');

        return 0;
    }
}
