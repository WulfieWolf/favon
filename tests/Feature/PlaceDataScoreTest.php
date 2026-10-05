<?php

namespace Tests\Feature;

use App\Services\PlaceDataScoreService;
use App\Services\PlaceHistoryService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PlaceDataScoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_data_score_uses_six_basis_components_and_known_relevant_features(): void
    {
        $this->seed(DatabaseSeeder::class);

        $placeTypeId = (int) DB::table('place_types')->where('slug', 'campground')->value('id');
        $placeId = DB::table('places')->insertGetId([
            'place_type_id' => $placeTypeId,
            'name' => 'DataScore Testplatz',
            'slug' => 'datascore-testplatz',
            'latitude' => 51.45,
            'longitude' => 7.01,
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => 'open',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('place_details')->insert([
            'place_id' => $placeId,
            'operator_name' => 'Test Betreiber',
            'is_active' => true,
            'version_valid_from' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('place_addresses')->insert([
            'place_id' => $placeId,
            'country_code' => 'DE',
            'postal_code' => '45127',
            'city' => 'Essen',
            'street' => 'Teststraße',
            'house_number' => null,
            'is_active' => true,
            'version_valid_from' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([
            ['telephone', '+49 201 123456'],
            ['email', 'camping@example.test'],
            ['website', 'https://example.test'],
        ] as [$type, $value]) {
            DB::table('place_contacts')->insert([
                'place_id' => $placeId,
                'contact_type' => $type,
                'value' => $value,
                'is_active' => true,
                'version_valid_from' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $featureId = DB::table('feature_place_types as fpt')
            ->join('features as f', 'f.id', '=', 'fpt.feature_id')
            ->join('feature_categories as fc', 'fc.id', '=', 'f.category_id')
            ->join('feature_workflows as fw', 'fw.feature_id', '=', 'f.id')
            ->where('fpt.place_type_id', $placeTypeId)
            ->whereIn('fpt.visibility', ['standard', 'extended'])
            ->where('f.is_active', true)
            ->where('fc.is_active', true)
            ->where('fw.is_active', true)
            ->value('f.id');

        $this->assertNotNull($featureId);

        DB::table('place_features')->insert([
            'place_id' => $placeId,
            'feature_id' => $featureId,
            'status' => 'unavailable',
            'is_active' => true,
            'valid_from' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $score = app(PlaceDataScoreService::class)->recalculate($placeId);

        $this->assertNotNull($score);
        $this->assertSame(6, $score['basis_known']);
        $this->assertSame(6, $score['basis_total']);
        $this->assertSame(1, $score['feature_known']);
        $this->assertGreaterThan(1, $score['feature_total']);
        $this->assertEqualsWithDelta(10.0, $score['basis_score'], 0.0001);

        $expectedFeature = 10 / $score['feature_total'];
        $expectedOverall = (10.0 * 0.75) + ($expectedFeature * 0.25);

        $this->assertEqualsWithDelta($expectedFeature, $score['feature_score'], 0.0001);
        $this->assertEqualsWithDelta($expectedOverall, $score['score'], 0.0001);
        $this->assertFalse((bool) DB::table('places')->where('id', $placeId)->value('data_score_dirty'));
    }

    public function test_place_history_marks_score_dirty_and_dirty_command_rebuilds_it(): void
    {
        $this->seed(DatabaseSeeder::class);

        $placeTypeId = (int) DB::table('place_types')->where('slug', 'campground')->value('id');
        $placeId = DB::table('places')->insertGetId([
            'place_type_id' => $placeTypeId,
            'name' => 'Dirty DataScore Testplatz',
            'slug' => 'dirty-datascore-testplatz',
            'latitude' => 51.45,
            'longitude' => 7.01,
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => 'unclear',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(PlaceDataScoreService::class)->recalculate($placeId);
        $this->assertFalse((bool) DB::table('places')->where('id', $placeId)->value('data_score_dirty'));

        app(PlaceHistoryService::class)->addSystem(
            $placeId,
            'test_change',
            'Teständerung',
        );

        $this->assertTrue((bool) DB::table('places')->where('id', $placeId)->value('data_score_dirty'));

        $this->artisan('places:recalculate-data-scores', ['--dirty' => true])
            ->expectsOutput('1 DatenScores neu berechnet.')
            ->assertSuccessful();

        $this->assertFalse((bool) DB::table('places')->where('id', $placeId)->value('data_score_dirty'));
        $this->assertNotNull(DB::table('places')->where('id', $placeId)->value('data_score_calculated_at'));
    }

    public function test_score_color_thresholds_are_stable(): void
    {
        $service = app(PlaceDataScoreService::class);

        $this->assertSame('red', $service->level(4.999));
        $this->assertSame('yellow', $service->level(5.0));
        $this->assertSame('yellow', $service->level(6.999));
        $this->assertSame('green', $service->level(7.0));
    }
}
