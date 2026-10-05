<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BillingUnitSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('units')->updateOrInsert(
            ['unit_key' => 'night'],
            [
                'symbol' => 'night',
                'sort_order' => 160,
                'is_active' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $unitId = DB::table('units')
            ->where('unit_key', 'night')
            ->value('id');

        foreach ([
            'de' => 'Nacht',
            'en' => 'Night',
        ] as $locale => $value) {
            DB::table('translations')->updateOrInsert(
                [
                    'entity_type' => 'unit',
                    'entity_id' => $unitId,
                    'locale' => $locale,
                    'field' => 'name',
                ],
                [
                    'value' => $value,
                    'is_active' => true,
                    'internal_comment' => null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        DB::table('unit_type_units')->updateOrInsert(
            [
                'unit_type' => 'time',
                'unit_id' => $unitId,
            ],
            [
                'sort_order' => 40,
                'is_default' => false,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}
