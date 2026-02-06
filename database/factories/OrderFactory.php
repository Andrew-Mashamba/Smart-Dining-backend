<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 20, 200);
        $tax = $subtotal * 0.18;
        $total = $subtotal + $tax;

        return [
            'order_number' => 'ORD-'.date('Ymd').'-'.fake()->unique()->numberBetween(1000, 9999),
            'table_id' => \App\Models\Table::factory(),
            'guest_id' => \App\Models\Guest::factory(),
            'waiter_id' => \App\Models\Staff::factory(),
            'order_source' => fake()->randomElement(['pos', 'whatsapp', 'web']),
            'status' => fake()->randomElement(['pending', 'preparing', 'ready', 'delivered', 'paid']),
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
        ];
    }
}
