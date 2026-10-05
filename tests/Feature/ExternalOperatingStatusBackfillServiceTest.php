<?php

namespace Tests\Feature;

use App\Services\Imports\ExternalOperatingStatusBackfillService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExternalOperatingStatusBackfillServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_fills_only_unknown_operating_statuses_and_records_history(): void
    {
        $sourceId = (int) DB::table('external_sources')->insertGetId([
            'slug' => 'status-backfill-test',
            'name' => 'Status Backfill Test',
            'source_type' => 'test',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $unknownPlaceId = $this->place('Unbekannter Status', 'unclear');
        $maintainedPlaceId = $this->place('Bereits gepflegt', 'temporarily_closed');

        $unknownRecordId = $this->record($sourceId, $unknownPlaceId, 'EXT-OPEN');
        $maintainedRecordId = $this->record($sourceId, $maintainedPlaceId, 'EXT-CLOSED');

        $updated = app(ExternalOperatingStatusBackfillService::class)->fillUnknownForSource(
            $sourceId,
            [
                'EXT-OPEN' => ['place' => ['opening_status' => 'open']],
                'EXT-CLOSED' => ['place' => ['opening_status' => 'permanently_closed']],
            ],
        );

        self::assertSame(1, $updated);

        $this->assertDatabaseHas('places', [
            'id' => $unknownPlaceId,
            'opening_status' => 'open',
        ]);
        $this->assertDatabaseHas('places', [
            'id' => $maintainedPlaceId,
            'opening_status' => 'temporarily_closed',
        ]);
        $this->assertDatabaseHas('external_records', [
            'id' => $unknownRecordId,
            'place_id' => $unknownPlaceId,
        ]);
        $this->assertDatabaseHas('external_records', [
            'id' => $maintainedRecordId,
            'place_id' => $maintainedPlaceId,
        ]);
        $this->assertDatabaseHas('place_history', [
            'place_id' => $unknownPlaceId,
            'external_source_id' => $sourceId,
            'actor_type' => 'external_source',
            'action' => 'external_operating_status_filled',
        ]);
        $this->assertDatabaseMissing('place_history', [
            'place_id' => $maintainedPlaceId,
            'action' => 'external_operating_status_filled',
        ]);
    }

    public function test_closed_status_wins_when_multiple_source_records_are_linked_to_one_unknown_place(): void
    {
        $sourceId = (int) DB::table('external_sources')->insertGetId([
            'slug' => 'status-priority-test',
            'name' => 'Status Priority Test',
            'source_type' => 'test',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $placeId = $this->place('Mehrere Quellflächen', 'unclear');
        $this->record($sourceId, $placeId, 'EXT-A');
        $this->record($sourceId, $placeId, 'EXT-B');

        $updated = app(ExternalOperatingStatusBackfillService::class)->fillUnknownForSource(
            $sourceId,
            [
                'EXT-A' => ['place' => ['opening_status' => 'open']],
                'EXT-B' => ['place' => ['opening_status' => 'permanently_closed']],
            ],
        );

        self::assertSame(1, $updated);
        $this->assertDatabaseHas('places', [
            'id' => $placeId,
            'opening_status' => 'permanently_closed',
        ]);
    }

    private function place(string $name, string $openingStatus): int
    {
        $placeTypeId = DB::table('place_types')->where('is_active', true)->value('id');

        return (int) DB::table('places')->insertGetId([
            'place_type_id' => $placeTypeId,
            'name' => $name,
            'slug' => 'status-backfill-'.uniqid(),
            'latitude' => 51.0,
            'longitude' => 7.0,
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => $openingStatus,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function record(int $sourceId, int $placeId, string $externalId): int
    {
        return (int) DB::table('external_records')->insertGetId([
            'external_source_id' => $sourceId,
            'external_id' => $externalId,
            'place_id' => $placeId,
            'status' => 'active',
            'classification' => 'created',
            'normalized_data' => json_encode(['external_id' => $externalId]),
            'normalized_hash' => hash('sha256', $externalId),
            'first_seen_at' => now(),
            'last_seen_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
