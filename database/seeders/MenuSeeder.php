<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Menu;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $menus = [
            // Main dishes
            [
                'category_id' => 1,
                'items' => [
                    ['name' => 'Chicken Adobo', 'price' => 150],
                    ['name' => 'Beef Kare-Kare', 'price' => 220],
                    ['name' => 'Grilled Bangus', 'price' => 180],
                ],
            ],

            // Drinks
            [
                'category_id' => 2,
                'items' => [
                    ['name' => 'Iced Tea', 'price' => 50],
                    ['name' => 'Buko Juice', 'price' => 70],
                    ['name' => 'Coffee', 'price' => 60],
                ],
            ],

            // Desserts
            [
                'category_id' => 3,
                'items' => [
                    ['name' => 'Leche Flan', 'price' => 90],
                    ['name' => 'Halo-Halo', 'price' => 120],
                    ['name' => 'Bibingka', 'price' => 100],
                ],
            ],

            // Snacks
            [
                'category_id' => 4,
                'items' => [
                    ['name' => 'Turon', 'price' => 40],
                    ['name' => 'Siomai', 'price' => 60],
                    ['name' => 'French Fries', 'price' => 80],
                ],
            ],
        ];

        foreach ($menus as $group) {
            foreach ($group['items'] as $item) {
                Menu::create([
                    'category_id'  => $group['category_id'],
                    'name'         => $item['name'],
                    'price'        => $item['price'],
                    'is_available' => 1,
                ]);
            }
        }
    }
}
