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
        Schema::create('cash_registers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cashier_id')
                  ->constrained('users') // or 'cashiers' if separate table
                  ->cascadeOnDelete();

            $table->foreignId('shift_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->decimal('net_sales', 10, 2)->default(0);
            $table->decimal('cash_on_hand', 10, 2)->default(0);

            $table->text('denomination')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_registers');
    }
};
