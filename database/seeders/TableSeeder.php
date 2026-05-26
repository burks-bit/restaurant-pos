<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Table;

class TableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $slots = ['A', 'B', 'C', 'D'];

        for ($i = 1; $i <= 40; $i++) {

            // Create parent table
            $parent = Table::create([
                'name' => 'Table ' . $i,
                'capacity' => 4,
                'parent_id' => null
            ]);

            // Create child tables
            foreach ($slots as $slot) {
                Table::create([
                    'name' => 'Table ' . $i . '-' . $slot,
                    'capacity' => 1,
                    'parent_id' => $parent->id
                ]);
            }
        }
    }
}