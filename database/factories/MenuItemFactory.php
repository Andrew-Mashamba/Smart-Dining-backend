<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MenuItem>
 */
class MenuItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'category_id' => \App\Models\MenuCategory::factory(),
            'price' => fake()->randomFloat(2, 5, 50),
            'prep_area' => fake()->randomElement(['kitchen', 'bar', 'both']),
            'prep_time_minutes' => fake()->numberBetween(5, 30),
            'status' => 'available',
            'stock_quantity' => 100,
            'unit' => 'pieces',
            'low_stock_threshold' => 10,
        ];
    }

    /**
     * Indicate that the menu item is unavailable.
     */
    public function unavailable(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'unavailable',
        ]);
    }

    /**
     * Indicate that the menu item has low stock.
     */
    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_quantity' => 5,
        ]);
    }
}
