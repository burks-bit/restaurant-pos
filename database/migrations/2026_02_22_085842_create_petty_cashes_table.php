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
        Schema::create('petty_cashes', function (Blueprint $table) {
            $table->id();
            $table->date('date'); // start-of-day float
            $table->decimal('total_amount', 12, 2); // total float amount
            $table->decimal('amount_used', 12, 2)->nullable(); // total float amount
            $table->decimal('remaining', 12, 2)->nullable(); // total float amount
            $table->text('denominations')->nullable(); // JSON stored as text
            $table->string('notes')->nullable(); // optional notes
            $table->string('posted_by')->nullable(); 
            $table->string('updated_by')->nullable(); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('petty_cashes');
    }
};
