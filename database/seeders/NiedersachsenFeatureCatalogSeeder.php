<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NiedersachsenFeatureCatalogSeeder extends Seeder
{
    private const RELEASE = 'niedersachsen-feature-catalog-2026-09-28';

    private const PLACE_TYPES = [
        'campground', 'motorhome-pitch', 'tent-site', 'parking', 'hiking-parking',
        'rest-area', 'free-pitch', 'service-station', 'camping-outdoor',
    ];

    public function run(): void
    {
        if (DB::table('catalog_releases')->where('release_key', self::RELEASE)->exists()) {
            return;
        }

        DB::transaction(function (): void {
            $this->ensureCategory('payment', 85, 'Bezahlung', 'Payment');
            $this->ensureCategory('suitability', 58, 'Geeignet für', 'Suitable for');

            $surroundingsId = DB::table('feature_categories')->where('slug', 'surroundings')->value('id');
            if ($surroundingsId) {
                $this->translate('feature_category', (int) $surroundingsId, 'Lage & Umgebung', 'Location & surroundings');
            }

            foreach ($this->features() as $feature) {
                $this->ensureFeature(...$feature);
            }

            $this->ensureVisibility();

            DB::table('catalog_releases')->insert([
                'release_key' => self::RELEASE,
                'applied_at' => now(),
            ]);
        });
    }

    private function features(): array
    {
        return [
            ['payment', 'payment-cash', 10, 'boolean', null, 'Barzahlung', 'Cash'],
            ['payment', 'payment-debit-card', 20, 'boolean', null, 'Girocard / Debitkarte', 'Debit card'],
            ['payment', 'payment-credit-card', 30, 'boolean', null, 'Kreditkarte', 'Credit card'],
            ['payment', 'payment-paypal', 40, 'boolean', null, 'PayPal', 'PayPal'],
            ['payment', 'payment-apple-pay', 50, 'boolean', null, 'Apple Pay', 'Apple Pay'],
            ['payment', 'payment-google-pay', 60, 'boolean', null, 'Google Pay', 'Google Pay'],
            ['payment', 'payment-bank-transfer', 70, 'boolean', null, 'Überweisung', 'Bank transfer'],
            ['payment', 'payment-invoice', 80, 'boolean', null, 'Rechnung', 'Invoice'],

            ['suitability', 'suitable-families', 10, 'boolean', null, 'Für Familien geeignet', 'Suitable for families'],
            ['suitability', 'suitable-groups', 20, 'boolean', null, 'Für Gruppen geeignet', 'Suitable for groups'],
            ['suitability', 'stroller-friendly', 30, 'boolean', null, 'Kinderwagentauglich', 'Stroller friendly'],

            ['surroundings', 'quiet-location', 210, 'boolean', null, 'Ruhige Lage', 'Quiet location'],
            ['surroundings', 'central-location', 220, 'boolean', null, 'Zentrale Lage', 'Central location'],
            ['surroundings', 'rural-location', 230, 'boolean', null, 'Ländliche Lage', 'Rural location'],
            ['surroundings', 'edge-of-town', 240, 'boolean', null, 'Ortsrand', 'Edge of town'],
            ['surroundings', 'at-lake', 250, 'boolean', null, 'Am See', 'By a lake'],
            ['surroundings', 'at-beach', 260, 'boolean', null, 'Am Strand', 'By the beach'],
            ['surroundings', 'near-river', 270, 'boolean', null, 'Flussnähe', 'Near a river'],
            ['surroundings', 'near-forest', 280, 'boolean', null, 'Waldnähe', 'Near a forest'],
            ['surroundings', 'at-coast', 290, 'boolean', null, 'An der Küste / am Meer', 'By the coast / sea'],
            ['surroundings', 'at-harbour', 300, 'boolean', null, 'Am Hafen', 'By a harbour'],
            ['surroundings', 'distance-to-forest', 310, 'number', 'distance', 'Entfernung zum Wald', 'Distance to forest'],
            ['surroundings', 'distance-to-bicycle-path', 320, 'number', 'distance', 'Entfernung zum Radweg', 'Distance to bicycle path'],
            ['surroundings', 'distance-to-station', 330, 'number', 'distance', 'Entfernung zum Bahnhof', 'Distance to station'],
            ['surroundings', 'distance-to-bus-stop', 340, 'number', 'distance', 'Entfernung zur Bushaltestelle', 'Distance to bus stop'],
            ['surroundings', 'distance-to-lake', 350, 'number', 'distance', 'Entfernung zum See', 'Distance to lake'],

            ['facilities', 'bicycle-storage', 180, 'boolean', null, 'Fahrradunterstellmöglichkeit', 'Bicycle storage'],
            ['utilities', 'water-hookup-at-pitch', 120, 'boolean', null, 'Wasseranschluss am Stellplatz', 'Water hookup at pitch'],
            ['utilities', 'wastewater-hookup-at-pitch', 130, 'boolean', null, 'Abwasseranschluss am Stellplatz', 'Wastewater hookup at pitch'],
            ['utilities', 'ebike-charging', 140, 'boolean', null, 'E-Bike-Lademöglichkeit', 'E-bike charging'],
        ];
    }

    private function ensureCategory(string $slug, int $sortOrder, string $de, string $en): void
    {
        DB::table('feature_categories')->updateOrInsert(
            ['slug' => $slug],
            [
                'sort_order' => $sortOrder,
                'is_active' => true,
                'is_searchable' => true,
                'is_system' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        $id = (int) DB::table('feature_categories')->where('slug', $slug)->value('id');
        $this->translate('feature_category', $id, $de, $en);
    }

    private function ensureFeature(string $categorySlug, string $slug, int $sortOrder, string $valueType, ?string $unitType, string $de, string $en): void
    {
        $categoryId = (int) DB::table('feature_categories')->where('slug', $categorySlug)->value('id');

        DB::table('features')->updateOrInsert(
            ['slug' => $slug],
            [
                'category_id' => $categoryId,
                'sort_order' => $sortOrder,
                'value_type' => $valueType,
                'unit_type' => $unitType,
                'approval_status' => 'approved',
                'reviewed_at' => now(),
                'is_active' => true,
                'is_searchable' => true,
                'is_system' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        $featureId = (int) DB::table('features')->where('slug', $slug)->value('id');
        $this->translate('feature', $featureId, $de, $en);

        $measurement = $valueType === 'number';
        DB::table('feature_workflows')->updateOrInsert(
            ['feature_id' => $featureId],
            [
                'config' => json_encode([
                    'status_mode' => $measurement ? 'measurement' : 'availability',
                    'status_options' => $measurement
                        ? [
                            ['value' => 'unknown', 'label' => ['de' => 'Unbekannt', 'en' => 'Unknown']],
                            ['value' => 'known', 'label' => ['de' => 'Bekannt', 'en' => 'Known']],
                        ]
                        : [
                            ['value' => 'unknown', 'label' => ['de' => 'Unbekannt', 'en' => 'Unknown']],
                            ['value' => 'available', 'label' => ['de' => 'Vorhanden', 'en' => 'Available']],
                            ['value' => 'unavailable', 'label' => ['de' => 'Nicht vorhanden', 'en' => 'Not available']],
                        ],
                    'details' => $measurement ? [[
                        'key' => 'value',
                        'type' => 'number',
                        'label' => ['de' => 'Entfernung', 'en' => 'Distance'],
                        'unit' => 'km',
                        'show_for' => ['known'],
                    ]] : [],
                    'comment' => true,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'sort_order' => $sortOrder,
                'is_active' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    private function ensureVisibility(): void
    {
        $placeTypes = DB::table('place_types')->whereIn('slug', self::PLACE_TYPES)->pluck('id', 'slug');
        $featureSlugs = collect($this->features())->pluck(1)->all();

        $features = DB::table('features as f')
            ->join('feature_categories as fc', 'fc.id', '=', 'f.category_id')
            ->whereIn('f.slug', $featureSlugs)
            ->get(['f.id', 'f.slug', 'fc.slug as category_slug']);

        foreach ($features as $feature) {
            foreach (self::PLACE_TYPES as $placeTypeSlug) {
                if (! isset($placeTypes[$placeTypeSlug])) {
                    continue;
                }

                $standard = match ((string) $feature->category_slug) {
                    'payment', 'surroundings' => self::PLACE_TYPES,
                    'suitability' => ['campground', 'motorhome-pitch', 'tent-site', 'free-pitch', 'camping-outdoor'],
                    'facilities' => ['campground', 'motorhome-pitch', 'tent-site', 'camping-outdoor'],
                    'utilities' => ['campground', 'motorhome-pitch', 'tent-site', 'rest-area', 'service-station'],
                    default => [],
                };

                $visibility = in_array($placeTypeSlug, $standard, true) ? 'standard' : 'extended';

                DB::table('feature_place_types')->updateOrInsert(
                    ['feature_id' => $feature->id, 'place_type_id' => $placeTypes[$placeTypeSlug]],
                    ['visibility' => $visibility, 'updated_at' => now(), 'created_at' => now()],
                );
            }
        }
    }

    private function translate(string $type, int $id, string $de, string $en): void
    {
        foreach (['de' => $de, 'en' => $en] as $locale => $value) {
            DB::table('translations')->updateOrInsert(
                ['entity_type' => $type, 'entity_id' => $id, 'locale' => $locale, 'field' => 'name'],
                ['value' => $value, 'is_active' => true, 'updated_at' => now(), 'created_at' => now()],
            );
        }
    }
}
