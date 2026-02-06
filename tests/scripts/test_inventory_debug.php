<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Events\OrderCreated;
use App\Models\InventoryTransaction;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Staff;
use App\Models\Table;
use App\Models\User;
use Illuminate\Support\Facades\DB;

// Clear database
DB::statement('DELETE FROM inventory_transactions');
DB::statement('DELETE FROM order_items');
DB::statement('DELETE FROM orders');
DB::statement('DELETE FROM menu_items');
DB::statement('DELETE FROM menu_categories');
DB::statement('DELETE FROM tables');
DB::statement('DELETE FROM staff');
DB::statement('DELETE FROM users');

// Create test data
$manager = User::create([
    'name' => 'Test Manager',
    'email' => 'manager@test.com',
    'password' => bcrypt('password'),
    'role' => 'manager',
    'status' => 'active',
]);

$waiter = Staff::create([
    'name' => 'Test Waiter',
    'email' => 'waiter@test.com',
    'password' => bcrypt('password'),
    'role' => 'waiter',
    'status' => 'active',
]);

$table = Table::create([
    'name' => 'Table 1',
    'capacity' => 4,
    'status' => 'available',
]);

$category = MenuCategory::create([
    'name' => 'Main Course',
    'status' => 'active',
]);

$menuItem = MenuItem::create([
    'category_id' => $category->id,
    'name' => 'Grilled Chicken',
    'price' => 25.00,
    'stock_quantity' => 10,
    'unit' => 'pieces',
    'low_stock_threshold' => 3,
    'prep_area' => 'kitchen',
    'prep_time_minutes' => 15,
    'status' => 'available',
]);

echo 'Initial stock: '.$menuItem->stock_quantity."\n";

// Create an order
$order = Order::create([
    'table_id' => $table->id,
    'waiter_id' => $waiter->id,
    'order_source' => 'pos',
    'status' => 'pending',
    'subtotal' => 50.00,
    'tax' => 9.00,
    'total' => 59.00,
]);

echo 'Order created with ID: '.$order->id."\n";

OrderItem::create([
    'order_id' => $order->id,
    'menu_item_id' => $menuItem->id,
    'quantity' => 2,
    'unit_price' => 25.00,
    'subtotal' => 50.00,
    'prep_status' => 'pending',
]);

echo "Order item created\n";

// Reload order with relationships
$order = $order->fresh(['orderItems.menuItem', 'table']);

// Check stock before event
$menuItem->refresh();
echo 'Stock before event: '.$menuItem->stock_quantity."\n";

// Trigger the event
echo "Firing OrderCreated event...\n";
event(new OrderCreated($order));

// Check stock after event (with sync queue, should be immediate)
$menuItem->refresh();
echo 'Stock after event: '.$menuItem->stock_quantity."\n";

// Check transactions
$transactions = InventoryTransaction::where('menu_item_id', $menuItem->id)->get();
echo 'Number of transactions: '.$transactions->count()."\n";
foreach ($transactions as $transaction) {
    echo "  - Transaction ID: {$transaction->id}, Quantity: {$transaction->quantity}, Type: {$transaction->transaction_type}\n";
}

echo "\nExpected stock: 8 (10 - 2)\n";
echo 'Actual stock: '.$menuItem->stock_quantity."\n";
