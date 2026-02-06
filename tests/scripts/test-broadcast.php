<?php

/**
 * Test script to verify Laravel Reverb broadcasting setup
 *
 * This script tests the OrderCreated event broadcasting functionality.
 * Run this after starting the Reverb server with: php artisan reverb:start
 *
 * Usage: php test-broadcast.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Events\OrderCreated;
use App\Models\Order;

echo "=== Laravel Reverb Broadcasting Test ===\n\n";

// Check if broadcast connection is set to reverb
$broadcastConnection = config('broadcasting.default');
echo "Broadcast Connection: {$broadcastConnection}\n";

if ($broadcastConnection !== 'reverb') {
    echo "❌ ERROR: Broadcast connection is not set to 'reverb'\n";
    echo "Please set BROADCAST_CONNECTION=reverb in your .env file\n";
    exit(1);
}

// Check Reverb configuration
$reverbKey = config('broadcasting.connections.reverb.key');
$reverbAppId = config('broadcasting.connections.reverb.app_id');
$reverbHost = config('broadcasting.connections.reverb.options.host');
$reverbPort = config('broadcasting.connections.reverb.options.port');

echo "Reverb App ID: {$reverbAppId}\n";
echo "Reverb Host: {$reverbHost}:{$reverbPort}\n";
echo 'Reverb Key: '.substr($reverbKey, 0, 10)."...\n\n";

// Try to find a test order or create one
echo "Looking for a test order...\n";

$order = Order::with(['table', 'orderItems'])->first();

if (! $order) {
    echo "❌ No orders found in database. Please create an order first.\n";
    echo "You can create a test order through the application or seeder.\n";
    exit(1);
}

echo "✓ Found Order ID: {$order->id}\n";
echo "  Table: {$order->table->name}\n";
echo "  Status: {$order->status}\n";
echo "  Items: {$order->orderItems->count()}\n\n";

// Dispatch the OrderCreated event
echo "Dispatching OrderCreated event...\n";

try {
    event(new OrderCreated($order));
    echo "✓ Event dispatched successfully!\n\n";

    echo "=== Test Complete ===\n";
    echo "If Reverb server is running, the event should be broadcast to:\n";
    echo "  - private-orders\n";
    echo "  - private-kitchen\n";
    echo "  - private-bar\n";
    echo "  - private-waiter.{$order->waiter_id}\n\n";

    echo "Check the Reverb server console for broadcast messages.\n";
    echo "You can also listen to these channels in your browser console using:\n";
    echo "  Echo.private('orders').listen('OrderCreated', (e) => console.log(e));\n";

} catch (Exception $e) {
    echo "❌ ERROR: Failed to dispatch event\n";
    echo 'Error: '.$e->getMessage()."\n";
    exit(1);
}
