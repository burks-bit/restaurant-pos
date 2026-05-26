<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Discount;

class DiscountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Discount::insert([
            ['type' => 'senior', 'percentage' => 20],
            ['type' => 'pwd', 'percentage' => 20],
        ]);
    }
}
