<?php

namespace Tests\Feature;

use App\Services\Imports\ExternalFeatureMaterializationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExternalFeatureMaterializationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_definite_non_conflicting_source_values_are_materialized(): void
    {
        $placeId = $this->place();
        $toiletId = (int) DB::table('features')->where('slug', 'toilet')->value('id');
        $showerId = (int) DB::table('features')->where('slug', 'shower')->value('id');

        $inserted = app(ExternalFeatureMaterializationService::class)->materializeForPlace($placeId, [
            'features' => [
                'toilet' => [
                    'status' => 'available',
                    'conflict' => false,
                ],
                'shower' => [
                    'status' => 'available',
                    'conflict' => true,
                ],
            ],
        ]);

        $this->assertSame(1, $inserted);
        $this->assertDatabaseHas('place_features', [
            'place_id' => $placeId,
            'feature_id' => $toiletId,
            'status' => 'available',
            'is_active' => true,
        ]);
        $this->assertDatabaseMissing('place_features', [
            'place_id' => $placeId,
            'feature_id' => $showerId,
            'is_active' => true,
        ]);
    }

    public function test_existing_camperwolf_value_is_never_overwritten_by_source_materialization(): void
    {
        $placeId = $this->place();
        $toiletId = (int) DB::table('features')->where('slug', 'toilet')->value('id');
        $now = now();

        DB::table('place_features')->insert([
            'place_id' => $placeId,
            'feature_id' => $toiletId,
            'feature_option_id' => null,
            'status' => 'unavailable',
            'metadata' => null,
            'is_active' => true,
            'internal_comment' => 'Communitywert',
            'valid_from' => $now,
            'valid_until' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $inserted = app(ExternalFeatureMaterializationService::class)->materializeForPlace($placeId, [
            'features' => [
                'toilet' => [
                    'status' => 'available',
                    'conflict' => false,
                ],
            ],
        ]);

        $this->assertSame(0, $inserted);
        $this->assertDatabaseHas('place_features', [
            'place_id' => $placeId,
            'feature_id' => $toiletId,
            'status' => 'unavailable',
            'is_active' => true,
        ]);
        $this->assertDatabaseCount('place_features', 1);
    }

    private function place(): int
    {
        $placeTypeId = (int) DB::table('place_types')
            ->where('is_active', true)
            ->value('id');

        return (int) DB::table('places')->insertGetId([
            'place_type_id' => $placeTypeId,
            'name' => 'Materialization Test Place',
            'slug' => 'materialization-test-'.uniqid(),
            'latitude' => 51.4,
            'longitude' => 7.4,
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => 'unclear',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
