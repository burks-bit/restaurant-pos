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
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')
                ->constrained('inventory_categories')
                ->cascadeOnDelete();

            $table->string('name');
            $table->enum('type', ['consumable', 'asset']);
            $table->string('unit')->nullable(); // kg, pcs, liters

            $table->decimal('current_quantity', 15, 2)->default(0);
            $table->decimal('unit_price', 15, 2)->default(0);

            $table->boolean('status')->default(true);
            $table->boolean('orderable')->default(0);
            
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
                
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
