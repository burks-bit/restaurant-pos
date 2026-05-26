<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PaymentMethod;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $methods = [

            /*
            |--------------------------------------------------------------------------
            | CASH
            |--------------------------------------------------------------------------
            */
            [
                'name' => 'Cash',
                'code' => 'cash',
                'type' => 'cash',
                'requires_reference' => false,
                'is_cash' => true,
                'is_active' => true,
            ],

            /*
            |--------------------------------------------------------------------------
            | E-WALLETS (PH)
            |--------------------------------------------------------------------------
            */
            [
                'name' => 'GCash',
                'code' => 'gcash',
                'type' => 'digital',
                'requires_reference' => true,
                'is_cash' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Maya',
                'code' => 'maya',
                'type' => 'digital',
                'requires_reference' => true,
                'is_cash' => false,
                'is_active' => true,
            ],
            [
                'name' => 'GrabPay',
                'code' => 'grabpay',
                'type' => 'digital',
                'requires_reference' => true,
                'is_cash' => false,
                'is_active' => true,
            ],

            /*
            |--------------------------------------------------------------------------
            | BANK TRANSFERS (COMMON PH BANKS)
            |--------------------------------------------------------------------------
            */
            [
                'name' => 'BDO',
                'code' => 'bdo',
                'type' => 'bank',
                'requires_reference' => true,
                'is_cash' => false,
                'is_active' => true,
            ],
            [
                'name' => 'BPI',
                'code' => 'bpi',
                'type' => 'bank',
                'requires_reference' => true,
                'is_cash' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Metrobank',
                'code' => 'metrobank',
                'type' => 'bank',
                'requires_reference' => true,
                'is_cash' => false,
                'is_active' => true,
            ],
            [
                'name' => 'UnionBank',
                'code' => 'unionbank',
                'type' => 'bank',
                'requires_reference' => true,
                'is_cash' => false,
                'is_active' => true,
            ],
            [
                'name' => 'LandBank',
                'code' => 'landbank',
                'type' => 'bank',
                'requires_reference' => true,
                'is_cash' => false,
                'is_active' => true,
            ],

            /*
            |--------------------------------------------------------------------------
            | CARD PAYMENTS
            |--------------------------------------------------------------------------
            */
            [
                'name' => 'Credit Card',
                'code' => 'credit_card',
                'type' => 'card',
                'requires_reference' => true,
                'is_cash' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Debit Card',
                'code' => 'debit_card',
                'type' => 'card',
                'requires_reference' => true,
                'is_cash' => false,
                'is_active' => true,
            ],

            /*
            |--------------------------------------------------------------------------
            | OTHERS
            |--------------------------------------------------------------------------
            */
            [
                'name' => 'Others',
                'code' => 'others',
                'type' => 'other',
                'requires_reference' => false,
                'is_cash' => false,
                'is_active' => true,
            ],
        ];

        foreach ($methods as $method) {
            PaymentMethod::updateOrCreate(
                ['code' => $method['code']],
                $method
            );
        }
    }
}