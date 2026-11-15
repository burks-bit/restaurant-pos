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
        Schema::table('reservations', function (Blueprint $table) {
            $table->foreignId('package_id')
                ->nullable()
                ->after('pricing_scheme_id')
                ->constrained('packages')
                ->nullOnDelete();

            $table->unsignedInteger('package_pax')->nullable()->after('package_id');
            $table->decimal('package_price', 12, 2)->nullable()->after('package_pax');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('package_id');
            $table->dropColumn(['package_pax', 'package_price']);
        });
    }
};
