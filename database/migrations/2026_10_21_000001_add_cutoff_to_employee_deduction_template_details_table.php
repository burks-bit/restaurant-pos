<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_deduction_template_details', function (Blueprint $table) {
            $table->string('cutoff', 10)
                ->default('15th')
                ->after('deduction_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('employee_deduction_template_details', function (Blueprint $table) {
            $table->dropColumn('cutoff');
        });
    }
};
