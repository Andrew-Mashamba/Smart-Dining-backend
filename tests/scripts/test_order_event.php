<?php

/**
 * Test script to dispatch OrderCreated event with real data
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Events\OrderCreated;
use App\Models\Order;
use App\Models\Staff;
use App\Models\Table;

echo "Testing OrderCreated Event Broadcast\n";
echo "=====================================\n\n";

try {
    // Find first table and waiter
    $table = Table::first();
    $waiter = Staff::where('role', 'waiter')->first();

    if (! $table || ! $waiter) {
        echo "⚠ Missing required data (table or waiter)\n";
        echo "Please ensure database has been seeded.\n";
        exit(1);
    }

    echo "Creating test order...\n";
    $order = Order::create([
        'table_id' => $table->id,
        'waiter_id' => $waiter->id,
        'order_source' => 'dine_in',
        'status' => 'pending',
        'total' => 75.50,
        'tax' => 7.55,
        'subtotal' => 67.95,
    ]);

    echo "✓ Order created with ID: {$order->id}\n\n";

    echo "Dispatching OrderCreated event...\n";
    event(new OrderCreated($order));
    echo "✓ Event dispatched!\n\n";

    echo "Broadcast Details:\n";
    echo "  Order ID: {$order->id}\n";
    echo "  Table: {$table->name}\n";
    echo "  Waiter: {$waiter->name}\n";
    echo "  Status: {$order->status}\n";
    echo "  Total: \${$order->total}\n\n";

    echo "Channels:\n";
    echo "  • orders (private)\n";
    echo "  • kitchen (private)\n";
    echo "  • bar (private)\n";
    echo "  • waiter.{$waiter->id} (private)\n\n";

    echo "✓ Test completed successfully!\n";
    echo "Check the Reverb server logs to confirm broadcast.\n\n";

} catch (\Exception $e) {
    echo '❌ Error: '.$e->getMessage()."\n";
    echo $e->getTraceAsString()."\n";
    exit(1);
}
