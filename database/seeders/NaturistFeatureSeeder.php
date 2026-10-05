<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NaturistFeatureSeeder extends Seeder
{
    private const RELEASE = 'naturist-features-2026-09-29';

    public function run(): void
    {
        if (DB::table('catalog_releases')->where('release_key', self::RELEASE)->exists()) {
            return;
        }

        DB::transaction(function (): void {
            $rulesCategoryId = DB::table('feature_categories')->where('slug', 'rules')->value('id');
            if (! $rulesCategoryId) {
                return;
            }

            $features = [
                [
                    'slug' => 'naturist',
                    'sort_order' => 180,
                    'de' => 'FKK',
                    'en' => 'Naturist',
                    'statuses' => [
                        ['unknown', 'Unbekannt', 'Unknown'],
                        ['yes', 'Ja', 'Yes'],
                        ['temporary', 'Zeitweise', 'At certain times'],
                        ['no', 'Nein', 'No'],
                    ],
                ],
                [
                    'slug' => 'naturist-area',
                    'sort_order' => 190,
                    'de' => 'FKK-Bereich',
                    'en' => 'Naturist area',
                    'statuses' => [
                        ['unknown', 'Unbekannt', 'Unknown'],
                        ['yes', 'Ja', 'Yes'],
                        ['no', 'Nein', 'No'],
                    ],
                ],
            ];

            foreach ($features as $feature) {
                DB::table('features')->updateOrInsert(
                    ['slug' => $feature['slug']],
                    [
                        'category_id' => $rulesCategoryId,
                        'sort_order' => $feature['sort_order'],
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

                $featureId = (int) DB::table('features')->where('slug', $feature['slug'])->value('id');
                $this->translate($featureId, $feature['de'], $feature['en']);

                DB::table('feature_workflows')->updateOrInsert(
                    ['feature_id' => $featureId],
                    [
                        'config' => json_encode([
                            'status_mode' => 'custom',
                            'status_options' => array_map(
                                fn (array $status) => [
                                    'value' => $status[0],
                                    'label' => ['de' => $status[1], 'en' => $status[2]],
                                ],
                                $feature['statuses'],
                            ),
                            'details' => [],
                            'comment' => true,
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'sort_order' => $feature['sort_order'],
                        'is_active' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ],
                );

                $this->ensureVisibility($featureId);
            }

            DB::table('catalog_releases')->insert([
                'release_key' => self::RELEASE,
                'applied_at' => now(),
            ]);
        });
    }

    private function ensureVisibility(int $featureId): void
    {
        $visibility = [
            'campground' => 'standard',
            'motorhome-pitch' => 'standard',
            'tent-site' => 'standard',
            'free-pitch' => 'standard',
            'parking' => 'extended',
            'hiking-parking' => 'extended',
            'camping-outdoor' => 'extended',
            'rest-area' => 'hidden',
            'service-station' => 'hidden',
        ];

        $placeTypes = DB::table('place_types')
            ->whereIn('slug', array_keys($visibility))
            ->pluck('id', 'slug');

        foreach ($visibility as $slug => $value) {
            if (! isset($placeTypes[$slug])) {
                continue;
            }

            DB::table('feature_place_types')->updateOrInsert(
                ['feature_id' => $featureId, 'place_type_id' => $placeTypes[$slug]],
                [
                    'visibility' => $value,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }

    private function translate(int $featureId, string $de, string $en): void
    {
        foreach (['de' => $de, 'en' => $en] as $locale => $value) {
            DB::table('translations')->updateOrInsert(
                [
                    'entity_type' => 'feature',
                    'entity_id' => $featureId,
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
