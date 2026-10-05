<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['unit_key' => 'cm', 'symbol' => 'cm', 'sort_order' => 10, 'de' => 'Zentimeter', 'en' => 'Centimetre'],
            ['unit_key' => 'm', 'symbol' => 'm', 'sort_order' => 20, 'de' => 'Meter', 'en' => 'Metre'],
            ['unit_key' => 'km', 'symbol' => 'km', 'sort_order' => 30, 'de' => 'Kilometer', 'en' => 'Kilometre'],
            ['unit_key' => 'min', 'symbol' => 'min', 'sort_order' => 40, 'de' => 'Minute', 'en' => 'Minute'],
            ['unit_key' => 'h', 'symbol' => 'h', 'sort_order' => 50, 'de' => 'Stunde', 'en' => 'Hour'],
            ['unit_key' => 'day', 'symbol' => 'Tag', 'sort_order' => 60, 'de' => 'Tag', 'en' => 'Day'],
            ['unit_key' => 'A', 'symbol' => 'A', 'sort_order' => 70, 'de' => 'Ampere', 'en' => 'Ampere'],
            ['unit_key' => 'W', 'symbol' => 'W', 'sort_order' => 80, 'de' => 'Watt', 'en' => 'Watt'],
            ['unit_key' => 'kW', 'symbol' => 'kW', 'sort_order' => 90, 'de' => 'Kilowatt', 'en' => 'Kilowatt'],
            ['unit_key' => 'Wh', 'symbol' => 'Wh', 'sort_order' => 100, 'de' => 'Wattstunde', 'en' => 'Watt-hour'],
            ['unit_key' => 'kWh', 'symbol' => 'kWh', 'sort_order' => 110, 'de' => 'Kilowattstunde', 'en' => 'Kilowatt-hour'],
            ['unit_key' => 'kg', 'symbol' => 'kg', 'sort_order' => 120, 'de' => 'Kilogramm', 'en' => 'Kilogram'],
            ['unit_key' => 't', 'symbol' => 't', 'sort_order' => 130, 'de' => 'Tonne', 'en' => 'Tonne'],
            ['unit_key' => 'l', 'symbol' => 'l', 'sort_order' => 140, 'de' => 'Liter', 'en' => 'Litre'],
            ['unit_key' => 'EUR', 'symbol' => '€', 'sort_order' => 150, 'de' => 'Euro', 'en' => 'Euro'],
        ];

        foreach ($units as $unit) {
            DB::table('units')->updateOrInsert(
                ['unit_key' => $unit['unit_key']],
                [
                    'symbol' => $unit['symbol'],
                    'sort_order' => $unit['sort_order'],
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            $unitId = DB::table('units')
                ->where('unit_key', $unit['unit_key'])
                ->value('id');

            foreach (['de', 'en'] as $locale) {
                DB::table('translations')->updateOrInsert(
                    [
                        'entity_type' => 'unit',
                        'entity_id' => $unitId,
                        'locale' => $locale,
                        'field' => 'name',
                    ],
                    [
                        'value' => $unit[$locale],
                        'is_active' => true,
                        'internal_comment' => null,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }

        $unitTypeMap = [
            'distance' => [
                ['unit_key' => 'm', 'sort_order' => 10, 'is_default' => true],
                ['unit_key' => 'km', 'sort_order' => 20, 'is_default' => false],
            ],
            'length' => [
                ['unit_key' => 'cm', 'sort_order' => 10, 'is_default' => false],
                ['unit_key' => 'm', 'sort_order' => 20, 'is_default' => true],
            ],
            'width' => [
                ['unit_key' => 'cm', 'sort_order' => 10, 'is_default' => false],
                ['unit_key' => 'm', 'sort_order' => 20, 'is_default' => true],
            ],
            'time' => [
                ['unit_key' => 'min', 'sort_order' => 10, 'is_default' => true],
                ['unit_key' => 'h', 'sort_order' => 20, 'is_default' => false],
                ['unit_key' => 'day', 'sort_order' => 30, 'is_default' => false],
            ],
            'current' => [
                ['unit_key' => 'A', 'sort_order' => 10, 'is_default' => true],
            ],
            'power' => [
                ['unit_key' => 'W', 'sort_order' => 10, 'is_default' => true],
                ['unit_key' => 'kW', 'sort_order' => 20, 'is_default' => false],
            ],
            'energy' => [
                ['unit_key' => 'Wh', 'sort_order' => 10, 'is_default' => false],
                ['unit_key' => 'kWh', 'sort_order' => 20, 'is_default' => true],
            ],
            'weight' => [
                ['unit_key' => 'kg', 'sort_order' => 10, 'is_default' => false],
                ['unit_key' => 't', 'sort_order' => 20, 'is_default' => true],
            ],
            'volume' => [
                ['unit_key' => 'l', 'sort_order' => 10, 'is_default' => true],
            ],
            'currency' => [
                ['unit_key' => 'EUR', 'sort_order' => 10, 'is_default' => true],
            ],
        ];

        foreach ($unitTypeMap as $unitType => $items) {
            foreach ($items as $item) {
                $unitId = DB::table('units')
                    ->where('unit_key', $item['unit_key'])
                    ->value('id');

                DB::table('unit_type_units')->updateOrInsert(
                    [
                        'unit_type' => $unitType,
                        'unit_id' => $unitId,
                    ],
                    [
                        'sort_order' => $item['sort_order'],
                        'is_default' => $item['is_default'],
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }

        foreach ($units as $unit) {
            $unitId = DB::table('units')
                ->where('unit_key', $unit['unit_key'])
                ->value('id');

            DB::table('place_features')
                ->whereNull('unit_id')
                ->where('unit_key', $unit['unit_key'])
                ->update([
                    'unit_id' => $unitId,
                    'updated_at' => now(),
                ]);
        }
    }
}
