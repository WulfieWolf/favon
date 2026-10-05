<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PlaceBrowseMapStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_map_markers_include_favorites_and_restore_the_exact_filtered_view(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $placeTypeId = DB::table('place_types')->insertGetId([
            'slug' => 'map-state-test',
            'icon_id' => null,
            'sort_order' => 10,
            'is_active' => true,
            'is_searchable' => true,
            'internal_comment' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $favoriteId = $this->place($placeTypeId, 'Favorit', 'favorit', 51.0, 7.0);
        $otherId = $this->place($placeTypeId, 'Anderer Platz', 'anderer-platz', 51.2, 7.2);

        DB::table('place_favorites')->insert([
            'user_id' => $user->id,
            'place_id' => $favoriteId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard', [
            'map_filter' => 1,
            'north' => 52,
            'south' => 50,
            'east' => 8,
            'west' => 6,
            'map_lat' => 51.1,
            'map_lng' => 7.1,
            'map_zoom' => 11,
        ]));

        $response->assertOk();
        $response->assertSee('style="z-index:2147482000"', false);
        $response->assertSee('map.setView([51.1657, 10.4515], 6);', false);
        $response->assertSee('if (!mapInteracted) return;', false);
        $response->assertViewHas('mapView', [
            'latitude' => 51.1,
            'longitude' => 7.1,
            'zoom' => 11,
        ]);
        $response->assertViewHas('markerLimit', 1000);
        $response->assertViewHas('markers', function ($markers) use ($favoriteId, $otherId): bool {
            $markers = collect($markers)->keyBy('id');

            return $markers->get($favoriteId)['is_favorite'] === true
                && $markers->get($otherId)['is_favorite'] === false;
        });
    }


    public function test_map_marker_limit_uses_a_stable_distributed_order_instead_of_lowest_ids(): void
    {
        $placeTypeId = DB::table('place_types')->insertGetId([
            'slug' => 'map-shuffle-test',
            'icon_id' => null,
            'sort_order' => 10,
            'is_active' => true,
            'is_searchable' => true,
            'internal_comment' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $rows = [];
        $now = now();

        for ($i = 1; $i <= 1100; $i++) {
            $rows[] = [
                'place_type_id' => $placeTypeId,
                'name' => 'Shuffle Platz '.$i,
                'slug' => 'shuffle-platz-'.$i,
                'latitude' => 47.0 + (($i % 400) / 100),
                'longitude' => 6.0 + (($i % 700) / 100),
                'publication_status' => 'published',
                'legal_status' => 'unclear',
                'opening_status' => 'unclear',
                'is_active' => true,
                'created_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('places')->insert($chunk);
        }

        $allIds = DB::table('places')
            ->where('place_type_id', $placeTypeId)
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id);

        $lowestThousand = $allIds->take(1000)->values()->all();

        $first = $this->get(route('dashboard'));
        $second = $this->get(route('dashboard'));

        $first->assertOk();
        $second->assertOk();

        $firstMarkers = collect($first->viewData('markers'))->pluck('id')->map(fn ($id) => (int) $id)->values();
        $secondMarkers = collect($second->viewData('markers'))->pluck('id')->map(fn ($id) => (int) $id)->values();

        self::assertCount(1000, $firstMarkers);
        self::assertSame($firstMarkers->all(), $secondMarkers->all());
        self::assertNotSame($lowestThousand, $firstMarkers->all());
        self::assertTrue($firstMarkers->contains(fn (int $id): bool => $id > $lowestThousand[array_key_last($lowestThousand)]));
    }


    public function test_sorted_result_page_places_are_always_present_in_the_marker_set(): void
    {
        $placeTypeId = DB::table('place_types')->insertGetId([
            'slug' => 'map-sort-alignment-test',
            'icon_id' => null,
            'sort_order' => 10,
            'is_active' => true,
            'is_searchable' => true,
            'internal_comment' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $rows = [];
        $now = now();

        for ($i = 1; $i <= 1100; $i++) {
            $rows[] = [
                'place_type_id' => $placeTypeId,
                'name' => sprintf('Sort Platz %04d', 1101 - $i),
                'slug' => 'sort-platz-'.$i,
                'latitude' => 47.0 + (($i % 400) / 100),
                'longitude' => 6.0 + (($i % 700) / 100),
                'publication_status' => 'published',
                'legal_status' => 'unclear',
                'opening_status' => 'unclear',
                'is_active' => true,
                'created_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('places')->insert($chunk);
        }

        $response = $this->get(route('dashboard', ['sort' => 'name']));

        $response->assertOk();

        $markerIds = collect($response->viewData('markers'))
            ->pluck('id')
            ->map(fn ($id) => (int) $id);

        $resultIds = $response->viewData('places')
            ->getCollection()
            ->pluck('id')
            ->map(fn ($id) => (int) $id);

        self::assertNotEmpty($resultIds);
        self::assertTrue($resultIds->every(fn (int $id): bool => $markerIds->contains($id)));
    }


    public function test_last_changed_sort_uses_latest_place_history_and_falls_back_to_created_at(): void
    {
        $placeTypeId = DB::table('place_types')->insertGetId([
            'slug' => 'last-changed-sort-test',
            'icon_id' => null,
            'sort_order' => 10,
            'is_active' => true,
            'is_searchable' => true,
            'internal_comment' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $olderWithRecentChange = DB::table('places')->insertGetId([
            'place_type_id' => $placeTypeId,
            'name' => 'Alter Platz mit neuer Änderung',
            'slug' => 'alter-platz-mit-neuer-aenderung',
            'latitude' => 51.0,
            'longitude' => 7.0,
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => 'unclear',
            'is_active' => true,
            'created_by' => null,
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10),
        ]);

        $newerWithoutHistory = DB::table('places')->insertGetId([
            'place_type_id' => $placeTypeId,
            'name' => 'Neuer Platz ohne Verlauf',
            'slug' => 'neuer-platz-ohne-verlauf',
            'latitude' => 51.1,
            'longitude' => 7.1,
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => 'unclear',
            'is_active' => true,
            'created_by' => null,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        DB::table('place_history')->insert([
            'place_id' => $olderWithRecentChange,
            'user_id' => null,
            'external_source_id' => null,
            'actor_type' => 'system',
            'action' => 'test_change',
            'summary' => 'Teständerung',
            'metadata' => null,
            'is_public' => true,
            'created_at' => now(),
        ]);

        $response = $this->get(route('dashboard', ['sort' => 'changed']));

        $response->assertOk();

        $resultIds = $response->viewData('places')
            ->getCollection()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        self::assertSame((int) $olderWithRecentChange, $resultIds->first());
        self::assertTrue($resultIds->contains((int) $newerWithoutHistory));
    }

    public function test_nearby_candidates_use_the_active_place_filters(): void
    {
        $wantedTypeId = DB::table('place_types')->insertGetId([
            'slug' => 'nearby-wanted',
            'icon_id' => null,
            'sort_order' => 10,
            'is_active' => true,
            'is_searchable' => true,
            'internal_comment' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $otherTypeId = DB::table('place_types')->insertGetId([
            'slug' => 'nearby-other',
            'icon_id' => null,
            'sort_order' => 20,
            'is_active' => true,
            'is_searchable' => true,
            'internal_comment' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $wantedId = $this->place($wantedTypeId, 'Passender Platz', 'passender-platz', 51.0, 7.0);
        $this->place($otherTypeId, 'Falscher Platz', 'falscher-platz', 51.01, 7.01);

        $response = $this->getJson(route('dashboard', [
            'nearby_candidates' => 1,
            'place_types' => ['nearby-wanted'],
        ]));

        $response
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonCount(1, 'places')
            ->assertJsonPath('places.0.id', $wantedId)
            ->assertJsonPath('places.0.name', 'Passender Platz');
    }

    private function place(int $placeTypeId, string $name, string $slug, float $latitude, float $longitude): int
    {
        return (int) DB::table('places')->insertGetId([
            'place_type_id' => $placeTypeId,
            'name' => $name,
            'slug' => $slug,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => 'unclear',
            'is_active' => true,
            'created_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
