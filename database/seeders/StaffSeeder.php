<?php

namespace Database\Seeders;

use App\Models\Staff;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StaffSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $staff = [
            [
                'name' => 'Admin User',
                'email' => 'admin@seacliff.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'phone_number' => '+255712345601',
                'status' => 'active',
            ],
            [
                'name' => 'John Manager',
                'email' => 'manager@seacliff.com',
                'password' => Hash::make('password'),
                'role' => 'manager',
                'phone_number' => '+255712345602',
                'status' => 'active',
            ],
            [
                'name' => 'Alice Waiter',
                'email' => 'alice.waiter@seacliff.com',
                'password' => Hash::make('password'),
                'role' => 'waiter',
                'phone_number' => '+255712345603',
                'status' => 'active',
            ],
            [
                'name' => 'Bob Waiter',
                'email' => 'bob.waiter@seacliff.com',
                'password' => Hash::make('password'),
                'role' => 'waiter',
                'phone_number' => '+255712345604',
                'status' => 'active',
            ],
            [
                'name' => 'Carol Waiter',
                'email' => 'carol.waiter@seacliff.com',
                'password' => Hash::make('password'),
                'role' => 'waiter',
                'phone_number' => '+255712345605',
                'status' => 'active',
            ],
            [
                'name' => 'David Chef',
                'email' => 'david.chef@seacliff.com',
                'password' => Hash::make('password'),
                'role' => 'chef',
                'phone_number' => '+255712345606',
                'status' => 'active',
            ],
            [
                'name' => 'Eve Chef',
                'email' => 'eve.chef@seacliff.com',
                'password' => Hash::make('password'),
                'role' => 'chef',
                'phone_number' => '+255712345607',
                'status' => 'active',
            ],
            [
                'name' => 'Frank Bartender',
                'email' => 'frank.bartender@seacliff.com',
                'password' => Hash::make('password'),
                'role' => 'bartender',
                'phone_number' => '+255712345608',
                'status' => 'active',
            ],
        ];

        foreach ($staff as $member) {
            Staff::create($member);
        }

        $this->command->info('Staff members seeded successfully!');
        $this->command->info('Default password for all staff: password');
    }
}
