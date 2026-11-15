<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $types = [
            ['name' => 'SSS', 'description' => 'Social Security System contribution'],
            ['name' => 'Pag-Ibig', 'description' => 'Pag-IBIG Fund contribution'],
            ['name' => 'PHIC', 'description' => 'Philippine Health Insurance Corporation contribution'],
        ];

        foreach ($types as $type) {
            $exists = DB::table('deduction_types')->where('name', $type['name'])->exists();

            if (!$exists) {
                DB::table('deduction_types')->insert([
                    'name' => $type['name'],
                    'description' => $type['description'],
                    'status' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('deduction_types')
            ->whereIn('name', ['SSS', 'Pag-Ibig', 'PHIC'])
            ->delete();
    }
};
