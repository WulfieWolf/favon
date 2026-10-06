<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_contains_only_active_published_places(): void
    {
        $this->seed(DatabaseSeeder::class);

        $placeTypeId = (int) DB::table('place_types')->value('id');
        if ($placeTypeId === 0) {
            $placeTypeId = (int) DB::table('place_types')->insertGetId([
                'slug' => 'sitemap-test',
                'icon_id' => null,
                'sort_order' => 10,
                'is_active' => true,
                'is_searchable' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach ([
            ['name' => 'Public Place', 'slug' => 'public-place', 'publication_status' => 'published', 'is_active' => true],
            ['name' => 'Draft Place', 'slug' => 'draft-place', 'publication_status' => 'draft', 'is_active' => true],
            ['name' => 'Inactive Place', 'slug' => 'inactive-place', 'publication_status' => 'published', 'is_active' => false],
        ] as $place) {
            DB::table('places')->insert([
                'place_type_id' => $placeTypeId,
                'name' => $place['name'],
                'slug' => $place['slug'],
                'latitude' => 51.45,
                'longitude' => 7.01,
                'publication_status' => $place['publication_status'],
                'legal_status' => 'unclear',
                'opening_status' => 'unclear',
                'is_active' => $place['is_active'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $response->assertSee(route('home'), false);
        $response->assertSee(route('places.show', 'public-place'), false);
        $response->assertDontSee('draft-place', false);
        $response->assertDontSee('inactive-place', false);
    }
}
