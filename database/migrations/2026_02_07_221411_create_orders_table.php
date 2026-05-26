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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('order_no')->nullable();
            $table->decimal('subtotal', 8, 2);
            $table->decimal('total_discount', 8, 2)->default(0);
            $table->decimal('total', 8, 2);
            $table->string('discount_type')->nullable();
            $table->string('status')->default('paid');
            $table->boolean('cancelled')->default(false);
            $table->integer('cancelled_by')->nullable();
            $table->text('cancellation_remarks')->nullable();

            $table->string('payment_method')->nullable();
            $table->decimal('cash_amount', 12, 2)->nullable();
            $table->decimal('change_amount', 12, 2)->nullable();

            $table->string('table_number')->nullable();

            $table->string('voucher_no_used')->nullable();
            $table->decimal('voucher_discount_used', 8, 2)->nullable()->default(0);

            $table->integer('discount_approving_manager_id')->nullable();
            $table->integer('cancel_approving_manager_id')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
