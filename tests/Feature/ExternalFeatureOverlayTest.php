<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\FeatureWorkflowService;
use App\Services\Imports\Datex2ParkingMapper;
use App\Services\Imports\Datex2ParkingParser;
use App\Services\Imports\ExternalFeatureOverlayService;
use App\Services\Imports\ExternalRecordClassificationService;
use App\Services\Imports\ExternalRecordReviewService;
use App\Services\Imports\ExternalRecordStagingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExternalFeatureOverlayTest extends TestCase
{
    use RefreshDatabase;

    public function test_real_datex_to_created_place_materializes_definite_toilet_value(): void
    {
        $xml = file_get_contents(base_path('tests/Fixtures/datex2-parking-sample.xml'));
        $this->assertIsString($xml);

        $toiletEquipment = <<<'XML'
            <parkingEquipmentOrServiceFacility equipmentOrServiceFacilityIndex="27">
              <parkingEquipmentOrServiceFacility xsi:type="Equipment"><availability>available</availability><equipmentType>toilet</equipmentType></parkingEquipmentOrServiceFacility>
            </parkingEquipmentOrServiceFacility>
XML;

        $xml = preg_replace(
            '/            <groupOfParkingSpaces groupIndex="1">/',
            $toiletEquipment."\n".'            <groupOfParkingSpaces groupIndex="1">',
            $xml,
            1,
            $replacements,
        );
        $this->assertIsString($xml);
        $this->assertSame(1, $replacements);

        $parser = app(Datex2ParkingParser::class);
        $mapper = app(Datex2ParkingMapper::class);
        $mapped = array_map(fn (array $record) => $mapper->map($record), $parser->parse($xml));

        $this->assertSame('available', $mapped[0]['features']['toilet']['status']);
        $this->assertFalse($mapped[0]['features']['toilet']['conflict']);

        $stage = app(ExternalRecordStagingService::class)->stageDatexParking($mapped, false);
        $sourceId = (int) $stage['source_id'];

        $classification = app(ExternalRecordClassificationService::class)->classifySource($sourceId);
        $this->assertSame(2, $classification['new_candidate']);

        $recordId = (int) DB::table('external_records')
            ->where('external_source_id', $sourceId)
            ->where('external_id', 'DE-BW-081165')
            ->value('id');

        $actor = User::factory()->create(['email_verified_at' => now()]);
        $placeId = app(ExternalRecordReviewService::class)->createPlaceFromCandidate($recordId, $actor);

        $record = DB::table('external_records')->where('id', $recordId)->first();
        $this->assertSame($placeId, (int) $record->place_id);
        $this->assertSame('active', $record->status);
        $this->assertSame('created', $record->classification);

        $feature = $this->feature($placeId, 'toilet');
        $this->assertSame('available', $feature->status);
        $this->assertSame('available', $feature->display_status ?? $feature->status);

        $presented = app(FeatureWorkflowService::class)->present($feature);
        $this->assertSame('positive', $presented->tone);
    }

    public function test_external_value_is_used_as_display_fallback_without_changing_stored_status(): void
    {
        $placeId = $this->place('Fallback Place');
        $this->externalRecord($placeId, 'Source A', 'source-a', '2026-09-24 10:00:00', [
            'toilet' => ['status' => 'available', 'conflict' => false],
        ]);

        $feature = $this->feature($placeId, 'toilet');

        $this->assertSame('unknown', $feature->status);
        $this->assertSame('available', $feature->display_status);

        $presented = app(FeatureWorkflowService::class)->present($feature);
        $this->assertSame('positive', $presented->tone);
        $this->assertSame('unknown', $presented->status);
    }

    public function test_newest_external_value_creates_conflict_only_when_it_differs_from_internal_value(): void
    {
        $placeId = $this->place('Conflict Place');
        $this->setFeature($placeId, 'toilet', 'available');

        $this->externalRecord($placeId, 'Old Source', 'old-source', '2026-09-20 10:00:00', [
            'toilet' => ['status' => 'available', 'conflict' => false],
        ]);
        $this->externalRecord($placeId, 'Newest Source', 'newest-source', '2026-09-24 10:00:00', [
            'toilet' => ['status' => 'unavailable', 'conflict' => false],
        ]);

        $feature = $this->feature($placeId, 'toilet');

        $this->assertSame('available', $feature->status);
        $this->assertSame('available', $feature->display_status ?? $feature->status);
        $this->assertCount(1, $feature->external_feature_conflicts);
        $this->assertSame('Newest Source', $feature->external_feature_conflicts[0]['source_name']);
        $this->assertSame('unavailable', $feature->external_feature_conflicts[0]['status']);
    }

    public function test_older_disagreement_does_not_create_noise_when_newest_external_value_matches_internal_value(): void
    {
        $placeId = $this->place('No Noise Place');
        $this->setFeature($placeId, 'toilet', 'available');

        $this->externalRecord($placeId, 'Old Source', 'old-source', '2026-09-20 10:00:00', [
            'toilet' => ['status' => 'unavailable', 'conflict' => false],
        ]);
        $this->externalRecord($placeId, 'Newest Source', 'newest-source', '2026-09-24 10:00:00', [
            'toilet' => ['status' => 'available', 'conflict' => false],
        ]);

        $feature = $this->feature($placeId, 'toilet');

        $this->assertSame('available', $feature->status);
        $this->assertSame([], $feature->external_feature_conflicts);
    }

    public function test_source_internal_conflict_flag_is_not_used_as_fallback_or_conflict(): void
    {
        $placeId = $this->place('Source Conflict Place');
        $this->externalRecord($placeId, 'Broken Source', 'broken-source', '2026-09-24 10:00:00', [
            'toilet' => ['status' => 'available', 'conflict' => true],
        ]);

        $feature = $this->feature($placeId, 'toilet');

        $this->assertSame('unknown', $feature->status);
        $this->assertFalse(isset($feature->display_status));
        $this->assertSame([], $feature->external_feature_conflicts);
    }

    public function test_profile_exposes_external_fallback_status_separately_from_persisted_status(): void
    {
        $placeId = $this->place('Frontend Fallback Place');
        $slug = DB::table('places')->where('id', $placeId)->value('slug');

        $this->externalRecord($placeId, 'Mobilithek Testquelle', 'mobilithek-frontend', '2026-09-24 10:00:00', [
            'toilet' => ['status' => 'available', 'conflict' => false],
        ]);

        $this->withSession(['locale' => 'de'])
            ->get(route('places.show', $slug))
            ->assertOk()
            ->assertSee('data-original-status="unknown"', false)
            ->assertSee('data-display-status="available"', false);
    }

    public function test_profile_shows_conflict_inside_existing_feature_info_tooltip(): void
    {
        $placeId = $this->place('Tooltip Place');
        $slug = DB::table('places')->where('id', $placeId)->value('slug');
        $this->setFeature($placeId, 'toilet', 'available', 'Zugang auf der Rückseite des Gebäudes.');
        $this->externalRecord($placeId, 'Mobilithek Testquelle', 'mobilithek-test', '2026-09-24 10:00:00', [
            'toilet' => ['status' => 'unavailable', 'conflict' => false],
        ]);

        $this->withSession(['locale' => 'de'])
            ->get(route('places.show', $slug))
            ->assertOk()
            ->assertSee('Zugang auf der Rückseite des Gebäudes.')
            ->assertSee('Laut Mobilithek Testquelle ist dieses Merkmal nicht vorhanden.');
    }

    private function feature(int $placeId, string $slug): object
    {
        $groups = app(ExternalFeatureOverlayService::class)->apply(
            $placeId,
            app(FeatureWorkflowService::class)->groupedForPlace($placeId),
        );

        $feature = $groups
            ->flatMap(fn ($group) => $group->features)
            ->first(fn ($feature) => $feature->feature_slug === $slug);

        $this->assertNotNull($feature, "Feature {$slug} not found.");

        return $feature;
    }

    private function place(string $name): int
    {
        $typeId = DB::table('place_types')->where('slug', 'rest-area')->value('id')
            ?: DB::table('place_types')->where('is_active', true)->value('id');

        return (int) DB::table('places')->insertGetId([
            'place_type_id' => $typeId,
            'name' => $name,
            'slug' => strtolower(str_replace(' ', '-', $name)).'-'.uniqid(),
            'latitude' => 51.45,
            'longitude' => 7.01,
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => 'unclear',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function setFeature(int $placeId, string $slug, string $status, ?string $comment = null): void
    {
        $featureId = DB::table('features')->where('slug', $slug)->value('id');
        $this->assertNotNull($featureId);

        $placeFeatureId = (int) DB::table('place_features')->insertGetId([
            'place_id' => $placeId,
            'feature_id' => $featureId,
            'feature_option_id' => null,
            'status' => $status,
            'value_number' => null,
            'value_text' => null,
            'unit_key' => null,
            'metadata' => null,
            'is_active' => true,
            'internal_comment' => 'Test community value',
            'valid_from' => now(),
            'valid_until' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($comment !== null) {
            DB::table('place_feature_notes')->insert([
                'place_feature_id' => $placeFeatureId,
                'locale' => 'de',
                'note' => $comment,
                'is_active' => true,
                'internal_comment' => 'Test note',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function externalRecord(int $placeId, string $sourceName, string $sourceSlug, string $updatedAt, array $features): void
    {
        $sourceId = (int) DB::table('external_sources')->insertGetId([
            'slug' => $sourceSlug.'-'.uniqid(),
            'name' => $sourceName,
            'source_type' => 'test',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $normalized = [
            'external_id' => 'EXT-'.uniqid(),
            'place' => [
                'name' => 'External source record',
                'latitude' => 51.45,
                'longitude' => 7.01,
            ],
            'features' => $features,
        ];

        DB::table('external_records')->insert([
            'external_source_id' => $sourceId,
            'external_id' => $normalized['external_id'],
            'place_id' => $placeId,
            'status' => 'active',
            'classification' => 'linked',
            'normalized_hash' => hash('sha256', json_encode($normalized)),
            'normalized_data' => json_encode($normalized),
            'source_updated_at' => $updatedAt,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
