<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employee_deduction_template_details', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_deduction_template_id');

            $table->foreign('employee_deduction_template_id', 'edt_details_template_fk')
                ->references('id')
                ->on('employee_deduction_templates')
                ->onDelete('cascade');

            $table->foreignId('deduction_type_id')
                ->constrained('deduction_types')
                ->onDelete('cascade');

            $table->decimal('default_value', 15, 2)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_deduction_template_details');
    }
};
