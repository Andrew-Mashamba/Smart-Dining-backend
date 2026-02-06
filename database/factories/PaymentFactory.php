<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => \App\Models\Order::factory(),
            'payment_method' => fake()->randomElement(['cash', 'card', 'mobile', 'gateway']),
            'amount' => fake()->randomFloat(2, 10, 500),
            'status' => fake()->randomElement(['pending', 'completed', 'failed']),
            'transaction_id' => fake()->uuid(),
        ];
    }
}
