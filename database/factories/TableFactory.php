<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Table>
 */
class TableFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Table '.fake()->numberBetween(1, 50),
            'location' => fake()->randomElement(['Main Floor', 'Patio', 'Bar Area', 'Private Room']),
            'capacity' => fake()->numberBetween(2, 8),
            'status' => fake()->randomElement(['available', 'occupied', 'reserved']),
        ];
    }
}
