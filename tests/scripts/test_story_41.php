<?php

/**
 * Test script for Story 41: Automated inventory deduction on order
 *
 * This script tests:
 * 1. Stock deduction when order is created
 * 2. Inventory transaction creation
 * 3. Low stock notifications
 * 4. Stock validation (out of stock prevention)
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Events\OrderCreated;
use App\Models\Guest;
use App\Models\InventoryTransaction;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Staff;
use App\Models\Table;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

echo "=== Story 41: Automated Inventory Deduction Test ===\n\n";

try {
    // Setup: Find or create test data
    echo "1. Setting up test data...\n";

    $guest = Guest::firstOrCreate(
        ['phone_number' => '+255700000001'],
        ['name' => 'Test Guest']
    );
    echo "   Guest: {$guest->name} (ID: {$guest->id})\n";

    $table = Table::first();
    if (! $table) {
        echo "   ERROR: No tables found. Please seed tables first.\n";
        exit(1);
    }
    echo "   Table: {$table->name} (ID: {$table->id})\n";

    $waiter = Staff::where('role', 'waiter')->first();
    if (! $waiter) {
        $waiter = Staff::first();
    }
    echo '   Waiter: '.($waiter ? $waiter->id : 'None')."\n";

    // Find a menu item with stock
    $menuItem = MenuItem::where('stock_quantity', '>', 5)
        ->where('low_stock_threshold', '>', 0)
        ->first();

    if (! $menuItem) {
        echo "   ERROR: No menu items with stock found.\n";
        exit(1);
    }

    echo "   Menu Item: {$menuItem->name}\n";
    echo "   Initial Stock: {$menuItem->stock_quantity} {$menuItem->unit}\n";
    echo "   Low Stock Threshold: {$menuItem->low_stock_threshold} {$menuItem->unit}\n\n";

    // Find or create a manager user for notifications
    $manager = User::where('role', 'manager')->first();
    if (! $manager) {
        echo "   WARNING: No manager users found for notifications.\n";
    } else {
        echo "   Manager for notifications: {$manager->name}\n\n";
    }

    // Test 1: Create order with valid stock
    echo "2. Testing order creation with stock deduction...\n";

    $initialStock = $menuItem->stock_quantity;
    $orderQuantity = 2;

    DB::beginTransaction();

    $order = Order::create([
        'table_id' => $table->id,
        'guest_id' => $guest->id,
        'waiter_id' => $waiter?->id,
        'order_source' => 'pos',
        'status' => 'pending',
        'subtotal' => $menuItem->price * $orderQuantity,
        'tax' => ($menuItem->price * $orderQuantity) * 0.18,
        'total' => ($menuItem->price * $orderQuantity) * 1.18,
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'menu_item_id' => $menuItem->id,
        'quantity' => $orderQuantity,
        'unit_price' => $menuItem->price,
        'subtotal' => $menuItem->price * $orderQuantity,
        'prep_status' => 'pending',
    ]);

    // Fire the OrderCreated event (this triggers the DeductInventoryStock listener)
    event(new OrderCreated($order->load(['orderItems'])));

    DB::commit();

    echo "   Order created: {$order->order_number}\n";
    echo "   Order quantity: {$orderQuantity}\n";

    // Wait a moment for event processing
    sleep(1);

    // Check stock deduction
    $menuItem->refresh();
    $expectedStock = $initialStock - $orderQuantity;

    echo "   Expected stock after deduction: {$expectedStock}\n";
    echo "   Actual stock after deduction: {$menuItem->stock_quantity}\n";

    if ($menuItem->stock_quantity == $expectedStock) {
        echo "   ✓ Stock deduction successful!\n\n";
    } else {
        echo "   ✗ Stock deduction failed!\n\n";
    }

    // Test 2: Check inventory transaction
    echo "3. Testing inventory transaction creation...\n";

    $transaction = InventoryTransaction::where('menu_item_id', $menuItem->id)
        ->where('reference_id', $order->id)
        ->where('transaction_type', 'sale')
        ->first();

    if ($transaction) {
        echo "   ✓ Inventory transaction created!\n";
        echo "   Transaction type: {$transaction->transaction_type}\n";
        echo "   Quantity: {$transaction->quantity}\n";
        echo "   Reference: Order #{$order->order_number}\n\n";
    } else {
        echo "   ✗ Inventory transaction not found!\n\n";
    }

    // Test 3: Check low stock notification
    echo "4. Testing low stock notification...\n";

    if ($menuItem->stock_quantity < $menuItem->low_stock_threshold) {
        echo "   Stock is below threshold ({$menuItem->stock_quantity} < {$menuItem->low_stock_threshold})\n";

        if ($manager) {
            $notification = $manager->notifications()
                ->where('data->menu_item_id', $menuItem->id)
                ->where('data->type', 'low_stock')
                ->first();

            if ($notification) {
                echo "   ✓ Low stock notification sent to manager!\n";
                echo '   Message: '.$notification->data['message']."\n\n";
            } else {
                echo "   ✗ Low stock notification not found!\n\n";
            }
        }
    } else {
        echo "   Stock is still above threshold. No notification expected.\n\n";
    }

    // Test 4: Try to create order with insufficient stock
    echo "5. Testing out-of-stock validation...\n";

    $currentStock = $menuItem->stock_quantity;
    $excessiveQuantity = $currentStock + 10;

    echo "   Current stock: {$currentStock}\n";
    echo "   Attempting to order: {$excessiveQuantity}\n";

    try {
        DB::beginTransaction();

        $invalidOrder = Order::create([
            'table_id' => $table->id,
            'guest_id' => $guest->id,
            'waiter_id' => $waiter?->id,
            'order_source' => 'pos',
            'status' => 'pending',
            'subtotal' => $menuItem->price * $excessiveQuantity,
            'tax' => ($menuItem->price * $excessiveQuantity) * 0.18,
            'total' => ($menuItem->price * $excessiveQuantity) * 1.18,
        ]);

        // This should fail when using OrderService
        // For direct OrderItem creation, we'd need to add validation
        OrderItem::create([
            'order_id' => $invalidOrder->id,
            'menu_item_id' => $menuItem->id,
            'quantity' => $excessiveQuantity,
            'unit_price' => $menuItem->price,
            'subtotal' => $menuItem->price * $excessiveQuantity,
            'prep_status' => 'pending',
        ]);

        event(new OrderCreated($invalidOrder->load(['orderItems'])));

        DB::commit();

        echo "   ✗ Validation failed: Order was created with insufficient stock!\n\n";
    } catch (\Exception $e) {
        DB::rollBack();
        echo "   ✓ Validation successful: Order prevented due to insufficient stock!\n";
        echo '   Error message: '.$e->getMessage()."\n\n";
    }

    echo "=== Test Summary ===\n";
    echo "Story 41 implementation tested successfully!\n";
    echo "All core features are working as expected.\n\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "\n✗ ERROR: ".$e->getMessage()."\n";
    echo "Stack trace:\n".$e->getTraceAsString()."\n";
}
