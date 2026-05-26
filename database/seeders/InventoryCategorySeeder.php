<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\InventoryCategory;

class InventoryCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Ingredient',
                'description' => 'Kitchen Ingredient',
                'status' => 1
            ],
            [
                'name' => 'Kitchen Equipment',
                'description' => 'Kitchen Equipment',
                'status' => 1
            ],
            [
                'name' => 'Dining Equipment',
                'description' => 'Dining Ingredient',
                'status' => 1
            ],
            [
                'name' => 'Drinks',
                'description' => 'Drinks',
                'status' => 1
            ],
        ];

        foreach ($categories as $category) {
            InventoryCategory::create($category);
        }
    }
}
