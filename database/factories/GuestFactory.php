<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Guest>
 */
class GuestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'phone_number' => fake()->unique()->numerify('+2637########'),
            'name' => fake()->name(),
            'first_visit_at' => now()->subDays(fake()->numberBetween(1, 365)),
            'last_visit_at' => now(),
            'loyalty_points' => fake()->numberBetween(0, 1000),
        ];
    }
}
