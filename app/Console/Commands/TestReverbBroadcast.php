<?php

namespace App\Console\Commands;

use App\Events\OrderCreated;
use App\Events\OrderStatusUpdated;
use App\Models\Order;
use Illuminate\Console\Command;

class TestReverbBroadcast extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reverb:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Reverb broadcasting with OrderCreated and OrderStatusUpdated events';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Reverb Broadcasting...');
        $this->newLine();

        // Get a test order or create one
        $order = Order::with(['table', 'orderItems'])->first();

        if (! $order) {
            $this->error('No orders found in database. Please create an order first.');

            return 1;
        }

        $this->info("Using Order ID: {$order->id}");
        $this->info("Table: {$order->table->name}");
        $this->info("Current Status: {$order->status}");
        $this->newLine();

        // Test OrderCreated event
        $this->info('Broadcasting OrderCreated event...');
        event(new OrderCreated($order));
        $this->info('✓ OrderCreated event dispatched');
        $this->newLine();

        // Test OrderStatusUpdated event
        $this->info('Broadcasting OrderStatusUpdated event...');
        $oldStatus = $order->status;
        $newStatus = $oldStatus === 'pending' ? 'preparing' : 'pending';
        event(new OrderStatusUpdated($order, $oldStatus, $newStatus));
        $this->info('✓ OrderStatusUpdated event dispatched');
        $this->newLine();

        $this->info('Events broadcasted successfully!');
        $this->info('Check the Reverb server logs to verify the broadcasts.');
        $this->info('Channels: orders, kitchen, bar, waiter.'.$order->waiter_id);

        return 0;
    }
}
