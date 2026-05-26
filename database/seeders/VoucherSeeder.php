<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Voucher;
use Carbon\Carbon;

class VoucherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $expiration = Carbon::create(2026, 3, 30);

        // 100 pcs of 10% discount
        for ($i = 1; $i <= 100; $i++) {
            Voucher::create([
                'control_no' => 'VC' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'type'       => '10% Discount',
                'status'     => 'available',
                'validity' => $expiration,
            ]);
        }

        // 20 pcs of Free Meal (with 2 companions)
        for ($i = 101; $i <= 120; $i++) {
            Voucher::create([
                'control_no' => 'VC' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'type'       => 'Free Meal (Bring 2 Companions)',
                'status'     => 'available',
                'validity' => $expiration,
            ]);
        }

        // 10 pcs of Free Meal
        for ($i = 121; $i <= 130; $i++) {
            Voucher::create([
                'control_no' => 'VC' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'type'       => 'Free Meal',
                'status'     => 'available',
                'validity' => $expiration,
            ]);
        }

    }
}
