<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PlaceTypeSeeder::class,
            VehicleTypeSeeder::class,
            V1FeatureCatalogSeeder::class,
            ExternalDataFeatureExtensionSeeder::class,
            UnitSeeder::class,
            BillingUnitSeeder::class,
            FeatureUnitSeeder::class,
            CountryRegionSeeder::class,
            PriceTypeSeeder::class,
            PriceCatalogSeeder::class,
            SuggestableFieldSeeder::class,
            RolePermissionSeeder::class,
            AdminAccessPermissionSeeder::class,
        ]);
    }
}
