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
        Schema::create('pricing_schemes', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. Regular Buffet, Birthday Event
            $table->enum('type', ['regular', 'event', 'promo'])->default('regular');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_schemes');
    }
};
