<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UtilitiesFeatureRefinementSeeder extends Seeder
{
    /**
     * Refine the initial utilities feature set.
     */
    public function run(): void
    {
        $utilitiesCategoryId = DB::table('feature_categories')
            ->where('slug', 'utilities')
            ->value('id');

        if (! $utilitiesCategoryId) {
            return;
        }

        DB::table('features')
            ->where('slug', 'fresh-water-distance')
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);

        $features = [
            [
                'slug' => 'electricity-price',
                'sort_order' => 80,
                'value_type' => 'number',
                'unit_type' => 'currency',
                'de' => 'Strompreis',
                'en' => 'Electricity price',
            ],
            [
                'slug' => 'fresh-water-price',
                'sort_order' => 90,
                'value_type' => 'number',
                'unit_type' => 'currency',
                'de' => 'Frischwasserpreis',
                'en' => 'Fresh water price',
            ],
            [
                'slug' => 'water-available-without-stay',
                'sort_order' => 100,
                'value_type' => 'boolean',
                'unit_type' => null,
                'de' => 'Wasser auch ohne Übernachtung nutzbar',
                'en' => 'Water available without overnight stay',
            ],
        ];

        foreach ($features as $feature) {
            DB::table('features')->updateOrInsert(
                ['slug' => $feature['slug']],
                [
                    'category_id' => $utilitiesCategoryId,
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
    }
}
