<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SurroundingsFeatureSeeder extends Seeder
{
    public function run(): void
    {
        $categoryId = DB::table('feature_categories')
            ->where('slug', 'surroundings')
            ->value('id');

        if (! $categoryId) {
            return;
        }

        DB::table('features')->updateOrInsert(
            ['slug' => 'distance-to-bicycle-rental'],
            [
                'category_id' => $categoryId,
                'icon_id' => null,
                'sort_order' => 80,
                'is_active' => true,
                'is_searchable' => true,
                'internal_comment' => null,
                'value_type' => 'number',
                'unit_type' => 'distance',
                'approval_status' => 'approved',
                'suggested_by' => null,
                'reviewed_by' => null,
                'reviewed_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $featureId = DB::table('features')
            ->where('slug', 'distance-to-bicycle-rental')
            ->value('id');

        foreach ([
            'de' => 'Entfernung zum Fahrradverleih',
            'en' => 'Distance to bicycle rental',
        ] as $locale => $value) {
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
                ]
            );
        }
    }
}
