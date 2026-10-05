<?php

namespace Tests\Feature;

use App\Services\Imports\Datex2ParkingMapper;
use App\Services\Imports\Datex2ParkingParser;
use App\Services\Imports\ExternalRecordStagingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExternalRecordStagingTest extends TestCase
{
    use RefreshDatabase;

    public function test_staging_is_repeatable_and_does_not_create_places(): void
    {
        $xml = file_get_contents(base_path('tests/Fixtures/datex2-parking-sample.xml'));
        $parser = app(Datex2ParkingParser::class);
        $mapper = app(Datex2ParkingMapper::class);
        $mapped = array_map(fn (array $record) => $mapper->map($record), $parser->parse($xml));

        $beforePlaces = DB::table('places')->count();

        $first = app(ExternalRecordStagingService::class)->stageDatexParking($mapped, true);
        $second = app(ExternalRecordStagingService::class)->stageDatexParking($mapped, true);

        $this->assertSame(2, $first['new']);
        $this->assertSame(0, $first['changed']);
        $this->assertSame(0, $second['new']);
        $this->assertSame(2, $second['unchanged']);
        $this->assertSame($beforePlaces, DB::table('places')->count());

        $this->assertDatabaseCount('external_records', 2);
        $this->assertDatabaseHas('external_records', [
            'external_id' => 'DE-BW-081165',
            'status' => 'active',
            'place_id' => null,
        ]);
    }

    public function test_staging_normalizes_iso_source_timestamp_for_database_storage(): void
    {
        $service = app(ExternalRecordStagingService::class);

        $service->stageDatexParking([
            [
                'external_id' => 'ISO-DATETIME',
                'source_updated_at' => '2026-09-23T08:48:00+02:00',
                'place' => ['name' => 'ISO timestamp test'],
                'features' => [],
                'vehicle_capacities' => [],
                'access_points' => [],
            ],
        ]);

        $record = DB::table('external_records')->where('external_id', 'ISO-DATETIME')->first();

        $this->assertNotNull($record);
        $this->assertSame('2026-09-23 06:48:00', $record->source_updated_at);
        $this->assertDatabaseHas('external_record_fields', [
            'external_record_id' => $record->id,
            'field_key' => 'name',
            'source_updated_at' => '2026-09-23 06:48:00',
        ]);
    }

    public function test_missing_linked_records_are_queued_for_review_and_resolved_when_they_return(): void
    {
        $service = app(ExternalRecordStagingService::class);

        $service->stageDatexParking([
            [
                'external_id' => 'A',
                'source_updated_at' => null,
                'place' => ['name' => 'A'],
                'features' => [],
                'vehicle_capacities' => [],
                'access_points' => [],
            ],
            [
                'external_id' => 'B',
                'source_updated_at' => null,
                'place' => ['name' => 'B'],
                'features' => [],
                'vehicle_capacities' => [],
                'access_points' => [],
            ],
        ]);

        $sourceId = (int) DB::table('external_sources')
            ->where('slug', 'mobilithek-itp-bab')
            ->value('id');
        $recordId = (int) DB::table('external_records')
            ->where('external_source_id', $sourceId)
            ->where('external_id', 'B')
            ->value('id');
        $placeTypeId = (int) DB::table('place_types')->where('is_active', true)->value('id');

        $placeId = (int) DB::table('places')->insertGetId([
            'place_type_id' => $placeTypeId,
            'name' => 'Verknüpfter Platz B',
            'slug' => 'verknuepfter-platz-b',
            'latitude' => 51.0,
            'longitude' => 7.0,
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => 'open',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('external_records')->where('id', $recordId)->update([
            'place_id' => $placeId,
        ]);

        $runId = (int) DB::table('external_import_runs')->insertGetId([
            'external_source_id' => $sourceId,
            'mode' => 'sync',
            'status' => 'running',
            'started_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        self::assertSame(1, $service->completeSnapshot($sourceId, ['A'], $runId));

        $this->assertDatabaseHas('external_import_review_items', [
            'external_import_run_id' => $runId,
            'external_source_id' => $sourceId,
            'external_record_id' => $recordId,
            'place_id' => $placeId,
            'type' => 'source_missing',
            'status' => 'pending',
        ]);

        $service->stageDatexParking([
            [
                'external_id' => 'B',
                'source_updated_at' => null,
                'place' => ['name' => 'B'],
                'features' => [],
                'vehicle_capacities' => [],
                'access_points' => [],
            ],
        ]);

        $this->assertDatabaseHas('external_records', [
            'id' => $recordId,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('external_import_review_items', [
            'external_record_id' => $recordId,
            'type' => 'source_missing',
            'status' => 'superseded',
        ]);
    }

    public function test_complete_snapshot_marks_only_external_record_missing(): void
    {
        $service = app(ExternalRecordStagingService::class);

        $service->stageDatexParking([
            [
                'external_id' => 'A',
                'source_updated_at' => null,
                'place' => ['name' => 'A'],
                'features' => [],
                'vehicle_capacities' => [],
                'access_points' => [],
            ],
            [
                'external_id' => 'B',
                'source_updated_at' => null,
                'place' => ['name' => 'B'],
                'features' => [],
                'vehicle_capacities' => [],
                'access_points' => [],
            ],
        ], true);

        $result = $service->stageDatexParking([
            [
                'external_id' => 'A',
                'source_updated_at' => null,
                'place' => ['name' => 'A'],
                'features' => [],
                'vehicle_capacities' => [],
                'access_points' => [],
            ],
        ], true);

        $this->assertSame(1, $result['missing_marked']);
        $this->assertDatabaseHas('external_records', ['external_id' => 'B', 'status' => 'missing']);
    }
}
