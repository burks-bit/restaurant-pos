<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\HeadPricingRule;

class HeadPricingRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Free for height below 3 feet
        HeadPricingRule::create([
            'label' => 'Kids 3ft below',
            'min_height' => 0,
            'max_height' => 3,
            'price' => 0,
            'is_active' => 1
        ]);

        HeadPricingRule::create([
            'label' => 'Kids 3ft above',
            'min_height' => 0,
            'max_height' => 3,
            'price' => '174.50',
            'is_active' => 1
        ]);

        // Full price for age 7 and up
        HeadPricingRule::create([
            'label' => 'Adult',
            'min_age' => 7,
            'max_age' => null, // no max
            'price' => 349,
            'is_active' => 1
        ]);
    }
}
