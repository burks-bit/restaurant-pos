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
        Schema::table('table_sessions', function (Blueprint $table) {
            $table->foreignId('pricing_scheme_id')
                ->nullable()
                ->after('table_id')
                ->constrained('pricing_schemes')
                ->nullOnDelete();

            $table->string('session_type')->default('walk_in')->after('pricing_scheme_id');
            // walk_in, event, promo

            $table->string('event_name')->nullable()->after('customer_name');
            $table->text('event_notes')->nullable()->after('event_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('table_sessions', function (Blueprint $table) {
            $table->dropForeign(['pricing_scheme_id']);
            $table->dropColumn([
                'pricing_scheme_id',
                'session_type',
                'event_name',
                'event_notes',
            ]);
        });
    }
};
