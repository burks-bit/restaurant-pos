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
        Schema::table('head_pricing_rules', function (Blueprint $table) {
            $table->foreignId('pricing_scheme_id')
                ->nullable()
                ->after('id')
                ->constrained('pricing_schemes')
                ->nullOnDelete();

            $table->string('code')->nullable()->after('pricing_scheme_id'); 
            // examples: adult, child_free, child_half, senior, custom
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('head_pricing_rules', function (Blueprint $table) {
            $table->dropForeign(['pricing_scheme_id']);
            $table->dropColumn(['pricing_scheme_id', 'code']);
        });
    }
};
