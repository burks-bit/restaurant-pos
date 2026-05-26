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
        Schema::create('head_pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->string('label')->nullable(); // e.g., Free, Half, Full
            $table->integer('min_age')->nullable(); // minimum age
            $table->integer('max_age')->nullable(); // maximum age
            $table->decimal('min_height', 5, 2)->nullable(); // in feet
            $table->decimal('max_height', 5, 2)->nullable(); // in feet
            $table->decimal('price', 8, 2)->default(0); // price in pesos
            $table->boolean('is_active')->default(true); // enable or disable rule
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('head_pricing_rules');
    }
};
