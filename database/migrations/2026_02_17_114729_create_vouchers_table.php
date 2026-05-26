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
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('control_no')->unique();
            $table->enum('type', ['10% Discount', 'Free Meal (Bring 2 Companions)', 'Free Meal']);
            $table->enum('status', ['available', 'used', 'expired'])->default('available');
            $table->unsignedBigInteger('used_by_order_id')->nullable();
            $table->dateTime('issued_at')->nullable();
            $table->dateTime('used_at')->nullable();
            $table->date('validity')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
