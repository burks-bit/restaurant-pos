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
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['menu_id']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('menu_id');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('inventory_item_id')->nullable()->after('order_id');
            $table->string('item_name')->nullable()->after('inventory_item_id');
            $table->string('unit')->nullable()->after('item_name');
            $table->decimal('subtotal', 12, 2)->default(0)->after('quantity');

            $table->foreign('inventory_item_id')
                ->references('id')
                ->on('inventory_items')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['inventory_item_id']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn([
                'inventory_item_id',
                'item_name',
                'unit',
                'quantity',
                'price_snapshot',
                'subtotal',
            ]);

            $table->unsignedBigInteger('menu_id')->nullable()->after('order_id');
            $table->foreign('menu_id')->references('id')->on('menus');
        });
    }
};
