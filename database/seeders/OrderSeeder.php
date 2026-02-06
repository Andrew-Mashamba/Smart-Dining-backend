<?php

namespace Database\Seeders;

use App\Models\Guest;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Staff;
use App\Models\Table;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates 100 sample orders with various statuses and order items
     */
    public function run(): void
    {
        // Get all necessary data
        $tables = Table::all();
        $guests = Guest::all();
        $waiters = Staff::where('role', 'waiter')->get();
        $menuItems = MenuItem::all();

        if ($tables->isEmpty() || $guests->isEmpty() || $waiters->isEmpty() || $menuItems->isEmpty()) {
            $this->command->error('Error: Required data not found. Please run other seeders first.');

            return;
        }

        $orderStatuses = ['pending', 'preparing', 'ready', 'served', 'paid', 'cancelled'];
        $orderSources = ['walk-in', 'reservation', 'takeaway'];

        $specialInstructions = [
            'No onions please',
            'Extra spicy',
            'Well done',
            'No salt',
            'Allergy: nuts',
            'Medium rare',
            'Extra sauce on the side',
            'Gluten-free preparation',
            null,
            null,
            null, // More nulls to make it more realistic
        ];

        $orders = [];

        for ($i = 0; $i < 100; $i++) {
            // Random date within last 30 days
            $daysAgo = rand(0, 30);
            $hoursAgo = rand(0, 23);
            $createdAt = Carbon::now()->subDays($daysAgo)->subHours($hoursAgo);

            // Weighted status distribution (more recent orders are more likely to be pending/preparing)
            if ($daysAgo < 1) {
                // Today's orders - varied statuses
                $statusWeights = ['pending' => 30, 'preparing' => 25, 'ready' => 15, 'served' => 15, 'paid' => 10, 'cancelled' => 5];
            } elseif ($daysAgo < 7) {
                // Last week - mostly paid or served
                $statusWeights = ['pending' => 5, 'preparing' => 5, 'ready' => 10, 'served' => 20, 'paid' => 55, 'cancelled' => 5];
            } else {
                // Older orders - almost all paid
                $statusWeights = ['pending' => 0, 'preparing' => 0, 'ready' => 0, 'served' => 5, 'paid' => 90, 'cancelled' => 5];
            }

            $status = $this->weightedRandom($statusWeights);

            $order = Order::create([
                'table_id' => $tables->random()->id,
                'guest_id' => rand(0, 10) > 2 ? $guests->random()->id : null, // 80% have guest
                'waiter_id' => $waiters->random()->id,
                'order_source' => $orderSources[array_rand($orderSources)],
                'status' => $status,
                'subtotal' => 0, // Will be updated after adding items
                'tax' => 0,
                'total' => 0,
                'special_instructions' => $specialInstructions[array_rand($specialInstructions)],
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            // Add 1-5 items to each order
            $itemCount = rand(1, 5);
            $orderSubtotal = 0;

            for ($j = 0; $j < $itemCount; $j++) {
                $menuItem = $menuItems->random();
                $quantity = rand(1, 3);
                $unitPrice = $menuItem->price;
                $subtotal = $quantity * $unitPrice;
                $orderSubtotal += $subtotal;

                // Determine prep_status based on order status
                // Valid values: pending, received, preparing, ready
                $prepStatus = match ($status) {
                    'pending' => 'pending',
                    'preparing' => rand(0, 1) ? 'preparing' : 'received',
                    'ready' => 'ready',
                    'served', 'paid' => 'ready',
                    'cancelled' => 'pending',
                    default => 'pending',
                };

                OrderItem::create([
                    'order_id' => $order->id,
                    'menu_item_id' => $menuItem->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                    'special_instructions' => rand(0, 10) > 7 ? $specialInstructions[array_rand($specialInstructions)] : null,
                    'prep_status' => $prepStatus,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }

            // Calculate order totals (tax rate of 18%)
            $taxRate = 0.18;
            $tax = $orderSubtotal * $taxRate;
            $total = $orderSubtotal + $tax;

            $order->update([
                'subtotal' => $orderSubtotal,
                'tax' => $tax,
                'total' => $total,
            ]);

            $orders[] = $order;
        }

        // Count orders by status
        $statusCounts = array_count_values(array_column($orders, 'status'));

        $this->command->info('✓ Orders seeded successfully!');
        $this->command->info('  - 100 orders created with varying statuses');
        $this->command->info('  - Each order has 1-5 menu items');
        $this->command->info('  - Orders distributed over last 30 days');
        $this->command->newLine();
        $this->command->info('📊 Order Status Distribution:');
        foreach ($statusCounts as $status => $count) {
            $this->command->info("  - {$status}: {$count} orders");
        }
    }

    /**
     * Select a random value based on weights
     */
    private function weightedRandom(array $weights): string
    {
        $rand = rand(1, array_sum($weights));
        $sum = 0;

        foreach ($weights as $key => $weight) {
            $sum += $weight;
            if ($rand <= $sum) {
                return $key;
            }
        }

        return array_key_first($weights);
    }
}
