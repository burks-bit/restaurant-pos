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
        Schema::create('package_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained('packages')->cascadeOnDelete();
            $table->unsignedInteger('pax');
            $table->decimal('price', 12, 2)->default(0);
            $table->timestamps();

            // A package cannot have two prices for the same pax count.
            $table->unique(['package_id', 'pax'], 'package_prices_package_pax_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_prices');
    }
};
