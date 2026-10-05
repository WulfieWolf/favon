<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FeatureSeeder extends Seeder
{
    /**
     * Seed the initial Camperwolf features/tags, options and translations.
     */
    public function run(): void
    {
        $categorySlugs = [
            'utilities',
            'sanitary',
            'facilities',
            'access',
            'rules',
            'surroundings',
            'services',
            'rental',
            'accessibility',
        ];

        $categoryIds = DB::table('feature_categories')
            ->whereIn('slug', $categorySlugs)
            ->pluck('id', 'slug');

        if ($categoryIds->count() !== count($categorySlugs)) {
            return;
        }

        $features = [
            // Utilities
            ['category_slug' => 'utilities', 'slug' => 'electricity', 'sort_order' => 10, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Stromanschluss', 'en' => 'Electricity'],
            ['category_slug' => 'utilities', 'slug' => 'fresh-water', 'sort_order' => 20, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Frischwasser', 'en' => 'Fresh water'],
            ['category_slug' => 'utilities', 'slug' => 'grey-water-disposal', 'sort_order' => 30, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Grauwasserentsorgung', 'en' => 'Grey water disposal'],
            ['category_slug' => 'utilities', 'slug' => 'black-water-disposal', 'sort_order' => 40, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Kassetten-/Schwarzwasserentsorgung', 'en' => 'Black water disposal'],
            ['category_slug' => 'utilities', 'slug' => 'gas-bottle-exchange', 'sort_order' => 50, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Gasflaschentausch', 'en' => 'Gas bottle exchange'],
            ['category_slug' => 'utilities', 'slug' => 'ev-charging', 'sort_order' => 60, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'E-Ladestation', 'en' => 'EV charging'],
            ['category_slug' => 'utilities', 'slug' => 'electricity-amperage', 'sort_order' => 70, 'value_type' => 'number', 'unit_type' => 'current', 'de' => 'Stromstärke', 'en' => 'Electricity amperage'],
            ['category_slug' => 'utilities', 'slug' => 'fresh-water-distance', 'sort_order' => 80, 'value_type' => 'number', 'unit_type' => 'distance', 'de' => 'Entfernung zur Wasserstelle', 'en' => 'Distance to fresh water'],

            // Sanitary
            ['category_slug' => 'sanitary', 'slug' => 'toilet', 'sort_order' => 10, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Toilette', 'en' => 'Toilet'],
            ['category_slug' => 'sanitary', 'slug' => 'shower', 'sort_order' => 20, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Dusche', 'en' => 'Shower'],
            ['category_slug' => 'sanitary', 'slug' => 'washbasin', 'sort_order' => 30, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Waschbecken', 'en' => 'Washbasin'],
            ['category_slug' => 'sanitary', 'slug' => 'hot-water', 'sort_order' => 40, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Warmwasser', 'en' => 'Hot water'],
            ['category_slug' => 'sanitary', 'slug' => 'family-bathroom', 'sort_order' => 50, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Familienbad', 'en' => 'Family bathroom'],
            ['category_slug' => 'sanitary', 'slug' => 'baby-changing', 'sort_order' => 60, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Wickelmöglichkeit', 'en' => 'Baby changing facility'],
            ['category_slug' => 'sanitary', 'slug' => 'shower-price', 'sort_order' => 70, 'value_type' => 'number', 'unit_type' => 'currency', 'de' => 'Duschpreis', 'en' => 'Shower price'],
            ['category_slug' => 'sanitary', 'slug' => 'shower-duration', 'sort_order' => 80, 'value_type' => 'number', 'unit_type' => 'time', 'de' => 'Duschdauer', 'en' => 'Shower duration'],
            ['category_slug' => 'sanitary', 'slug' => 'hot-water-price', 'sort_order' => 90, 'value_type' => 'number', 'unit_type' => 'currency', 'de' => 'Warmwasserpreis', 'en' => 'Hot water price'],
            ['category_slug' => 'sanitary', 'slug' => 'hot-water-duration', 'sort_order' => 100, 'value_type' => 'number', 'unit_type' => 'time', 'de' => 'Warmwasserdauer', 'en' => 'Hot water duration'],

            // Facilities
            ['category_slug' => 'facilities', 'slug' => 'wifi', 'sort_order' => 10, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'WLAN', 'en' => 'Wi-Fi'],
            ['category_slug' => 'facilities', 'slug' => 'washing-machine', 'sort_order' => 20, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Waschmaschine', 'en' => 'Washing machine'],
            ['category_slug' => 'facilities', 'slug' => 'dryer', 'sort_order' => 30, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Trockner', 'en' => 'Dryer'],
            ['category_slug' => 'facilities', 'slug' => 'kitchen', 'sort_order' => 40, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Gemeinschaftsküche', 'en' => 'Shared kitchen'],
            ['category_slug' => 'facilities', 'slug' => 'common-room', 'sort_order' => 50, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Aufenthaltsraum', 'en' => 'Common room'],
            ['category_slug' => 'facilities', 'slug' => 'barbecue-area', 'sort_order' => 60, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Grillplatz', 'en' => 'Barbecue area'],
            ['category_slug' => 'facilities', 'slug' => 'playground', 'sort_order' => 70, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Spielplatz', 'en' => 'Playground'],
            ['category_slug' => 'facilities', 'slug' => 'swimming-pool', 'sort_order' => 80, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Pool', 'en' => 'Swimming pool'],
            ['category_slug' => 'facilities', 'slug' => 'washing-machine-price', 'sort_order' => 90, 'value_type' => 'number', 'unit_type' => 'currency', 'de' => 'Preis Waschmaschine', 'en' => 'Washing machine price'],
            ['category_slug' => 'facilities', 'slug' => 'dryer-price', 'sort_order' => 100, 'value_type' => 'number', 'unit_type' => 'currency', 'de' => 'Preis Trockner', 'en' => 'Dryer price'],

            // Access
            ['category_slug' => 'access', 'slug' => 'surface', 'sort_order' => 10, 'value_type' => 'option', 'unit_type' => null, 'de' => 'Untergrund', 'en' => 'Surface'],
            ['category_slug' => 'access', 'slug' => 'entrance-height', 'sort_order' => 20, 'value_type' => 'number', 'unit_type' => 'length', 'de' => 'Einfahrtshöhe', 'en' => 'Entrance height'],
            ['category_slug' => 'access', 'slug' => 'max-vehicle-length', 'sort_order' => 30, 'value_type' => 'number', 'unit_type' => 'length', 'de' => 'Maximale Fahrzeuglänge', 'en' => 'Maximum vehicle length'],
            ['category_slug' => 'access', 'slug' => 'max-vehicle-width', 'sort_order' => 40, 'value_type' => 'number', 'unit_type' => 'length', 'de' => 'Maximale Fahrzeugbreite', 'en' => 'Maximum vehicle width'],
            ['category_slug' => 'access', 'slug' => 'barrier', 'sort_order' => 50, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Schranke', 'en' => 'Barrier gate'],
            ['category_slug' => 'access', 'slug' => 'turning-space', 'sort_order' => 60, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Wendemöglichkeit', 'en' => 'Turning space'],
            ['category_slug' => 'access', 'slug' => 'narrow-access', 'sort_order' => 70, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Enge Zufahrt', 'en' => 'Narrow access'],
            ['category_slug' => 'access', 'slug' => 'steep-access', 'sort_order' => 80, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Steile Zufahrt', 'en' => 'Steep access'],

            // Rules
            ['category_slug' => 'rules', 'slug' => 'dogs-allowed', 'sort_order' => 10, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Hunde erlaubt', 'en' => 'Dogs allowed'],
            ['category_slug' => 'rules', 'slug' => 'generators-allowed', 'sort_order' => 20, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Generatoren erlaubt', 'en' => 'Generators allowed'],
            ['category_slug' => 'rules', 'slug' => 'campfires-allowed', 'sort_order' => 30, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Lagerfeuer erlaubt', 'en' => 'Campfires allowed'],
            ['category_slug' => 'rules', 'slug' => 'barbecue-allowed', 'sort_order' => 40, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Grillen erlaubt', 'en' => 'Barbecues allowed'],
            ['category_slug' => 'rules', 'slug' => 'awning-allowed', 'sort_order' => 50, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Markise erlaubt', 'en' => 'Awning allowed'],
            ['category_slug' => 'rules', 'slug' => 'outdoor-furniture-allowed', 'sort_order' => 60, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Tische und Stühle draußen erlaubt', 'en' => 'Outdoor furniture allowed'],
            ['category_slug' => 'rules', 'slug' => 'reservation-required', 'sort_order' => 70, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Reservierung erforderlich', 'en' => 'Reservation required'],
            ['category_slug' => 'rules', 'slug' => 'max-stay-duration', 'sort_order' => 80, 'value_type' => 'number', 'unit_type' => 'time', 'de' => 'Maximale Aufenthaltsdauer', 'en' => 'Maximum stay duration'],

            // Surroundings
            ['category_slug' => 'surroundings', 'slug' => 'distance-to-supermarket', 'sort_order' => 10, 'value_type' => 'number', 'unit_type' => 'distance', 'de' => 'Entfernung zum Supermarkt', 'en' => 'Distance to supermarket'],
            ['category_slug' => 'surroundings', 'slug' => 'distance-to-bakery', 'sort_order' => 20, 'value_type' => 'number', 'unit_type' => 'distance', 'de' => 'Entfernung zur Bäckerei', 'en' => 'Distance to bakery'],
            ['category_slug' => 'surroundings', 'slug' => 'distance-to-restaurant', 'sort_order' => 30, 'value_type' => 'number', 'unit_type' => 'distance', 'de' => 'Entfernung zum Restaurant', 'en' => 'Distance to restaurant'],
            ['category_slug' => 'surroundings', 'slug' => 'distance-to-public-transport', 'sort_order' => 40, 'value_type' => 'number', 'unit_type' => 'distance', 'de' => 'Entfernung zum ÖPNV', 'en' => 'Distance to public transport'],
            ['category_slug' => 'surroundings', 'slug' => 'distance-to-beach', 'sort_order' => 50, 'value_type' => 'number', 'unit_type' => 'distance', 'de' => 'Entfernung zum Strand', 'en' => 'Distance to beach'],
            ['category_slug' => 'surroundings', 'slug' => 'distance-to-hiking-trail', 'sort_order' => 60, 'value_type' => 'number', 'unit_type' => 'distance', 'de' => 'Entfernung zum Wanderweg', 'en' => 'Distance to hiking trail'],
            ['category_slug' => 'surroundings', 'slug' => 'distance-to-city-center', 'sort_order' => 70, 'value_type' => 'number', 'unit_type' => 'distance', 'de' => 'Entfernung zum Ortszentrum', 'en' => 'Distance to town centre'],

            // Services
            ['category_slug' => 'services', 'slug' => 'reception', 'sort_order' => 10, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Rezeption', 'en' => 'Reception'],
            ['category_slug' => 'services', 'slug' => 'bread-roll-service', 'sort_order' => 20, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Brötchenservice', 'en' => 'Bread roll service'],
            ['category_slug' => 'services', 'slug' => 'shuttle-service', 'sort_order' => 30, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Shuttleservice', 'en' => 'Shuttle service'],
            ['category_slug' => 'services', 'slug' => 'bicycle-rental', 'sort_order' => 40, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Fahrradverleih', 'en' => 'Bicycle rental'],
            ['category_slug' => 'services', 'slug' => 'repair-service', 'sort_order' => 50, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Reparaturservice', 'en' => 'Repair service'],
            ['category_slug' => 'services', 'slug' => 'self-check-in', 'sort_order' => 60, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Selbstständiger Check-in', 'en' => 'Self check-in'],

            // Rental
            ['category_slug' => 'rental', 'slug' => 'rental-caravan', 'sort_order' => 10, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Mietwohnwagen', 'en' => 'Rental caravan'],
            ['category_slug' => 'rental', 'slug' => 'rental-tent', 'sort_order' => 20, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Mietzelt', 'en' => 'Rental tent'],
            ['category_slug' => 'rental', 'slug' => 'rental-mobile-home', 'sort_order' => 30, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Miet-Mobilheim', 'en' => 'Rental mobile home'],
            ['category_slug' => 'rental', 'slug' => 'rental-cabin', 'sort_order' => 40, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Miethütte/Bungalow', 'en' => 'Rental cabin or bungalow'],
            ['category_slug' => 'rental', 'slug' => 'rental-camper', 'sort_order' => 50, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Mietcamper', 'en' => 'Rental camper'],

            // Accessibility
            ['category_slug' => 'accessibility', 'slug' => 'step-free-access', 'sort_order' => 10, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Stufenloser Zugang', 'en' => 'Step-free access'],
            ['category_slug' => 'accessibility', 'slug' => 'accessible-pitch', 'sort_order' => 20, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Barrierefreier Stellplatz', 'en' => 'Accessible pitch'],
            ['category_slug' => 'accessibility', 'slug' => 'accessible-toilet', 'sort_order' => 30, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Barrierefreies WC', 'en' => 'Accessible toilet'],
            ['category_slug' => 'accessibility', 'slug' => 'accessible-shower', 'sort_order' => 40, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Barrierefreie Dusche', 'en' => 'Accessible shower'],
            ['category_slug' => 'accessibility', 'slug' => 'accessible-reception', 'sort_order' => 50, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Barrierefreie Rezeption', 'en' => 'Accessible reception'],
            ['category_slug' => 'accessibility', 'slug' => 'accessible-common-room', 'sort_order' => 60, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Barrierefreier Aufenthaltsraum', 'en' => 'Accessible common room'],
            ['category_slug' => 'accessibility', 'slug' => 'wheelchair-accessible-paths', 'sort_order' => 70, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Rollstuhlgerechte Wege', 'en' => 'Wheelchair-accessible paths'],
        ];

        foreach ($features as $feature) {
            DB::table('features')->updateOrInsert(
                ['slug' => $feature['slug']],
                [
                    'category_id' => $categoryIds[$feature['category_slug']],
                    'icon_id' => null,
                    'sort_order' => $feature['sort_order'],
                    'is_active' => true,
                    'is_searchable' => true,
                    'internal_comment' => null,
                    'value_type' => $feature['value_type'],
                    'unit_type' => $feature['unit_type'],
                    'approval_status' => 'approved',
                    'suggested_by' => null,
                    'reviewed_by' => null,
                    'reviewed_at' => now(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            $featureId = DB::table('features')
                ->where('slug', $feature['slug'])
                ->value('id');

            foreach (['de', 'en'] as $locale) {
                DB::table('translations')->updateOrInsert(
                    [
                        'entity_type' => 'feature',
                        'entity_id' => $featureId,
                        'locale' => $locale,
                        'field' => 'name',
                    ],
                    [
                        'value' => $feature[$locale],
                        'is_active' => true,
                        'internal_comment' => null,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }

        $options = [
            'surface' => [
                ['slug' => 'asphalt', 'sort_order' => 10, 'de' => 'Asphalt', 'en' => 'Asphalt'],
                ['slug' => 'gravel', 'sort_order' => 20, 'de' => 'Schotter', 'en' => 'Gravel'],
                ['slug' => 'grass', 'sort_order' => 30, 'de' => 'Wiese/Gras', 'en' => 'Grass'],
                ['slug' => 'mixed', 'sort_order' => 40, 'de' => 'Gemischt', 'en' => 'Mixed'],
            ],
        ];

        foreach ($options as $featureSlug => $featureOptions) {
            $featureId = DB::table('features')->where('slug', $featureSlug)->value('id');

            if (! $featureId) {
                continue;
            }

            foreach ($featureOptions as $option) {
                DB::table('feature_options')->updateOrInsert(
                    [
                        'feature_id' => $featureId,
                        'slug' => $option['slug'],
                    ],
                    [
                        'sort_order' => $option['sort_order'],
                        'is_active' => true,
                        'is_searchable' => true,
                        'internal_comment' => null,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );

                $optionId = DB::table('feature_options')
                    ->where('feature_id', $featureId)
                    ->where('slug', $option['slug'])
                    ->value('id');

                foreach (['de', 'en'] as $locale) {
                    DB::table('translations')->updateOrInsert(
                        [
                            'entity_type' => 'feature_option',
                            'entity_id' => $optionId,
                            'locale' => $locale,
                            'field' => 'name',
                        ],
                        [
                            'value' => $option[$locale],
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
}
