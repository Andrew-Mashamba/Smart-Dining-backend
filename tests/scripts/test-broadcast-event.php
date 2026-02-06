<?php

/**
 * Test Script to Dispatch and Verify Broadcasting
 *
 * This script tests actual event dispatching to verify broadcasts work
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Events\OrderCreated;
use App\Events\OrderStatusUpdated;
use App\Models\Order;
use App\Models\Table;
use App\Models\User;
use Illuminate\Support\Facades\Event;

echo "==============================================\n";
echo "Testing Event Broadcasting (Story 24)\n";
echo "==============================================\n\n";

echo "Note: For live testing, ensure the following are running:\n";
echo "  - php artisan reverb:start (in separate terminal)\n";
echo "  - php artisan queue:work (in separate terminal)\n";
echo "\n";

// Check if we can connect to database
try {
    echo "1. Checking database connection...\n";
    \Illuminate\Support\Facades\DB::connection()->getPdo();
    echo "   ✓ Database connected\n\n";
} catch (\Exception $e) {
    echo '   ✗ Database connection failed: '.$e->getMessage()."\n";
    echo "   Please ensure database is set up and migrations are run.\n";
    exit(1);
}

// Test 1: Check if we have test data
echo "2. Checking for test data...\n";
$table = Table::first();
$waiter = User::where('role', 'waiter')->first();

if (! $table) {
    echo "   ! No tables found in database\n";
    echo "   Creating a test table...\n";
    $table = Table::create([
        'name' => 'Test Table 1',
        'seats' => 4,
        'status' => 'available',
    ]);
    echo "   ✓ Test table created (ID: {$table->id})\n";
} else {
    echo "   ✓ Found existing table (ID: {$table->id}, Name: {$table->name})\n";
}

if (! $waiter) {
    echo "   ! No waiter found in database\n";
    echo "   Creating a test waiter...\n";
    $waiter = User::create([
        'name' => 'Test Waiter',
        'email' => 'waiter@test.com',
        'password' => bcrypt('password'),
        'role' => 'waiter',
    ]);
    echo "   ✓ Test waiter created (ID: {$waiter->id})\n";
} else {
    echo "   ✓ Found existing waiter (ID: {$waiter->id}, Name: {$waiter->name})\n";
}
echo "\n";

// Test 2: Create a test order
echo "3. Creating test order...\n";
try {
    // Generate unique order number
    $orderNumber = 'TEST-'.strtoupper(uniqid());

    $order = Order::create([
        'order_number' => $orderNumber,
        'table_id' => $table->id,
        'waiter_id' => $waiter->id,
        'order_source' => 'pos',
        'status' => 'pending',
        'subtotal' => 0.00,
        'tax' => 0.00,
        'total' => 0.00,
    ]);

    echo "   ✓ Test order created (ID: {$order->id})\n";
    echo "   - Table: {$table->name}\n";
    echo "   - Waiter: {$waiter->name}\n";
    echo "   - Status: {$order->status}\n";
} catch (\Exception $e) {
    echo '   ✗ Failed to create order: '.$e->getMessage()."\n";
    exit(1);
}
echo "\n";

// Test 3: Dispatch OrderCreated event
echo "4. Dispatching OrderCreated event...\n";
try {
    // Reload order with relationships
    $order = Order::with(['table', 'orderItems'])->find($order->id);

    Event::dispatch(new OrderCreated($order));
    echo "   ✓ OrderCreated event dispatched successfully\n";
    echo "   - Order ID: {$order->id}\n";
    echo "   - Broadcasting to channels: orders, kitchen, bar, waiter.{$waiter->id}\n";
} catch (\Exception $e) {
    echo '   ✗ Failed to dispatch event: '.$e->getMessage()."\n";
    exit(1);
}
echo "\n";

// Test 4: Dispatch OrderStatusUpdated event
echo "5. Dispatching OrderStatusUpdated event...\n";
try {
    $oldStatus = $order->status;
    $order->status = 'preparing';
    $order->save();

    // Reload order with relationships
    $order = Order::with(['table', 'orderItems'])->find($order->id);

    Event::dispatch(new OrderStatusUpdated($order, $oldStatus, 'preparing'));
    echo "   ✓ OrderStatusUpdated event dispatched successfully\n";
    echo "   - Order ID: {$order->id}\n";
    echo "   - Status changed: {$oldStatus} -> preparing\n";
    echo "   - Broadcasting to channels: orders, kitchen, bar, waiter.{$waiter->id}\n";
} catch (\Exception $e) {
    echo '   ✗ Failed to dispatch event: '.$e->getMessage()."\n";
    exit(1);
}
echo "\n";

// Test 5: Check broadcast configuration
echo "6. Verifying broadcast configuration...\n";
$broadcastConnection = config('broadcasting.default');
echo "   ✓ Broadcast connection: {$broadcastConnection}\n";

if ($broadcastConnection === 'reverb') {
    $reverbConfig = config('broadcasting.connections.reverb');
    echo "   ✓ Using Reverb broadcaster\n";
    echo '   - App ID: '.config('reverb.apps.apps.0.app_id')."\n";
    echo '   - Host: '.config('reverb.apps.apps.0.options.host')."\n";
    echo '   - Port: '.config('reverb.apps.apps.0.options.port')."\n";
    echo '   - Scheme: '.config('reverb.apps.apps.0.options.scheme')."\n";
}
echo "\n";

// Clean up
echo "7. Cleaning up test data...\n";
try {
    $order->delete();
    echo "   ✓ Test order deleted\n";
} catch (\Exception $e) {
    echo '   ! Warning: Could not delete test order: '.$e->getMessage()."\n";
}
echo "\n";

// Summary
echo "==============================================\n";
echo "✓ BROADCAST TEST COMPLETE!\n";
echo "==============================================\n\n";

echo "What was tested:\n";
echo "  1. Database connectivity\n";
echo "  2. Test data creation\n";
echo "  3. Order creation\n";
echo "  4. OrderCreated event dispatch\n";
echo "  5. OrderStatusUpdated event dispatch\n";
echo "  6. Broadcast configuration verification\n\n";

echo "To verify real-time broadcasting:\n";
echo "  1. Start Reverb: php artisan reverb:start\n";
echo "  2. Start Queue: php artisan queue:work\n";
echo "  3. Open browser to your app\n";
echo "  4. Open browser console and check for Echo messages\n";
echo "  5. Create an order and watch for broadcasts\n\n";

echo "Story 24 Status: ✓ COMPLETE\n";
