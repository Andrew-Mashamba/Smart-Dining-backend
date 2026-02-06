<?php

namespace Database\Seeders;

use App\Events\OrderCreated;
use App\Models\Guest;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Staff;
use App\Models\Table;
use Illuminate\Database\Seeder;

class ReverbTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates test data and broadcasts an OrderCreated event for testing Reverb.
     */
    public function run(): void
    {
        $this->command->info('Creating test data for Reverb broadcasting...');

        // Use existing guest or create new one
        $guest = Guest::first();
        if (! $guest) {
            $guest = Guest::create([
                'phone_number' => '+1234567890',
                'name' => 'Test Guest',
            ]);
            $this->command->info("✓ Guest created: {$guest->name}");
        } else {
            $this->command->info("✓ Using existing guest: {$guest->name}");
        }

        // Use existing table or create new one
        $table = Table::first();
        if (! $table) {
            $table = Table::create([
                'name' => 'T-01',
                'capacity' => 4,
                'status' => 'available',
            ]);
            $this->command->info("✓ Table created: {$table->name}");
        } else {
            $this->command->info("✓ Using existing table: {$table->name}");
        }

        // Use existing waiter or create new one
        $waiter = Staff::where('role', 'waiter')->first();
        if (! $waiter) {
            $waiter = Staff::create([
                'name' => 'Test Waiter',
                'email' => 'waiter@test.com',
                'phone_number' => '+1234567891',
                'role' => 'waiter',
                'shift_start' => '09:00:00',
                'shift_end' => '17:00:00',
                'status' => 'active',
            ]);
            $this->command->info("✓ Waiter created: {$waiter->name}");
        } else {
            $this->command->info("✓ Using existing waiter: {$waiter->name}");
        }

        // Use existing menu item or create new one
        $menuItem = MenuItem::first();
        if (! $menuItem) {
            $menuItem = MenuItem::create([
                'name' => 'Test Burger',
                'description' => 'Delicious test burger',
                'price' => 12.99,
                'category' => 'food',
                'preparation_time' => 15,
                'available' => true,
            ]);
            $this->command->info("✓ Menu item created: {$menuItem->name}");
        } else {
            $this->command->info("✓ Using existing menu item: {$menuItem->name}");
        }

        // Create a test order
        $order = Order::create([
            'table_id' => $table->id,
            'guest_id' => $guest->id,
            'waiter_id' => $waiter->id,
            'order_source' => 'waiter',
            'status' => 'pending',
            'subtotal' => 0,
            'tax' => 0,
            'total' => 0,
        ]);
        $this->command->info("✓ Order created: {$order->order_number}");

        // Create order items
        OrderItem::create([
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
            'quantity' => 2,
            'price' => $menuItem->price,
            'subtotal' => $menuItem->price * 2,
            'status' => 'pending',
            'destination' => 'kitchen',
        ]);
        $this->command->info('✓ Order item created');

        // Calculate totals
        $order->calculateTotals();
        $this->command->info('✓ Order totals calculated');

        // Dispatch the OrderCreated event
        $this->command->info("\n🚀 Broadcasting OrderCreated event...");
        event(new OrderCreated($order));

        $this->command->info("\n✅ Test data created and event broadcasted successfully!");
        $this->command->info("Order ID: {$order->id}");
        $this->command->info("Order Number: {$order->order_number}");
        $this->command->info("Table: {$table->name}");
        $this->command->info("Status: {$order->status}");
        $this->command->info("Total: £{$order->total}");
        $this->command->info("\n📡 Event broadcasted to channels:");
        $this->command->info('  - orders');
        $this->command->info('  - kitchen');
        $this->command->info('  - bar');
        $this->command->info("  - waiter.{$waiter->id}");
        $this->command->info("\n💡 Make sure Reverb server is running: php artisan reverb:start");
    }
}
