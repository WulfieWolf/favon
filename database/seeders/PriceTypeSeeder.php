<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PriceTypeSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['slug' => 'overnight', 'sort_order' => 10, 'de' => 'Übernachtung', 'en' => 'Overnight stay'],
            ['slug' => 'adult', 'sort_order' => 20, 'de' => 'Erwachsener', 'en' => 'Adult'],
            ['slug' => 'child', 'sort_order' => 30, 'de' => 'Kind', 'en' => 'Child'],
            ['slug' => 'dog', 'sort_order' => 40, 'de' => 'Hund', 'en' => 'Dog'],
            ['slug' => 'vehicle', 'sort_order' => 50, 'de' => 'Fahrzeug', 'en' => 'Vehicle'],
            ['slug' => 'caravan', 'sort_order' => 60, 'de' => 'Wohnwagen', 'en' => 'Caravan'],
            ['slug' => 'electricity', 'sort_order' => 70, 'de' => 'Strom', 'en' => 'Electricity'],
            ['slug' => 'water', 'sort_order' => 80, 'de' => 'Wasser', 'en' => 'Water'],
            ['slug' => 'shower', 'sort_order' => 90, 'de' => 'Dusche', 'en' => 'Shower'],
            ['slug' => 'waste-disposal', 'sort_order' => 100, 'de' => 'Entsorgung', 'en' => 'Waste disposal'],
            ['slug' => 'tourist-tax', 'sort_order' => 110, 'de' => 'Kurtaxe', 'en' => 'Tourist tax'],
            ['slug' => 'reservation-fee', 'sort_order' => 120, 'de' => 'Reservierungsgebühr', 'en' => 'Reservation fee'],
        ];

        foreach ($items as $item) {
            DB::table('price_types')->updateOrInsert(
                ['slug' => $item['slug']],
                [
                    'sort_order' => $item['sort_order'],
                    'is_active' => true,
                    'is_searchable' => true,
                    'internal_comment' => null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            $priceTypeId = DB::table('price_types')
                ->where('slug', $item['slug'])
                ->value('id');

            foreach (['de', 'en'] as $locale) {
                DB::table('translations')->updateOrInsert(
                    [
                        'entity_type' => 'price_type',
                        'entity_id' => $priceTypeId,
                        'locale' => $locale,
                        'field' => 'name',
                    ],
                    [
                        'value' => $item[$locale],
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
