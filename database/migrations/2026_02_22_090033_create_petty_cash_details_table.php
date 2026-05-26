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
        Schema::create('petty_cash_details', function (Blueprint $table) {
            $table->id();
            // Reference to petty_cashes entry
            $table->foreignId('petty_cash_id')->constrained('petty_cashes')->onDelete('cascade');
            
            $table->string('purpose')->default('change'); // e.g., change, supplies, misc
            $table->decimal('amount', 12, 2); // amount used
            $table->text('denominations')->nullable(); // specific bills/coins used
            $table->string('notes')->nullable(); // optional notes
            $table->string('posted_by')->nullable(); // optional notes
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('petty_cash_details');
    }
};
