<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ExpenseCategory;

class ExpenseCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $expense_categories = [
            [
                'name' => 'Kitchen',
                'status' => 1
            ],
            [
                'name' => 'Utilities',
                'status' => 1
            ],
            [
                'name' => 'Payroll',
                'status' => 1
            ],
            [
                'name' => 'Marketing',
                'status' => 1
            ],
        ];

        foreach ($expense_categories as $category) {
            ExpenseCategory::create($category);
        }
    }
}
