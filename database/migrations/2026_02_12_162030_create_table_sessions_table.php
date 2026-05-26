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
        Schema::create('table_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name')->nullable();
            $table->string('ref_no')->nullable();
            $table->foreignId('table_id')->constrained()->cascadeOnDelete();

            $table->integer('pax'); // number of guests
            $table->enum('status', ['open', 'paid', 'closed', 'cancelled'])->default('open');
            $table->string('remarks')->nullable();

            $table->foreignId('frontdoor_id')->constrained('users');
            $table->foreignId('cashier_id')->nullable()->constrained('users');
            $table->foreignId('order_id')->nullable()->constrained('orders');

            $table->decimal('total_amount', 10, 2)->nullable();
            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('closed_at')->nullable();
            $table->boolean('is_shared')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_sessions');
    }
};
