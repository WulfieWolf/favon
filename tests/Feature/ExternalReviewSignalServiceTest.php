<?php

namespace Tests\Feature;

use App\Services\Imports\ExternalReviewSignalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExternalReviewSignalServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_queues_possible_reopen_once_for_closed_linked_place_reported_open_by_source(): void
    {
        $sourceId = (int) DB::table('external_sources')->insertGetId([
            'slug' => 'reopen-signal-test',
            'name' => 'Reopen Signal Test',
            'source_type' => 'test',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $placeTypeId = (int) DB::table('place_types')->where('is_active', true)->value('id');
        $placeId = (int) DB::table('places')->insertGetId([
            'place_type_id' => $placeTypeId,
            'name' => 'Geschlossener Testplatz',
            'slug' => 'geschlossener-testplatz',
            'latitude' => 51.0,
            'longitude' => 7.0,
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => 'permanently_closed',
            'is_active' => true,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $mapped = [
            'external_id' => 'REOPEN-1',
            'place' => [
                'name' => 'Geschlossener Testplatz',
                'latitude' => 51.0,
                'longitude' => 7.0,
                'opening_status' => 'open',
            ],
        ];

        $recordId = (int) DB::table('external_records')->insertGetId([
            'external_source_id' => $sourceId,
            'external_id' => 'REOPEN-1',
            'place_id' => $placeId,
            'status' => 'active',
            'classification' => 'linked',
            'normalized_data' => json_encode($mapped),
            'normalized_hash' => hash('sha256', json_encode($mapped)),
            'first_seen_at' => now()->subDay(),
            'last_seen_at' => now(),
            'created_at' => now()->subDay(),
            'updated_at' => now(),
        ]);

        $runId = $this->importRun($sourceId);

        $service = app(ExternalReviewSignalService::class);

        self::assertSame(1, $service->queuePossibleReopens($sourceId, $runId));
        self::assertSame(0, $service->queuePossibleReopens($sourceId, $runId));

        $this->assertDatabaseHas('external_import_review_items', [
            'external_record_id' => $recordId,
            'place_id' => $placeId,
            'type' => 'possible_reopen',
            'status' => 'pending',
        ]);
    }

    public function test_it_does_not_queue_reopen_when_source_also_reports_closed(): void
    {
        $sourceId = (int) DB::table('external_sources')->insertGetId([
            'slug' => 'reopen-closed-source-test',
            'name' => 'Reopen Closed Source Test',
            'source_type' => 'test',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $placeTypeId = (int) DB::table('place_types')->where('is_active', true)->value('id');
        $placeId = (int) DB::table('places')->insertGetId([
            'place_type_id' => $placeTypeId,
            'name' => 'Weiter geschlossener Testplatz',
            'slug' => 'weiter-geschlossener-testplatz',
            'latitude' => 51.0,
            'longitude' => 7.0,
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => 'permanently_closed',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $mapped = [
            'external_id' => 'REOPEN-2',
            'place' => [
                'name' => 'Weiter geschlossener Testplatz',
                'latitude' => 51.0,
                'longitude' => 7.0,
                'opening_status' => 'permanently_closed',
            ],
        ];

        DB::table('external_records')->insert([
            'external_source_id' => $sourceId,
            'external_id' => 'REOPEN-2',
            'place_id' => $placeId,
            'status' => 'active',
            'classification' => 'linked',
            'normalized_data' => json_encode($mapped),
            'normalized_hash' => hash('sha256', json_encode($mapped)),
            'first_seen_at' => now(),
            'last_seen_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        self::assertSame(
            0,
            app(ExternalReviewSignalService::class)->queuePossibleReopens($sourceId, $this->importRun($sourceId)),
        );
    }

    private function importRun(int $sourceId): int
    {
        return (int) DB::table('external_import_runs')->insertGetId([
            'external_source_id' => $sourceId,
            'mode' => 'sync',
            'status' => 'running',
            'started_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
