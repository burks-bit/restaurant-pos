<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Configuration;

class ConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $configurations = [
            [
                'name' => 'Discounts (SC/PWD)',
                'description' => 'is discount enabled',
                'value' => 0,
                'status' => 0
            ],
        ];

        foreach ($configurations as $config) {
            Configuration::create($config);
        }
    }
}
