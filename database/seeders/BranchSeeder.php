<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Branch;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Branch::insert([
            [
                'name' => 'Hapag sa Balai',
                'code' => 'HSB',
                'address' => '704 Leveriza St, corner P Quirino Ave, Malate, Manila, 1004 Metro Manila, Philippines',
                'contact' => '09123456789',
                'main' => 1
            ]
        ]);
    }
}
