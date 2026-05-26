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
        Schema::create('payroll_earnings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('payroll_item_id')
                ->constrained()
                ->onDelete('cascade');

            $table->string('earning_type'); // Salary Adjustment, De Minimis, Overtime, etc.
            $table->decimal('amount', 15, 2);

            $table->text('remarks')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_earnings');
    }
};
