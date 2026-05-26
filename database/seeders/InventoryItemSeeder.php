<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\InventoryCategory;

class InventoryItemSeeder extends Seeder
{
    public function run(): void
    {
        $drinksCategory = InventoryCategory::where('name', 'Drinks')->first();

        if (!$drinksCategory) {
            throw new \Exception("Category 'Drinks' not found. Please seed it first.");
        }

        $items = [
            [
                'name' => 'Coke in Can',
                'unit' => 'can',
                'unit_price' => 35,
            ],
            [
                'name' => 'Coke 1.5 Liters',
                'unit' => 'bottle',
                'unit_price' => 100,
            ],
            [
                'name' => 'Royal in Can',
                'unit' => 'can',
                'unit_price' => 35,
            ],
            [
                'name' => '7Up Bottle',
                'unit' => 'bottle',
                'unit_price' => 10,
            ],
        ];

        $userId = 1;

        foreach ($items as $item) {
            $inventoryItem = InventoryItem::updateOrCreate(
                ['name' => $item['name']],
                [
                    'category_id'      => $drinksCategory->id,
                    'type'             => 'consumable',
                    'unit'             => $item['unit'],
                    'current_quantity' => 100,
                    'unit_price'       => $item['unit_price'],
                    'orderable'        => 1,
                    'status'           => 1,
                    'created_by'       => $userId,
                    'updated_by'       => null,
                ]
            );

            InventoryMovement::create([
                'inventory_item_id' => $inventoryItem->id,
                'type'              => 'stockin',
                'quantity'          => 100,
                'unit_price'        => $item['unit_price'],
                'note'              => 'Initial stock',
                'created_by'        => $userId,
                'updated_by'        => null,
            ]);
        }
    }
}