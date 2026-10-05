<?php

namespace Tests\Feature;

use App\Services\Imports\ExternalRecordClassificationService;
use App\Services\Imports\ExternalReviewSignalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DeletedPlaceImportReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_changed_linked_external_record_is_reviewed_once_after_tombstone(): void
    {
        $typeId = $this->placeType('deleted-import-campground');
        $placeId = $this->tombstone($typeId, 51.45, 7.01);
        $sourceId = $this->source('deleted-import-linked');

        $normalized = json_encode([
            'place' => [
                'name' => 'Changed source name',
                'suggested_place_type' => 'deleted-import-campground',
                'latitude' => 51.45,
                'longitude' => 7.01,
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $recordId = DB::table('external_records')->insertGetId([
            'external_source_id' => $sourceId,
            'external_id' => 'LINKED-1',
            'place_id' => $placeId,
            'status' => 'active',
            'normalized_hash' => 'new-hash',
            'tombstone_review_hash' => 'old-hash',
            'normalized_data' => $normalized,
            'classification' => 'created',
            'first_seen_at' => now(),
            'last_seen_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $runId = $this->importRun($sourceId);
        $service = app(ExternalReviewSignalService::class);

        $this->assertSame(1, $service->queueDeletedPlaceChanges($sourceId, $runId));
        $this->assertDatabaseHas('external_import_review_items', [
            'external_record_id' => $recordId,
            'place_id' => $placeId,
            'type' => 'deleted_place_changed',
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('external_records', [
            'id' => $recordId,
            'tombstone_review_hash' => 'new-hash',
        ]);

        $this->assertSame(0, $service->queueDeletedPlaceChanges($sourceId, $this->importRun($sourceId)));
    }

    public function test_unlinked_external_record_near_tombstone_becomes_review_instead_of_candidate(): void
    {
        config([
            'place_tombstones.block_radius_m' => 20,
            'place_tombstones.warning_radius_m' => 100,
        ]);

        $typeId = $this->placeType('deleted-import-nearby');
        $placeId = $this->tombstone($typeId, 51.45, 7.01);
        $sourceId = $this->source('deleted-import-unlinked');

        $normalized = json_encode([
            'place' => [
                'name' => 'Potential return',
                'suggested_place_type' => 'deleted-import-nearby',
                'latitude' => 51.45001,
                'longitude' => 7.01,
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $recordId = DB::table('external_records')->insertGetId([
            'external_source_id' => $sourceId,
            'external_id' => 'UNLINKED-1',
            'place_id' => null,
            'status' => 'active',
            'normalized_hash' => hash('sha256', $normalized),
            'normalized_data' => $normalized,
            'classification' => null,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(ExternalRecordClassificationService::class)->classifySource($sourceId, true);

        $this->assertDatabaseHas('external_records', [
            'id' => $recordId,
            'classification' => 'needs_review',
            'place_id' => null,
        ]);
        $this->assertDatabaseHas('external_import_review_items', [
            'external_record_id' => $recordId,
            'place_id' => $placeId,
            'type' => 'nearby_deleted_place',
            'status' => 'pending',
        ]);
    }

    private function placeType(string $slug): int
    {
        return (int) DB::table('place_types')->insertGetId([
            'slug' => $slug,
            'icon_id' => null,
            'sort_order' => 10,
            'is_active' => true,
            'is_searchable' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function tombstone(int $typeId, float $lat, float $lon): int
    {
        return (int) DB::table('places')->insertGetId([
            'place_type_id' => $typeId,
            'name' => null,
            'slug' => null,
            'latitude' => $lat,
            'longitude' => $lon,
            'publication_status' => 'deleted',
            'legal_status' => 'unclear',
            'opening_status' => 'unclear',
            'is_active' => false,
            'deleted_at' => now(),
            'deletion_reason' => 'operator_request',
            'deletion_note' => 'Internal test reason',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function source(string $slug): int
    {
        return (int) DB::table('external_sources')->insertGetId([
            'slug' => $slug,
            'name' => $slug,
            'source_type' => 'api',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function importRun(int $sourceId): int
    {
        return (int) DB::table('external_import_runs')->insertGetId([
            'external_source_id' => $sourceId,
            'mode' => 'sync',
            'status' => 'completed',
            'started_at' => now(),
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
