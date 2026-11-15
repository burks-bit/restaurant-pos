<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('additional_pax')->default(0)->after('package_price');
            $table->decimal('additional_pax_amount', 12, 2)->default(0)->after('additional_pax');
            $table->decimal('time_extension_amount', 12, 2)->default(0)->after('additional_pax_amount');
            $table->unsignedInteger('senior_pax')->default(0)->after('time_extension_amount');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'additional_pax',
                'additional_pax_amount',
                'time_extension_amount',
                'senior_pax',
            ]);
        });
    }
};
