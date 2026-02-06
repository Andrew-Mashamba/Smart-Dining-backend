<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Runs all seeders in the correct logical order:
     * 1. Settings (required by other seeders)
     * 2. Staff (creates users with roles)
     * 3. Menu (categories and items)
     * 4. Tables
     * 5. Guests
     * 6. Orders (depends on all above)
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('🌱 Starting database seeding...');
        $this->command->info('');

        // Seed in logical order - dependencies first
        $this->call([
            SettingsSeeder::class,      // 1. Settings first (needed for calculations)
            RoleAndUserSeeder::class,   // 2. Staff/Users (needed for orders)
            MenuSeeder::class,          // 3. Menu categories and items (needed for orders)
            TableSeeder::class,         // 4. Tables (needed for orders)
            GuestSeeder::class,         // 5. Guests (needed for orders)
            OrderSeeder::class,         // 6. Orders last (depends on all above)
        ]);

        $this->command->info('');
        $this->command->info('✅ Database seeding completed successfully!');
        $this->command->info('');

        $this->command->info('📊 Summary:');
        $this->command->info('  ✓ Settings: Comprehensive system configuration');
        $this->command->info('  ✓ Staff: 8 members (1 admin, 1 manager, 3 waiters, 2 chefs, 1 bartender)');
        $this->command->info('  ✓ Menu: 5 categories with 42 menu items');
        $this->command->info('  ✓ Tables: 20 tables (10 indoor, 6 outdoor, 4 bar seats)');
        $this->command->info('  ✓ Guests: 50 guests with loyalty points and preferences');
        $this->command->info('  ✓ Orders: 100 orders with various statuses and items');

        $this->command->info('');
        $this->command->info('🔑 Login Credentials:');
        $this->command->info('  Admin:    admin@seacliff.com / password');
        $this->command->info('  Manager:  manager@seacliff.com / password');
        $this->command->info('  Waiter:   alice.waiter@seacliff.com / password');
        $this->command->info('  Chef:     david.chef@seacliff.com / password');
        $this->command->info('  Bartender: frank.bartender@seacliff.com / password');

        $this->command->info('');
        $this->command->info('💡 Tip: Run "php artisan migrate:fresh --seed" to reset and reseed the database');
        $this->command->info('');
    }
}
