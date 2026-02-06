<?php

/**
 * Test script for Reverb broadcasting
 *
 * This script creates test data and dispatches an OrderCreated event
 * to verify that Reverb broadcasting is working correctly.
 *
 * Usage: php tests/test-reverb-broadcast.php
 */

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Testing Reverb Broadcasting ===\n\n";

try {
    // Get or create a table
    $table = App\Models\Table::first();
    if (! $table) {
        echo "Creating test table...\n";
        $table = App\Models\Table::create([
            'name' => 'Test Table 1',
            'capacity' => 4,
            'status' => 'available',
            'qr_code' => 'TEST-QR-001',
            'location' => 'Main Hall',
        ]);
        echo "✓ Table created: {$table->name} (ID: {$table->id})\n";
    } else {
        echo "Using existing table: {$table->name} (ID: {$table->id})\n";
    }

    // Get or create a staff member
    $staff = App\Models\Staff::where('position', 'waiter')->first();
    if (! $staff) {
        $staff = App\Models\Staff::first();
    }

    if (! $staff) {
        echo "Creating test staff member...\n";
        $staff = App\Models\Staff::create([
            'name' => 'Test Waiter',
            'email' => 'waiter@test.com',
            'phone' => '555-0100',
            'position' => 'waiter',
            'hourly_rate' => 15000,
            'is_active' => true,
        ]);
        echo "✓ Staff created: {$staff->name} (ID: {$staff->id})\n";
    } else {
        echo "Using existing staff: {$staff->name} (ID: {$staff->id})\n";
    }

    // Create an order
    echo "\nCreating test order...\n";
    $order = App\Models\Order::create([
        'table_id' => $table->id,
        'waiter_id' => $staff->id,
        'status' => 'pending',
        'order_source' => 'pos',
        'subtotal' => 45000,
        'tax' => 5000,
        'total' => 50000,
        'special_instructions' => 'This is a test order for Reverb broadcasting',
    ]);

    echo "✓ Order created: {$order->order_number} (ID: {$order->id})\n";
    echo "  Table: {$table->name}\n";
    echo "  Waiter: {$staff->name}\n";
    echo '  Total: Rp '.number_format($order->total, 0, ',', '.')."\n";

    // Dispatch the OrderCreated event
    echo "\nDispatching OrderCreated event...\n";
    event(new App\Events\OrderCreated($order->load(['table', 'orderItems'])));

    echo "✓ OrderCreated event dispatched successfully!\n";
    echo "\n=== Test Complete ===\n";
    echo "\nThe event has been broadcast to the following channels:\n";
    echo "  - orders\n";
    echo "  - kitchen\n";
    echo "  - bar\n";
    echo "  - waiter.{$staff->id}\n";
    echo "\nCheck your Reverb server logs to confirm the broadcast.\n";
    echo "You can also open /test-broadcast in your browser to see live events.\n";

} catch (Exception $e) {
    echo "\n✗ Error: {$e->getMessage()}\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString()."\n";
    exit(1);
}
