<?php

namespace Tests\Feature;

use App\Services\Imports\ExternalDuplicateGroupService;
use App\Services\Imports\ExternalRecordClassificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExternalDuplicateGroupTest extends TestCase
{
    use RefreshDatabase;

    public function test_nearby_identical_records_are_grouped_across_sources_without_auto_merging(): void
    {
        $sourceA = $this->source('source-a', 'Source A');
        $sourceB = $this->source('source-b', 'Source B');
        $runId = $this->createImportRun($sourceA);

        $nearA = $this->record($sourceA, 'A-1', 'Waldcamping Brombach', 'campground', 49.1130, 10.9658);
        $nearB = $this->record($sourceB, 'B-1', 'Waldcamping Brombach', 'campground', 49.1132, 10.9665);
        $far = $this->record($sourceB, 'B-2', 'Waldcamping Brombach', 'campground', 49.1300, 10.9900);
        $differentName = $this->record($sourceB, 'B-3', 'Waldcamping Brombach Ost', 'campground', 49.1131, 10.9660);

        $result = app(ExternalDuplicateGroupService::class)->detectForSource($sourceA, $runId);

        $this->assertSame(1, $result['groups']);
        $this->assertSame(2, $result['grouped_records']);
        $this->assertSame(2, $result['review_items']);

        foreach ([$nearA, $nearB] as $recordId) {
            $this->assertDatabaseHas('external_records', [
                'id' => $recordId,
                'classification' => 'possible_duplicate',
                'place_id' => null,
            ]);

            $this->assertDatabaseHas('external_import_review_items', [
                'external_record_id' => $recordId,
                'type' => 'external_duplicate_group',
                'status' => 'pending',
            ]);
        }

        foreach ([$far, $differentName] as $recordId) {
            $this->assertDatabaseMissing('external_import_review_items', [
                'external_record_id' => $recordId,
                'type' => 'external_duplicate_group',
                'status' => 'pending',
            ]);
        }

        $groupKeys = DB::table('external_import_review_items')
            ->whereIn('external_record_id', [$nearA, $nearB])
            ->where('type', 'external_duplicate_group')
            ->pluck('details')
            ->map(fn ($details) => json_decode((string) $details, true)['group_key'] ?? null)
            ->unique();

        $this->assertCount(1, $groupKeys);
    }


    public function test_nearby_identical_records_from_same_source_are_not_grouped(): void
    {
        $source = $this->source('same-source', 'Same Source');
        $runId = $this->createImportRun($source);

        $first = $this->record($source, 'S-1', 'Campingplatz', 'campground', 51.0000, 7.0000);
        $second = $this->record($source, 'S-2', 'Campingplatz', 'campground', 51.0010, 7.0010);

        $result = app(ExternalDuplicateGroupService::class)->detectForSource($source, $runId);

        $this->assertSame(0, $result['groups']);
        $this->assertSame(0, $result['grouped_records']);
        $this->assertSame(0, $result['review_items']);

        foreach ([$first, $second] as $recordId) {
            $this->assertDatabaseMissing('external_import_review_items', [
                'external_record_id' => $recordId,
                'type' => 'external_duplicate_group',
                'status' => 'pending',
            ]);
        }
    }

    public function test_standard_source_classification_applies_cross_source_grouping_automatically(): void
    {
        $sourceA = $this->source('classification-source-a', 'Classification Source A');
        $sourceB = $this->source('classification-source-b', 'Classification Source B');

        $recordA = $this->record($sourceA, 'CA-1', 'Gemeinsamer Stellplatz', 'motorhome-pitch', 49.5000, 11.0000);
        $recordB = $this->record($sourceB, 'CB-1', 'Gemeinsamer Stellplatz', 'motorhome-pitch', 49.5003, 11.0003);

        $result = app(ExternalRecordClassificationService::class)->classifySource($sourceA);

        $this->assertSame(0, $result['new_candidate']);
        $this->assertSame(1, $result['possible_duplicate']);
        $this->assertSame(1, $result['duplicate_groups']['groups']);

        foreach ([$recordA, $recordB] as $recordId) {
            $this->assertDatabaseHas('external_records', [
                'id' => $recordId,
                'classification' => 'possible_duplicate',
            ]);

            $this->assertDatabaseHas('external_import_review_items', [
                'external_record_id' => $recordId,
                'type' => 'external_duplicate_group',
                'status' => 'pending',
            ]);
        }
    }

    private function source(string $slug, string $name): int
    {
        return (int) DB::table('external_sources')->insertGetId([
            'slug' => $slug,
            'name' => $name,
            'source_type' => 'test',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createImportRun(int $sourceId): int
    {
        return (int) DB::table('external_import_runs')->insertGetId([
            'external_source_id' => $sourceId,
            'mode' => 'classify',
            'status' => 'running',
            'started_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function record(
        int $sourceId,
        string $externalId,
        string $name,
        string $type,
        float $latitude,
        float $longitude,
    ): int {
        $data = [
            'external_id' => $externalId,
            'place' => [
                'name' => $name,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'suggested_place_type' => $type,
            ],
        ];

        return (int) DB::table('external_records')->insertGetId([
            'external_source_id' => $sourceId,
            'external_id' => $externalId,
            'status' => 'active',
            'normalized_hash' => hash('sha256', json_encode($data)),
            'normalized_data' => json_encode($data),
            'first_seen_at' => now(),
            'last_seen_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
