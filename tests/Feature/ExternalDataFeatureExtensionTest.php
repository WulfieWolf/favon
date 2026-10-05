<?php

namespace Tests\Feature;

use Database\Seeders\ExternalDataFeatureExtensionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExternalDataFeatureExtensionTest extends TestCase
{
    use RefreshDatabase;

    public function test_external_data_features_are_added_without_duplicating_existing_features(): void
    {
        $category = DB::table('feature_categories')->where('slug', 'medical-first-aid')->first();

        $this->assertNotNull($category);
        $this->assertTrue((bool) $category->is_active);

        $this->assertDatabaseHas('translations', [
            'entity_type' => 'feature_category',
            'entity_id' => $category->id,
            'locale' => 'de',
            'field' => 'name',
            'value' => 'Erste Hilfe & Medizin',
        ]);

        $this->assertDatabaseHas('features', [
            'slug' => 'defibrillator',
            'category_id' => $category->id,
            'value_type' => 'boolean',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('features', [
            'slug' => 'first-aid-equipment',
            'category_id' => $category->id,
            'value_type' => 'boolean',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('features', [
            'slug' => 'dumping-station',
            'value_type' => 'boolean',
            'is_active' => true,
        ]);

        foreach (['waste-bins', 'rest-picnic-area', 'toilet', 'shower', 'playground', 'fresh-water'] as $slug) {
            $this->assertSame(1, DB::table('features')->where('slug', $slug)->count(), $slug);
        }

        foreach (['defibrillator', 'first-aid-equipment', 'dumping-station'] as $slug) {
            $featureId = DB::table('features')->where('slug', $slug)->value('id');

            $config = json_decode(
                DB::table('feature_workflows')->where('feature_id', $featureId)->value('config'),
                true,
            );

            $this->assertSame('availability', $config['status_mode']);
            $this->assertSame(
                ['unknown', 'available', 'unavailable'],
                array_column($config['status_options'], 'value'),
            );
        }
    }

    public function test_extension_seeder_is_idempotent(): void
    {
        $before = DB::table('features')->count();

        (new ExternalDataFeatureExtensionSeeder)->run();
        (new ExternalDataFeatureExtensionSeeder)->run();

        $this->assertSame($before, DB::table('features')->count());
        $this->assertSame(
            1,
            DB::table('catalog_releases')
                ->where('release_key', 'external-data-features-2026-09-24')
                ->count(),
        );
    }
}
