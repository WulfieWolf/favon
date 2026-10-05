<?php

namespace Tests\Feature;

use Database\Seeders\PlaceTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PlaceTypeCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_v1_place_types_are_seeded_with_de_and_en_labels(): void
    {
        $this->seed(PlaceTypeSeeder::class);

        foreach ([
            'campground',
            'motorhome-pitch',
            'tent-site',
            'parking',
            'hiking-parking',
            'rest-area',
            'free-pitch',
            'service-station',
            'camping-outdoor',
        ] as $slug) {
            $id = DB::table('place_types')->where('slug', $slug)->value('id');

            $this->assertNotNull($id, $slug);
            $this->assertDatabaseHas('translations', [
                'entity_type' => 'place_type',
                'entity_id' => $id,
                'locale' => 'de',
                'field' => 'name',
                'is_active' => true,
            ]);
            $this->assertDatabaseHas('translations', [
                'entity_type' => 'place_type',
                'entity_id' => $id,
                'locale' => 'en',
                'field' => 'name',
                'is_active' => true,
            ]);
        }

        $parkingId = DB::table('place_types')->where('slug', 'parking')->value('id');
        $hikingParkingId = DB::table('place_types')->where('slug', 'hiking-parking')->value('id');

        $parkingFeatures = DB::table('feature_place_types')
            ->where('place_type_id', $parkingId)
            ->orderBy('feature_id')
            ->get(['feature_id', 'visibility', 'filter_priority'])
            ->map(fn ($row) => (array) $row)
            ->values()
            ->all();

        $hikingParkingFeatures = DB::table('feature_place_types')
            ->where('place_type_id', $hikingParkingId)
            ->orderBy('feature_id')
            ->get(['feature_id', 'visibility', 'filter_priority'])
            ->map(fn ($row) => (array) $row)
            ->values()
            ->all();

        $this->assertSame($parkingFeatures, $hikingParkingFeatures);
    }
}
