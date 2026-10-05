<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VehicleTypeSeeder extends Seeder
{
    /**
     * Seed the vehicle types and their translations.
     */
    public function run(): void
    {
        $vehicleTypes = [
            [
                'slug' => 'car',
                'sort_order' => 10,
                'de' => 'PKW',
                'en' => 'Car',
            ],
            [
                'slug' => 'minicamper',
                'sort_order' => 20,
                'de' => 'Minicamper',
                'en' => 'Minicamper',
            ],
            [
                'slug' => 'campervan',
                'sort_order' => 30,
                'de' => 'Campervan',
                'en' => 'Campervan',
            ],
            [
                'slug' => 'large-camper',
                'sort_order' => 40,
                'de' => 'Großer Camper',
                'en' => 'Large camper',
            ],
            [
                'slug' => 'motorhome',
                'sort_order' => 50,
                'de' => 'Wohnmobil',
                'en' => 'Motorhome',
            ],
            [
                'slug' => 'caravan',
                'sort_order' => 60,
                'de' => 'Wohnwagengespann',
                'en' => 'Caravan outfit',
            ],
            [
                'slug' => 'rooftop-tent',
                'sort_order' => 70,
                'de' => 'Dachzelt',
                'en' => 'Rooftop tent',
            ],
            [
                'slug' => 'tent',
                'sort_order' => 80,
                'de' => 'Zelt',
                'en' => 'Tent',
            ],
        ];

        foreach ($vehicleTypes as $vehicleType) {
            $vehicleTypeId = DB::table('vehicle_types')->updateOrInsert(
                ['slug' => $vehicleType['slug']],
                [
                    'icon_id' => null,
                    'sort_order' => $vehicleType['sort_order'],
                    'is_active' => true,
                    'is_searchable' => true,
                    'internal_comment' => null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            $entityId = DB::table('vehicle_types')
                ->where('slug', $vehicleType['slug'])
                ->value('id');

            foreach (['de', 'en'] as $locale) {
                DB::table('translations')->updateOrInsert(
                    [
                        'entity_type' => 'vehicle_type',
                        'entity_id' => $entityId,
                        'locale' => $locale,
                        'field' => 'name',
                    ],
                    [
                        'value' => $vehicleType[$locale],
                        'is_active' => true,
                        'internal_comment' => null,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }
    }
}
