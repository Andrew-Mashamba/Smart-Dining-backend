<?php

namespace Database\Seeders;

use App\Models\Guest;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class GuestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates 50 sample guests with varying loyalty points and visit history
     */
    public function run(): void
    {
        $firstNames = [
            'Andrew', 'Sarah', 'John', 'Mary', 'Peter', 'Grace', 'David', 'Emma',
            'James', 'Linda', 'Michael', 'Patricia', 'Robert', 'Jennifer', 'William',
            'Elizabeth', 'Richard', 'Susan', 'Joseph', 'Jessica', 'Thomas', 'Karen',
            'Christopher', 'Nancy', 'Daniel', 'Lisa', 'Matthew', 'Betty', 'Anthony',
            'Margaret', 'Mark', 'Sandra', 'Donald', 'Ashley', 'Steven', 'Dorothy',
            'Paul', 'Kimberly', 'Joshua', 'Emily', 'Kenneth', 'Donna', 'Kevin',
            'Michelle', 'Brian', 'Carol', 'George', 'Amanda', 'Edward', 'Melissa',
        ];

        $lastNames = [
            'Mashamba', 'Johnson', 'Doe', 'Smith', 'Brown', 'Mwangi', 'Kamau', 'Wilson',
            'Ochieng', 'Kimani', 'Anderson', 'Taylor', 'Thomas', 'Moore', 'Jackson',
            'Martin', 'Lee', 'Thompson', 'White', 'Harris', 'Sanchez', 'Clark',
            'Ramirez', 'Lewis', 'Robinson', 'Walker', 'Young', 'Allen', 'King',
            'Wright', 'Scott', 'Torres', 'Nguyen', 'Hill', 'Flores', 'Green',
            'Adams', 'Nelson', 'Baker', 'Hall', 'Rivera', 'Campbell', 'Mitchell',
            'Carter', 'Roberts', 'Gomez', 'Phillips', 'Evans', 'Turner', 'Diaz',
        ];

        $dietaryOptions = [
            ['no_pork'],
            ['vegetarian'],
            ['vegan'],
            ['gluten_free'],
            ['pescatarian'],
            ['no_shellfish'],
            ['dairy_free'],
            ['nut_allergy'],
        ];

        $guests = [];

        for ($i = 0; $i < 50; $i++) {
            $firstName = $firstNames[$i];
            $lastName = $lastNames[$i];
            $name = "$firstName $lastName";

            // Generate phone number
            $phoneNumber = '+25571'.str_pad(2000001 + $i, 7, '0', STR_PAD_LEFT);

            // Varying visit history and loyalty points
            $monthsAgo = rand(1, 12);
            $daysAgo = rand(1, 30);
            $loyaltyPoints = rand(0, 300);

            // Some guests have preferences, some don't
            $hasPreferences = rand(0, 10) > 3; // 70% have preferences
            $preferences = null;

            if ($hasPreferences) {
                $prefData = [];

                // Random dietary restrictions (40% chance)
                if (rand(0, 10) > 6) {
                    $prefData['dietary'] = $dietaryOptions[array_rand($dietaryOptions)];
                }

                // Random favorite table (30% chance)
                if (rand(0, 10) > 7) {
                    $tableOptions = ['Table 1', 'Table 5', 'Table 10', 'Outdoor', 'Bar Seat 1'];
                    $prefData['favorite_table'] = $tableOptions[array_rand($tableOptions)];
                }

                // Random favorite items (50% chance)
                if (rand(0, 10) > 5) {
                    $itemOptions = [
                        ['Grilled Tilapia', 'Mojito'],
                        ['Vegetable Pasta', 'Fresh Orange Juice'],
                        ['Beef Steak', 'Red Wine (Glass)'],
                        ['Grilled Chicken', 'Fruit Salad'],
                        ['Seafood Platter', 'White Wine (Glass)'],
                        ['Chicken Wings', 'Local Beer'],
                        ['Fish & Chips', 'Calamari', 'Passion Juice'],
                        ['Pilau Rice with Beef', 'Mango Smoothie'],
                        ['Lamb Chops', 'Piña Colada'],
                        ['Butter Chicken', 'Cappuccino'],
                    ];
                    $prefData['favorite_items'] = $itemOptions[array_rand($itemOptions)];
                }

                if (! empty($prefData)) {
                    $preferences = json_encode($prefData);
                }
            }

            $guests[] = [
                'phone_number' => $phoneNumber,
                'name' => $name,
                'first_visit_at' => Carbon::now()->subMonths($monthsAgo),
                'last_visit_at' => Carbon::now()->subDays($daysAgo),
                'loyalty_points' => $loyaltyPoints,
                'preferences' => $preferences,
            ];
        }

        foreach ($guests as $guest) {
            Guest::create($guest);
        }

        $this->command->info('✓ Guest records seeded successfully!');
        $this->command->info('  - 50 guests created with varying visit history');
        $this->command->info('  - Loyalty points range: 0-300');
        $this->command->info('  - Includes dietary preferences and favorites');
    }
}
