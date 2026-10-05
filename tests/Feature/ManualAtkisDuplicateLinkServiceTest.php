<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Imports\ManualAtkisDuplicateLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ManualAtkisDuplicateLinkServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_and_execute_link_only_one_unambiguous_100_percent_match_within_100_meters(): void
    {
        $sourceId = $this->source();
        $runId = $this->importRun($sourceId);
        $placeId = $this->place('Rastplatz Beispiel Ost', 49.0000, 11.0000);
        $reviewIds = $this->group($sourceId, $runId, 'group-a', [
            ['A-1', 49.0001, 11.0000],
            ['A-2', 49.0002, 11.0000],
        ], [
            ['place_id' => $placeId, 'name' => 'Rastplatz Beispiel Ost', 'distance_m' => 40, 'name_similarity' => 100.0],
            ['place_id' => $this->place('Rastplatz Beispiel West', 49.0010, 11.0000), 'name' => 'Rastplatz Beispiel West', 'distance_m' => 120, 'name_similarity' => 85.0],
        ]);

        $service = app(ManualAtkisDuplicateLinkService::class);
        $preview = $service->preview();

        self::assertSame(1, $preview['groups']);
        self::assertSame(2, $preview['records']);
        self::assertSame($placeId, $preview['matches'][0]['place_id']);

        $actor = User::factory()->create();
        $result = $service->execute($actor);

        self::assertSame(1, $result['linked_groups']);
        self::assertSame(2, $result['linked_records']);
        self::assertSame([], $result['skipped']);

        foreach ($reviewIds as $reviewId) {
            $recordId = DB::table('external_import_review_items')->where('id', $reviewId)->value('external_record_id');
            $this->assertDatabaseHas('external_records', [
                'id' => $recordId,
                'place_id' => $placeId,
                'classification' => 'linked',
            ]);
            $this->assertDatabaseHas('external_import_review_items', [
                'id' => $reviewId,
                'status' => 'resolved',
            ]);
        }
    }

    public function test_preview_skips_match_farther_than_100_meters_and_ambiguous_targets(): void
    {
        $sourceId = $this->source();
        $runId = $this->importRun($sourceId);

        $farPlace = $this->place('Rastplatz Fern', 49.0020, 11.0);
        $this->group($sourceId, $runId, 'group-far', [
            ['F-1', 49.0, 11.0],
            ['F-2', 49.0001, 11.0],
        ], [
            ['place_id' => $farPlace, 'name' => 'Rastplatz Fern', 'distance_m' => 101, 'name_similarity' => 100.0],
        ]);

        $placeA = $this->place('Rastplatz Doppelt', 49.1, 11.1);
        $placeB = $this->place('Rastplatz Doppelt', 49.1002, 11.1);
        $this->group($sourceId, $runId, 'group-ambiguous', [
            ['D-1', 49.1, 11.1],
            ['D-2', 49.1001, 11.1],
        ], [
            ['place_id' => $placeA, 'name' => 'Rastplatz Doppelt', 'distance_m' => 30, 'name_similarity' => 100.0],
            ['place_id' => $placeB, 'name' => 'Rastplatz Doppelt', 'distance_m' => 60, 'name_similarity' => 100.0],
        ]);

        $preview = app(ManualAtkisDuplicateLinkService::class)->preview();

        self::assertSame(0, $preview['groups']);
        self::assertSame(0, $preview['records']);
    }

    private function source(): int
    {
        return (int) DB::table('external_sources')->insertGetId([
            'slug' => ManualAtkisDuplicateLinkService::SOURCE_SLUG,
            'name' => 'Bayern ATKIS',
            'source_type' => 'wfs',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function importRun(int $sourceId): int
    {
        return (int) DB::table('external_import_runs')->insertGetId([
            'external_source_id' => $sourceId,
            'mode' => 'classify',
            'status' => 'completed',
            'started_at' => now(),
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function place(string $name, float $lat, float $lon): int
    {
        $typeId = DB::table('place_types')->where('slug', 'rest-area')->value('id');

        if (! $typeId) {
            $typeId = DB::table('place_types')->insertGetId([
                'slug' => 'rest-area',
                'sort_order' => 10,
                'is_active' => true,
                'is_searchable' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return (int) DB::table('places')->insertGetId([
            'place_type_id' => $typeId,
            'name' => $name,
            'slug' => 'test-'.uniqid(),
            'latitude' => $lat,
            'longitude' => $lon,
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => 'open',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function group(int $sourceId, int $runId, string $groupKey, array $records, array $candidates): array
    {
        $groupName = (string) ($candidates[0]['name'] ?? 'Testgruppe');
        $created = [];

        foreach ($records as [$externalId, $lat, $lon]) {
            $data = [
                'external_id' => $externalId,
                'place' => [
                    'name' => $groupName,
                    'latitude' => $lat,
                    'longitude' => $lon,
                    'suggested_place_type' => 'rest-area',
                ],
            ];

            $recordId = (int) DB::table('external_records')->insertGetId([
                'external_source_id' => $sourceId,
                'external_id' => $externalId,
                'status' => 'active',
                'classification' => 'possible_duplicate',
                'normalized_hash' => hash('sha256', json_encode($data)),
                'normalized_data' => json_encode($data),
                'first_seen_at' => now(),
                'last_seen_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $created[] = [
                'record_id' => $recordId,
                'external_id' => $externalId,
                'latitude' => $lat,
                'longitude' => $lon,
            ];
        }

        $reviewIds = [];

        foreach ($created as $member) {
            $reviewIds[] = (int) DB::table('external_import_review_items')->insertGetId([
                'external_import_run_id' => $runId,
                'external_source_id' => $sourceId,
                'external_record_id' => $member['record_id'],
                'type' => 'external_duplicate_group',
                'severity' => 'warning',
                'status' => 'pending',
                'details' => json_encode([
                    'group_key' => $groupKey,
                    'group_name' => $groupName,
                    'group_place_type' => 'rest-area',
                    'members' => $created,
                    'candidates' => $candidates,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $reviewIds;
    }
}

