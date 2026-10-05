<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExternalDataFeatureExtensionSeeder extends Seeder
{
    private const RELEASE = 'external-data-features-2026-09-24';

    private const PLACE_TYPES = [
        'campground',
        'motorhome-pitch',
        'tent-site',
        'parking',
        'hiking-parking',
        'rest-area',
        'free-pitch',
        'service-station',
        'camping-outdoor',
    ];

    public function run(): void
    {
        if (DB::table('catalog_releases')->where('release_key', self::RELEASE)->exists()) {
            return;
        }

        DB::transaction(function (): void {
            $this->ensureCategory(
                'medical-first-aid',
                75,
                'Erste Hilfe & Medizin',
                'First aid & medical',
            );

            // Already existing Camperwolf features intentionally reused for
            // DATEX-II mapping:
            // refuseBin        -> waste-bins
            // picnicFacilities -> rest-picnic-area
            // toilet           -> toilet
            // shower           -> shower
            // playground       -> playground
            // freshWater       -> fresh-water

            $this->ensureFeature(
                'medical-first-aid',
                'defibrillator',
                10,
                'Defibrillator',
                'Defibrillator',
            );

            $this->ensureFeature(
                'medical-first-aid',
                'first-aid-equipment',
                20,
                'Erste-Hilfe-Ausstattung',
                'First aid equipment',
            );

            // DATEX-II only states that a dumping station exists. It does not
            // reliably distinguish grey water from black water, so keep this
            // generic instead of inventing a more precise Camperwolf value.
            $this->ensureFeature(
                'utilities',
                'dumping-station',
                55,
                'Entsorgungsstation',
                'Dumping station',
            );

            $this->ensurePlaceTypeVisibility();

            DB::table('catalog_releases')->insert([
                'release_key' => self::RELEASE,
                'applied_at' => now(),
            ]);
        });
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

        $id = DB::table('feature_categories')->where('slug', $slug)->value('id');
        $this->translate('feature_category', (int) $id, $de, $en);
    }

    private function ensureFeature(
        string $categorySlug,
        string $slug,
        int $sortOrder,
        string $de,
        string $en,
    ): void {
        $categoryId = DB::table('feature_categories')->where('slug', $categorySlug)->value('id');

        DB::table('features')->updateOrInsert(
            ['slug' => $slug],
            [
                'category_id' => $categoryId,
                'sort_order' => $sortOrder,
                'value_type' => 'boolean',
                'unit_type' => null,
                'approval_status' => 'approved',
                'reviewed_at' => now(),
                'is_active' => true,
                'is_searchable' => true,
                'is_system' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        $featureId = DB::table('features')->where('slug', $slug)->value('id');
        $this->translate('feature', (int) $featureId, $de, $en);

        DB::table('feature_workflows')->updateOrInsert(
            ['feature_id' => $featureId],
            [
                'config' => json_encode([
                    'status_mode' => 'availability',
                    'status_options' => [
                        ['value' => 'unknown', 'label' => ['de' => 'Unbekannt', 'en' => 'Unknown']],
                        ['value' => 'available', 'label' => ['de' => 'Vorhanden', 'en' => 'Available']],
                        ['value' => 'unavailable', 'label' => ['de' => 'Nicht vorhanden', 'en' => 'Not available']],
                    ],
                    'details' => [],
                    'comment' => true,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'sort_order' => $sortOrder,
                'is_active' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    private function ensurePlaceTypeVisibility(): void
    {
        $placeTypes = DB::table('place_types')
            ->whereIn('slug', self::PLACE_TYPES)
            ->pluck('id', 'slug');

        $features = DB::table('features as f')
            ->join('feature_categories as fc', 'fc.id', '=', 'f.category_id')
            ->whereIn('f.slug', ['defibrillator', 'first-aid-equipment', 'dumping-station'])
            ->get(['f.id', 'f.slug', 'fc.slug as category_slug']);

        foreach ($features as $feature) {
            foreach (self::PLACE_TYPES as $placeTypeSlug) {
                if (! isset($placeTypes[$placeTypeSlug])) {
                    continue;
                }

                $visibility = match ((string) $feature->category_slug) {
                    'medical-first-aid' => 'standard',
                    'utilities' => in_array($placeTypeSlug, [
                        'campground',
                        'motorhome-pitch',
                        'tent-site',
                        'rest-area',
                        'service-station',
                    ], true) ? 'standard' : 'extended',
                    default => 'extended',
                };

                DB::table('feature_place_types')->updateOrInsert(
                    [
                        'feature_id' => $feature->id,
                        'place_type_id' => $placeTypes[$placeTypeSlug],
                    ],
                    [
                        'visibility' => $visibility,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ],
                );
            }
        }
    }

    private function translate(string $entityType, int $entityId, string $de, string $en): void
    {
        foreach (['de' => $de, 'en' => $en] as $locale => $value) {
            DB::table('translations')->updateOrInsert(
                [
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                    'locale' => $locale,
                    'field' => 'name',
                ],
                [
                    'value' => $value,
                    'is_active' => true,
                    'internal_comment' => null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }
}
