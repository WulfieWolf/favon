<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlaceTypeSeeder extends Seeder
{
    public function run(): void
    {
        $placeTypes = [
            [
                'slug' => 'campground',
                'sort_order' => 10,
                'translations' => ['de' => 'Campingplatz', 'en' => 'Campground'],
                'descriptions' => [
                    'de' => 'Klassischer Campingplatz für Wohnmobile, Wohnwagen, Zelte und ähnliche Campingformen.',
                    'en' => 'Classic campground for motorhomes, caravans, tents and similar camping.',
                ],
            ],
            [
                'slug' => 'motorhome-pitch',
                'sort_order' => 20,
                'translations' => ['de' => 'Wohnmobilstellplatz', 'en' => 'Motorhome pitch'],
                'descriptions' => [
                    'de' => 'Dedizierter Stellplatz für Wohnmobile oder Camper, häufig mit Parkplatzcharakter.',
                    'en' => 'Dedicated place for motorhomes or campervans, often similar to a parking area.',
                ],
            ],
            [
                'slug' => 'tent-site',
                'sort_order' => 30,
                'translations' => ['de' => 'Zeltplatz', 'en' => 'Tent site'],
                'descriptions' => [
                    'de' => 'Platz speziell für Zelte, z. B. Trekking-, Jugend- oder Pfadfinderplätze.',
                    'en' => 'Site specifically for tents, such as trekking, youth or scout camps.',
                ],
            ],
            [
                'slug' => 'parking',
                'sort_order' => 40,
                'translations' => ['de' => 'Parkplatz', 'en' => 'Parking area'],
                'descriptions' => [
                    'de' => 'Klassischer Parkplatz; ob Übernachten erlaubt oder geduldet ist, wird separat erfasst.',
                    'en' => 'Regular parking area; whether overnight stays are allowed or tolerated is recorded separately.',
                ],
            ],
            [
                'slug' => 'hiking-parking',
                'sort_order' => 45,
                'translations' => ['de' => 'Wanderparkplatz', 'en' => 'Hiking parking'],
                'descriptions' => [
                    'de' => 'Parkplatz mit besonderem Bezug zu Wanderwegen oder Wandergebieten; ob Übernachten erlaubt oder geduldet ist, wird separat erfasst.',
                    'en' => 'Parking area primarily serving hiking trails or hiking areas; whether overnight stays are allowed or tolerated is recorded separately.',
                ],
            ],
            [
                'slug' => 'rest-area',
                'sort_order' => 50,
                'translations' => ['de' => 'Rastplatz / Autohof', 'en' => 'Rest area / truck stop'],
                'descriptions' => [
                    'de' => 'Rastplatz, Raststätte oder Autohof für Reisende, einschließlich LKW-Verkehr.',
                    'en' => 'Rest area, service area or truck stop for travellers, including truck traffic.',
                ],
            ],
            [
                'slug' => 'free-pitch',
                'sort_order' => 60,
                'translations' => ['de' => 'Freier Stellplatz', 'en' => 'Informal pitch'],
                'descriptions' => [
                    'de' => 'Ein einfacher Stellplatz außerhalb klassischer Camping- oder Parkplatzstrukturen, z. B. Wiese, Hof oder Platz am See.',
                    'en' => 'A simple pitch outside classic campground or parking structures, e.g. a field, farm or lakeside spot.',
                ],
            ],
            [
                'slug' => 'service-station',
                'sort_order' => 70,
                'translations' => ['de' => 'Servicestation', 'en' => 'Service station'],
                'descriptions' => [
                    'de' => 'Ort für campingbezogene Dienstleistungen wie Wasser, Entsorgung, Waschanlage oder Werkstatt.',
                    'en' => 'Place for camping-related services such as water, disposal, washing or workshop services.',
                ],
            ],
            [
                'slug' => 'camping-outdoor',
                'sort_order' => 80,
                'translations' => ['de' => 'Camping-/Outdoor-Shop', 'en' => 'Camping & outdoor store'],
                'descriptions' => [
                    'de' => 'Camping- oder Outdoor-Fachhandel und anderer dauerhaft campingbezogener Einzelhandel.',
                    'en' => 'Camping or outdoor retailer and other permanent camping-related retail destination.',
                ],
            ],
        ];

        DB::table('place_types')
            ->whereIn('slug', ['stay', 'stay-service', 'service'])
            ->update([
                'is_searchable' => false,
                'updated_at' => now(),
            ]);

        foreach ($placeTypes as $placeTypeData) {
            $placeTypeId = DB::table('place_types')->updateOrInsert(
                ['slug' => $placeTypeData['slug']],
                [
                    'icon_id' => null,
                    'sort_order' => $placeTypeData['sort_order'],
                    'is_active' => true,
                    'is_searchable' => true,
                    'internal_comment' => null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            $placeType = DB::table('place_types')
                ->where('slug', $placeTypeData['slug'])
                ->first();

            foreach ($placeTypeData['translations'] as $locale => $value) {
                DB::table('translations')->updateOrInsert(
                    [
                        'entity_type' => 'place_type',
                        'entity_id' => $placeType->id,
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

                DB::table('translations')->updateOrInsert(
                    [
                        'entity_type' => 'place_type',
                        'entity_id' => $placeType->id,
                        'locale' => $locale,
                        'field' => 'description',
                    ],
                    [
                        'value' => $placeTypeData['descriptions'][$locale],
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
