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
        Schema::create('employment_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('position');
            $table->string('department')->nullable();
            $table->date('hire_date');
            $table->decimal('salary', 12, 2)->nullable();
            $table->decimal('daily_rate', 12, 2)->nullable();
            $table->decimal('overtime_rate', 12, 2)->nullable();
            $table->string('employment_type')->nullable(); // regular, contractual
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employment_details');
    }
};
