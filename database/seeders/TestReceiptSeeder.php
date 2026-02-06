<?php

namespace Database\Seeders;

use App\Models\Guest;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Staff;
use App\Models\Table;
use App\Models\Tip;
use Illuminate\Database\Seeder;

class TestReceiptSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create or get test data
        $guest = Guest::firstOrCreate(
            ['phone_number' => '+27123456789'],
            [
                'name' => 'Test Guest',
            ]
        );

        $table = Table::firstOrCreate(
            ['name' => 'T-01'],
            [
                'capacity' => 4,
                'status' => 'available',
                'location' => 'Main Hall',
            ]
        );

        $waiter = Staff::firstOrCreate(
            ['email' => 'waiter@test.com'],
            [
                'name' => 'John Smith',
                'role' => 'waiter',
                'status' => 'active',
                'password' => bcrypt('password'),
            ]
        );

        // Create menu categories if they don't exist
        $mainCategory = \App\Models\MenuCategory::firstOrCreate(
            ['name' => 'Main Course'],
            ['description' => 'Main course dishes']
        );

        $starterCategory = \App\Models\MenuCategory::firstOrCreate(
            ['name' => 'Starters'],
            ['description' => 'Appetizers and starters']
        );

        $beverageCategory = \App\Models\MenuCategory::firstOrCreate(
            ['name' => 'Beverages'],
            ['description' => 'Drinks and beverages']
        );

        // Create menu items if they don't exist
        $menuItem1 = MenuItem::firstOrCreate(
            ['name' => 'Grilled Salmon'],
            [
                'description' => 'Fresh Atlantic salmon with herbs',
                'price' => 185.00,
                'category_id' => $mainCategory->id,
                'prep_area' => 'kitchen',
                'prep_time_minutes' => 20,
                'status' => 'available',
            ]
        );

        $menuItem2 = MenuItem::firstOrCreate(
            ['name' => 'Caesar Salad'],
            [
                'description' => 'Classic Caesar salad with croutons',
                'price' => 75.00,
                'category_id' => $starterCategory->id,
                'prep_area' => 'kitchen',
                'prep_time_minutes' => 10,
                'status' => 'available',
            ]
        );

        $menuItem3 = MenuItem::firstOrCreate(
            ['name' => 'Mineral Water'],
            [
                'description' => 'Sparkling or still mineral water',
                'price' => 25.00,
                'category_id' => $beverageCategory->id,
                'prep_area' => 'bar',
                'prep_time_minutes' => 2,
                'status' => 'available',
            ]
        );

        // Create an order (order_number will be auto-generated)
        $order = new Order([
            'guest_id' => $guest->id,
            'table_id' => $table->id,
            'waiter_id' => $waiter->id,
            'order_source' => 'pos',
            'status' => 'paid',
            'subtotal' => 0,
            'tax' => 0,
            'total' => 0,
        ]);
        $order->order_number = ''; // Temporary placeholder
        $order->save();

        // Create order items
        OrderItem::create([
            'order_id' => $order->id,
            'menu_item_id' => $menuItem1->id,
            'quantity' => 2,
            'unit_price' => $menuItem1->price,
            'special_instructions' => 'Well done, no onions',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'menu_item_id' => $menuItem2->id,
            'quantity' => 1,
            'unit_price' => $menuItem2->price,
            'special_instructions' => null,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'menu_item_id' => $menuItem3->id,
            'quantity' => 3,
            'unit_price' => $menuItem3->price,
            'special_instructions' => 'Extra ice',
        ]);

        // Recalculate order totals
        $order->calculateTotals();
        $order->refresh();

        // Create payment
        Payment::create([
            'order_id' => $order->id,
            'payment_method' => 'cash',
            'amount' => $order->total + 50.00, // Overpayment for change calculation
            'status' => 'completed',
        ]);

        // Create tip
        Tip::create([
            'order_id' => $order->id,
            'waiter_id' => $waiter->id,
            'amount' => 50.00,
            'tip_method' => 'cash',
        ]);

        $this->command->info('Test order created successfully!');
        $this->command->info('Order ID: '.$order->id);
        $this->command->info('Order Number: '.$order->order_number);
        $this->command->info('You can test the receipt at: /api/orders/'.$order->id.'/receipt');
    }
}
