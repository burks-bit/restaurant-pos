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
            $table->foreignId('package_id')
                ->nullable()
                ->after('pricing_scheme_id')
                ->constrained('packages')
                ->nullOnDelete();

            $table->string('package_name')->nullable()->after('package_id');
            $table->unsignedInteger('package_pax')->nullable()->after('package_name');
            $table->decimal('package_price', 12, 2)->nullable()->after('package_pax');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('table_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('package_id');
            $table->dropColumn(['package_name', 'package_pax', 'package_price']);
        });
    }
};
