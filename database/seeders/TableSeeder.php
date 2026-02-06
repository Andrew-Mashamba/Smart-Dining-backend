<?php

namespace Database\Seeders;

use App\Models\Table;
use Illuminate\Database\Seeder;

class TableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates 20 tables across indoor, outdoor, and bar sections
     */
    public function run(): void
    {
        $tables = [];

        // Indoor Tables (10 tables)
        for ($i = 1; $i <= 10; $i++) {
            $tables[] = [
                'name' => "Table $i",
                'location' => 'indoor',
                'capacity' => ($i % 3 === 0) ? 6 : (($i % 2 === 0) ? 4 : 2),
                'status' => 'available',
            ];
        }

        // Outdoor Tables (6 tables)
        for ($i = 11; $i <= 16; $i++) {
            $tables[] = [
                'name' => "Table $i (Outdoor)",
                'location' => 'outdoor',
                'capacity' => ($i % 2 === 0) ? 4 : 2,
                'status' => 'available',
            ];
        }

        // Bar Seats (4 tables/seats)
        for ($i = 1; $i <= 4; $i++) {
            $tables[] = [
                'name' => "Bar Seat $i",
                'location' => 'bar',
                'capacity' => 2,
                'status' => 'available',
            ];
        }

        foreach ($tables as $table) {
            Table::create($table);
        }

        $this->command->info('✓ Tables seeded successfully!');
        $this->command->info('  - 10 indoor tables');
        $this->command->info('  - 6 outdoor tables');
        $this->command->info('  - 4 bar seats');
        $this->command->info('  - Total: 20 tables');
    }
}
