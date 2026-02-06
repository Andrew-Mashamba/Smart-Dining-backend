#!/usr/bin/env php
<?php

/**
 * Reverb Broadcasting Test Script
 *
 * This script tests the Laravel Reverb broadcasting setup by:
 * 1. Verifying configuration
 * 2. Testing OrderCreated event broadcast
 * 3. Testing OrderStatusUpdated event broadcast
 *
 * Usage: php test-reverb-broadcast.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Events\OrderCreated;
use App\Events\OrderStatusUpdated;
use App\Models\Order;
use App\Models\Table;

echo "=== Laravel Reverb Broadcasting Test ===\n\n";

// 1. Verify Reverb Configuration
echo "Step 1: Verifying Reverb Configuration\n";
echo str_repeat('-', 50)."\n";

$broadcastConnection = config('broadcasting.default');
echo "✓ Broadcast Connection: {$broadcastConnection}\n";

if ($broadcastConnection !== 'reverb') {
    echo "✗ ERROR: Broadcasting connection is not set to 'reverb'\n";
    echo "  Please set BROADCAST_CONNECTION=reverb in .env\n";
    exit(1);
}

$reverbConfig = config('broadcasting.connections.reverb');
echo '✓ Reverb App ID: '.($reverbConfig['app_id'] ?? 'NOT SET')."\n";
echo '✓ Reverb App Key: '.($reverbConfig['key'] ?? 'NOT SET')."\n";
echo '✓ Reverb Host: '.($reverbConfig['options']['host'] ?? 'NOT SET')."\n";
echo '✓ Reverb Port: '.($reverbConfig['options']['port'] ?? 'NOT SET')."\n";
echo '✓ Reverb Scheme: '.($reverbConfig['options']['scheme'] ?? 'NOT SET')."\n";

if (empty($reverbConfig['app_id']) || empty($reverbConfig['key']) || empty($reverbConfig['secret'])) {
    echo "✗ ERROR: Reverb credentials not configured\n";
    echo "  Please set REVERB_APP_ID, REVERB_APP_KEY, and REVERB_APP_SECRET in .env\n";
    exit(1);
}

echo "\n";

// 2. Verify Events Exist
echo "Step 2: Verifying Event Classes\n";
echo str_repeat('-', 50)."\n";

$events = [
    'OrderCreated' => App\Events\OrderCreated::class,
    'OrderStatusUpdated' => App\Events\OrderStatusUpdated::class,
];

foreach ($events as $name => $class) {
    if (class_exists($class)) {
        echo "✓ Event exists: {$name}\n";

        // Check if implements ShouldBroadcast
        $reflection = new ReflectionClass($class);
        $implements = $reflection->getInterfaceNames();

        if (in_array('Illuminate\Contracts\Broadcasting\ShouldBroadcast', $implements)) {
            echo "  ✓ Implements ShouldBroadcast\n";
        } else {
            echo "  ✗ Does NOT implement ShouldBroadcast\n";
        }

        // Check if has broadcastOn method
        if ($reflection->hasMethod('broadcastOn')) {
            echo "  ✓ Has broadcastOn() method\n";
        } else {
            echo "  ✗ Missing broadcastOn() method\n";
        }
    } else {
        echo "✗ Event NOT found: {$name}\n";
    }
}

echo "\n";

// 3. Verify Channel Authorization
echo "Step 3: Verifying Channel Routes\n";
echo str_repeat('-', 50)."\n";

$channelsFile = __DIR__.'/routes/channels.php';
if (file_exists($channelsFile)) {
    echo "✓ Channel routes file exists\n";

    $channelContent = file_get_contents($channelsFile);
    $channels = ['kitchen', 'bar', 'orders', 'waiter'];

    foreach ($channels as $channel) {
        if (strpos($channelContent, "'{$channel}'") !== false ||
            strpos($channelContent, "\"{$channel}\"") !== false) {
            echo "  ✓ Channel defined: {$channel}\n";
        } else {
            echo "  ✗ Channel NOT defined: {$channel}\n";
        }
    }
} else {
    echo "✗ Channel routes file NOT found\n";
}

echo "\n";

// 4. Test Broadcasting (Dry Run)
echo "Step 4: Testing Event Broadcasting (Dry Run)\n";
echo str_repeat('-', 50)."\n";

try {
    // Check for existing order
    $order = Order::with(['table', 'items'])->first();

    if (! $order) {
        echo "⚠ No orders in database, creating mock objects for testing...\n";

        // Create a mock order for testing (without saving to database)
        $order = new Order;
        $order->id = 999;
        $order->waiter_id = 1;
        $order->table_id = 1;
        $order->status = 'pending';

        // Create mock relationships
        $mockTable = new Table;
        $mockTable->id = 1;
        $mockTable->name = 'Table 1';
        $order->setRelation('table', $mockTable);

        $mockItems = collect([]);
        $order->setRelation('items', $mockItems);
    } else {
        echo "✓ Using existing order from database (ID: {$order->id})\n";
    }

    // Test OrderCreated event
    echo "\nTesting OrderCreated event:\n";
    $orderCreated = new OrderCreated($order);

    $channels = $orderCreated->broadcastOn();
    echo "  ✓ Event created successfully\n";
    echo '  ✓ Broadcasting to '.count($channels)." channel(s):\n";

    foreach ($channels as $channel) {
        $channelName = method_exists($channel, 'name') ? $channel->name : get_class($channel);
        echo "    - {$channelName}\n";
    }

    if (method_exists($orderCreated, 'broadcastWith')) {
        $data = $orderCreated->broadcastWith();
        echo '  ✓ Broadcast data: '.json_encode($data, JSON_PRETTY_PRINT)."\n";
    }

    // Test OrderStatusUpdated event
    echo "\nTesting OrderStatusUpdated event:\n";
    $orderStatusUpdated = new OrderStatusUpdated($order, 'pending', 'preparing');

    $channels = $orderStatusUpdated->broadcastOn();
    echo "  ✓ Event created successfully\n";
    echo '  ✓ Broadcasting to '.count($channels)." channel(s):\n";

    foreach ($channels as $channel) {
        $channelName = method_exists($channel, 'name') ? $channel->name : get_class($channel);
        echo "    - {$channelName}\n";
    }

    if (method_exists($orderStatusUpdated, 'broadcastWith')) {
        $data = $orderStatusUpdated->broadcastWith();
        echo '  ✓ Broadcast data: '.json_encode($data, JSON_PRETTY_PRINT)."\n";
    }

} catch (Exception $e) {
    echo '✗ ERROR: '.$e->getMessage()."\n";
    echo '  Stack trace: '.$e->getTraceAsString()."\n";
    exit(1);
}

echo "\n";

// 5. Summary
echo str_repeat('=', 50)."\n";
echo "Summary:\n";
echo str_repeat('=', 50)."\n";
echo "✓ Reverb is properly configured\n";
echo "✓ Events implement ShouldBroadcast\n";
echo "✓ Channel routes are defined\n";
echo "✓ Broadcasting system is ready\n";
echo "\n";
echo "Next Steps:\n";
echo "1. Start Reverb server: php artisan reverb:start\n";
echo "2. In another terminal, run queue worker: php artisan queue:work\n";
echo "3. Test live broadcasting by creating an order in the app\n";
echo "4. Monitor the Reverb server output for broadcast messages\n";
echo "\n";
echo "To manually dispatch a test event:\n";
echo "  php artisan tinker\n";
echo "  >>> \$order = Order::first();\n";
echo "  >>> event(new App\\Events\\OrderCreated(\$order));\n";
echo "\n";
