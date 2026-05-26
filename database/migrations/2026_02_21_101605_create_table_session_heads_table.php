<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('table_session_heads', function (Blueprint $table) {
            $table->id();

            $table->foreignId('table_session_id')
                ->constrained('table_sessions')
                ->cascadeOnDelete();

            $table->foreignId('head_pricing_rule_id')
                ->constrained('head_pricing_rules')
                ->cascadeOnDelete();

            $table->integer('qty')->default(0);

            // snapshot of price at the time of transaction
            $table->decimal('price_snapshot', 10, 2);

            $table->decimal('subtotal', 10, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_session_heads');
    }
};
