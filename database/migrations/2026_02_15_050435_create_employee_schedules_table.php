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
        Schema::create('employee_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->date('schedule_date')->nullable();
            $table->string('shift')->nullable(); // Morning, Evening, etc.
            $table->time('time_in')->nullable();
            $table->time('time_out')->nullable();
            
            $table->enum('status', ['Scheduled', 'Absent', 'Leave', 'Day Off'])->default('Scheduled');
            $table->string('remarks')->nullable(); // Reason for absence, leave, etc.
            
            $table->time('actual_time_in')->nullable();
            $table->time('actual_time_out')->nullable();
            $table->enum('holiday_type', ['Regular', 'Special'])->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_schedules');
    }
};
