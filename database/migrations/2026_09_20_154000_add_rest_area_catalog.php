<?php

use Database\Seeders\RestAreaCatalogSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        (new RestAreaCatalogSeeder())->run();
    }

    public function down(): void
    {
        $featureSlugs = [
            'fuel-station',
            'fuel-petrol-e5',
            'fuel-petrol-e10',
            'fuel-super-plus',
            'fuel-diesel',
            'fuel-premium-diesel',
            'fuel-adblue',
            'fuel-lpg',
            'fuel-cng',
            'fuel-hydrogen',
            'fuel-truck-pump',
            'rest-convenience-store',
            'rest-hotel',
            'rest-workshop',
            'rest-tire-service',
            'rest-car-wash',
            'rest-truck-wash',
            'rest-secure-truck-parking',
            'rest-trucker-lounge',
            'rest-atm',
            'rest-vending-machines',
            'rest-picnic-area',
            'rest-dog-area',
            'rest-laundry',
            'rest-emergency-phone',
        ];

        DB::table('features')
            ->whereIn('slug', $featureSlugs)
            ->update([
                'is_active' => false,
                'is_searchable' => false,
                'updated_at' => now(),
            ]);

        DB::table('feature_categories')
            ->where('slug', 'fuel-rest-area')
            ->update([
                'is_active' => false,
                'is_searchable' => false,
                'updated_at' => now(),
            ]);

        DB::table('vehicle_types')
            ->whereIn('slug', [
                'car-with-trailer',
                'motorcycle',
                'small-van',
                'truck',
                'light-truck',
                'heavy-truck',
                'truck-combination',
                'coach',
                'heavy-haulage',
            ])
            ->update([
                'is_active' => false,
                'is_searchable' => false,
                'updated_at' => now(),
            ]);
    }
};
