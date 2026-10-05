<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountryRegionSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('countries')->updateOrInsert(
            ['code' => 'DE'],
            [
                'local_name' => 'Deutschland',
                'is_active' => true,
                'sort_order' => 10,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        foreach ([
            'de' => 'Deutschland',
            'en' => 'Germany',
        ] as $locale => $name) {
            DB::table('country_translations')->updateOrInsert(
                [
                    'country_code' => 'DE',
                    'locale' => $locale,
                ],
                [
                    'name' => $name,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $regions = [
            ['code' => 'DE-BW', 'sort_order' => 10,  'local_name' => 'Baden-Württemberg',        'en' => 'Baden-Württemberg'],
            ['code' => 'DE-BY', 'sort_order' => 20,  'local_name' => 'Bayern',                   'en' => 'Bavaria'],
            ['code' => 'DE-BE', 'sort_order' => 30,  'local_name' => 'Berlin',                   'en' => 'Berlin'],
            ['code' => 'DE-BB', 'sort_order' => 40,  'local_name' => 'Brandenburg',              'en' => 'Brandenburg'],
            ['code' => 'DE-HB', 'sort_order' => 50,  'local_name' => 'Bremen',                   'en' => 'Bremen'],
            ['code' => 'DE-HH', 'sort_order' => 60,  'local_name' => 'Hamburg',                  'en' => 'Hamburg'],
            ['code' => 'DE-HE', 'sort_order' => 70,  'local_name' => 'Hessen',                   'en' => 'Hesse'],
            ['code' => 'DE-MV', 'sort_order' => 80,  'local_name' => 'Mecklenburg-Vorpommern',   'en' => 'Mecklenburg-Western Pomerania'],
            ['code' => 'DE-NI', 'sort_order' => 90,  'local_name' => 'Niedersachsen',             'en' => 'Lower Saxony'],
            ['code' => 'DE-NW', 'sort_order' => 100, 'local_name' => 'Nordrhein-Westfalen',      'en' => 'North Rhine-Westphalia'],
            ['code' => 'DE-RP', 'sort_order' => 110, 'local_name' => 'Rheinland-Pfalz',          'en' => 'Rhineland-Palatinate'],
            ['code' => 'DE-SL', 'sort_order' => 120, 'local_name' => 'Saarland',                 'en' => 'Saarland'],
            ['code' => 'DE-SN', 'sort_order' => 130, 'local_name' => 'Sachsen',                  'en' => 'Saxony'],
            ['code' => 'DE-ST', 'sort_order' => 140, 'local_name' => 'Sachsen-Anhalt',           'en' => 'Saxony-Anhalt'],
            ['code' => 'DE-SH', 'sort_order' => 150, 'local_name' => 'Schleswig-Holstein',       'en' => 'Schleswig-Holstein'],
            ['code' => 'DE-TH', 'sort_order' => 160, 'local_name' => 'Thüringen',                'en' => 'Thuringia'],
        ];

        foreach ($regions as $region) {
            DB::table('regions')->updateOrInsert(
                ['code' => $region['code']],
                [
                    'country_code' => 'DE',
                    'local_name' => $region['local_name'],
                    'is_active' => true,
                    'sort_order' => $region['sort_order'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            $regionId = DB::table('regions')
                ->where('code', $region['code'])
                ->value('id');

            foreach ([
                'de' => $region['local_name'],
                'en' => $region['en'],
            ] as $locale => $name) {
                DB::table('region_translations')->updateOrInsert(
                    [
                        'region_id' => $regionId,
                        'locale' => $locale,
                    ],
                    [
                        'name' => $name,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }
    }
}
