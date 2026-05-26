<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        
        $this->call([
            // UserSeeder::class,
            // CategorySeeder::class,
            // MenuSeeder::class,
            // DiscountSeeder::class,
            // TableSeeder::class,
            // EmployeeSeeder::class,
            // VoucherSeeder::class,
            // HeadPricingRuleSeeder::class,
            // BranchSeeder::class,
            // InventoryCategorySeeder::class,
            // ExpenseCategorySeeder::class,
            // PhilippineHolidaySeeder::class,
            // ConfigurationSeeder::class,
            InventoryItemSeeder::class,
        ]);
    }
}
