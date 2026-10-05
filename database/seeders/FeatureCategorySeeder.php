<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FeatureCategorySeeder extends Seeder
{
    /**
     * Seed the feature categories and their translations.
     */
    public function run(): void
    {
        $categories = [
            [
                'slug' => 'utilities',
                'sort_order' => 10,
                'de' => 'Versorgung',
                'en' => 'Utilities',
            ],
            [
                'slug' => 'sanitary',
                'sort_order' => 20,
                'de' => 'Sanitär',
                'en' => 'Sanitary',
            ],
            [
                'slug' => 'facilities',
                'sort_order' => 30,
                'de' => 'Ausstattung',
                'en' => 'Facilities',
            ],
            [
                'slug' => 'access',
                'sort_order' => 40,
                'de' => 'Zufahrt & Platz',
                'en' => 'Access & pitch',
            ],
            [
                'slug' => 'rules',
                'sort_order' => 50,
                'de' => 'Regeln & Nutzung',
                'en' => 'Rules & use',
            ],
            [
                'slug' => 'surroundings',
                'sort_order' => 60,
                'de' => 'Umgebung',
                'en' => 'Surroundings',
            ],
            [
                'slug' => 'services',
                'sort_order' => 70,
                'de' => 'Service',
                'en' => 'Services',
            ],
            [
                'slug' => 'rental',
                'sort_order' => 80,
                'de' => 'Vermietung',
                'en' => 'Rental',
            ],
            [
                'slug' => 'accessibility',
                'sort_order' => 90,
                'de' => 'Barrierefreiheit',
                'en' => 'Accessibility',
            ],
        ];

        foreach ($categories as $category) {
            DB::table('feature_categories')->updateOrInsert(
                ['slug' => $category['slug']],
                [
                    'icon_id' => null,
                    'sort_order' => $category['sort_order'],
                    'is_active' => true,
                    'is_searchable' => true,
                    'internal_comment' => null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            $entityId = DB::table('feature_categories')
                ->where('slug', $category['slug'])
                ->value('id');

            foreach (['de', 'en'] as $locale) {
                DB::table('translations')->updateOrInsert(
                    [
                        'entity_type' => 'feature_category',
                        'entity_id' => $entityId,
                        'locale' => $locale,
                        'field' => 'name',
                    ],
                    [
                        'value' => $category[$locale],
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
