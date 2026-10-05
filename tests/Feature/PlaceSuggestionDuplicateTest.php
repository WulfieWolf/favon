<?php

namespace Tests\Feature;

use App\Models\User;
use App\Http\Controllers\PlaceSuggestionController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PlaceSuggestionDuplicateTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_check_includes_published_and_pending_places_but_not_drafts(): void
    {
        $typeId = DB::table('place_types')->insertGetId([
            'slug' => 'duplicate-test',
            'icon_id' => null,
            'sort_order' => 10,
            'is_active' => true,
            'is_searchable' => true,
            'internal_comment' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::factory()->create();

        foreach ([
            ['Published nearby', 'published', 51.0000, 7.0000],
            ['Pending nearby', 'pending', 51.0005, 7.0005],
            ['Draft nearby', 'draft', 51.0003, 7.0003],
            ['Published far away', 'published', 51.0500, 7.0500],
        ] as [$name, $status, $lat, $lng]) {
            DB::table('places')->insert([
                'place_type_id' => $typeId,
                'name' => $name,
                'slug' => str($name)->slug().'-'.uniqid(),
                'latitude' => $lat,
                'longitude' => $lng,
                'publication_status' => $status,
                'legal_status' => 'unclear',
                'opening_status' => 'unclear',
                'is_active' => true,
                'created_by' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $request = Request::create('/places/suggest/duplicates', 'GET', [
            'latitude' => 51.0000,
            'longitude' => 7.0000,
            'name' => 'Published nearby',
        ]);

        $response = app(PlaceSuggestionController::class)->nearbyDuplicates($request);
        $names = collect($response->getData(true)['matches'])->pluck('name')->all();

        $this->assertContains('Published nearby', $names);
        $this->assertContains('Pending nearby', $names);
        $this->assertNotContains('Draft nearby', $names);
        $this->assertNotContains('Published far away', $names);
    }
    public function test_duplicate_check_can_exclude_current_place_when_editing(): void
    {
        $typeId = DB::table('place_types')->insertGetId([
            'slug' => 'duplicate-edit-test',
            'icon_id' => null,
            'sort_order' => 10,
            'is_active' => true,
            'is_searchable' => true,
            'internal_comment' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::factory()->create();

        $currentPlaceId = DB::table('places')->insertGetId([
            'place_type_id' => $typeId,
            'name' => 'Current place',
            'slug' => 'current-place',
            'latitude' => 51.0000,
            'longitude' => 7.0000,
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => 'unclear',
            'is_active' => true,
            'created_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('places')->insert([
            'place_type_id' => $typeId,
            'name' => 'Other nearby place',
            'slug' => 'other-nearby-place',
            'latitude' => 51.0004,
            'longitude' => 7.0004,
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => 'unclear',
            'is_active' => true,
            'created_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create('/places/suggest/duplicates', 'GET', [
            'latitude' => 51.0000,
            'longitude' => 7.0000,
            'name' => 'Current place',
            'exclude_place_id' => $currentPlaceId,
        ]);

        $response = app(PlaceSuggestionController::class)->nearbyDuplicates($request);
        $names = collect($response->getData(true)['matches'])->pluck('name')->all();

        $this->assertNotContains('Current place', $names);
        $this->assertContains('Other nearby place', $names);
    }

}
