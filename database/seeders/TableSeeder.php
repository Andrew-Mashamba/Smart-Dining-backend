<?php

namespace Database\Seeders;

use App\Models\Table;
use Illuminate\Database\Seeder;

class TableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Empties the tables table and re-inserts with nomenclature:
     * - Indoor: T + 4 digits (e.g. T0001, T0002)
     * - Outdoor: OT + 3 digits (e.g. OT001, OT002)
     * - Bar: BT + 2 digits (e.g. BT01, BT02)
     * Mix of statuses: mostly available, some occupied/reserved.
     */
    public function run(): void
    {
        // Delete all tables (orders.table_id and guest_sessions.table_id will be set null via FK)
        Table::query()->delete();

        $toInsert = [];

        // Indoor: T0001–T0008 (8 tables), sensible capacities, mostly available
        $indoorCapacities = [2, 4, 4, 6, 2, 4, 6, 4];
        $indoorStatuses = ['available', 'available', 'occupied', 'available', 'available', 'reserved', 'available', 'available'];
        for ($i = 1; $i <= 8; $i++) {
            $toInsert[] = [
                'name' => 'T' . str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'location' => 'indoor',
                'capacity' => $indoorCapacities[$i - 1],
                'status' => $indoorStatuses[$i - 1],
            ];
        }

        // Outdoor: OT001–OT003 (3 tables)
        $outdoorCapacities = [4, 2, 4];
        $outdoorStatuses = ['available', 'occupied', 'available'];
        for ($i = 1; $i <= 3; $i++) {
            $toInsert[] = [
                'name' => 'OT' . str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'location' => 'outdoor',
                'capacity' => $outdoorCapacities[$i - 1],
                'status' => $outdoorStatuses[$i - 1],
            ];
        }

        // Bar: BT01–BT03 (3 seats)
        $barCapacities = [2, 2, 2];
        $barStatuses = ['available', 'available', 'occupied'];
        for ($i = 1; $i <= 3; $i++) {
            $toInsert[] = [
                'name' => 'BT' . str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'location' => 'bar',
                'capacity' => $barCapacities[$i - 1],
                'status' => $barStatuses[$i - 1],
            ];
        }

        foreach ($toInsert as $row) {
            Table::create($row);
        }

        $this->command->info('✓ Tables seeded with new nomenclature');
        $this->command->info('  - 8 indoor (T0001–T0008)');
        $this->command->info('  - 3 outdoor (OT001–OT003)');
        $this->command->info('  - 3 bar (BT01–BT03)');
        $this->command->info('  - Total: 14 tables');
    }
}
