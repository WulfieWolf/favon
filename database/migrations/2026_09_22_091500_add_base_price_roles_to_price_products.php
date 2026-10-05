<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('price_products', function (Blueprint $table) {
            $table->boolean('is_vehicle_base_price')->default(false)->after('supports_age_range');
            $table->boolean('is_overnight_base_price')->default(false)->after('is_vehicle_base_price');
        });

        DB::table('price_products')
            ->whereIn('slug', ['pitch', 'motorhome-pitch', 'campervan-pitch', 'caravan-pitch', 'car-rooftent-pitch'])
            ->update(['is_vehicle_base_price' => true]);

        DB::table('price_products')
            ->whereIn('slug', ['overnight', 'adult'])
            ->update(['is_overnight_base_price' => true]);
    }

    public function down(): void
    {
        Schema::table('price_products', function (Blueprint $table) {
            $table->dropColumn(['is_vehicle_base_price', 'is_overnight_base_price']);
        });
    }
};
