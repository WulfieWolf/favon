<?php

namespace Tests\Feature;

use App\Services\Imports\ExternalRecordClassificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExternalRecordClassificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_records_are_classified_as_new_duplicate_or_review(): void
    {
        $sourceId = $this->source();

        $placeTypeId = DB::table('place_types')->where('slug', 'rest-area')->value('id')
            ?: DB::table('place_types')->value('id');

        DB::table('places')->insert([
            'place_type_id' => $placeTypeId,
            'name' => 'Known Rest Area',
            'slug' => 'known-rest-area-classification-test',
            'latitude' => 51.0000,
            'longitude' => 7.0000,
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => 'unclear',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->externalRecord($sourceId, 'NEW', [
            'external_id' => 'NEW',
            'place' => ['name' => 'Far Away', 'latitude' => 48.0, 'longitude' => 9.0],
        ]);

        $this->externalRecord($sourceId, 'DUP', [
            'external_id' => 'DUP',
            'place' => ['name' => 'Known Rest Area', 'latitude' => 51.0002, 'longitude' => 7.0002],
        ]);

        $this->externalRecord($sourceId, 'NOCOORD', [
            'external_id' => 'NOCOORD',
            'place' => ['name' => 'No Coordinates', 'latitude' => null, 'longitude' => null],
        ]);

        $result = app(ExternalRecordClassificationService::class)->classifySource($sourceId);

        $this->assertSame(3, $result['records']);
        $this->assertSame(1, $result['new_candidate']);
        $this->assertSame(1, $result['possible_duplicate']);
        $this->assertSame(1, $result['needs_review']);
        $this->assertSame(2, $result['review_items']);

        $this->assertDatabaseHas('external_records', ['external_id' => 'NEW', 'classification' => 'new_candidate']);
        $this->assertDatabaseHas('external_records', ['external_id' => 'DUP', 'classification' => 'possible_duplicate']);
        $this->assertDatabaseHas('external_records', ['external_id' => 'NOCOORD', 'classification' => 'needs_review']);

        $this->assertDatabaseHas('external_import_review_items', [
            'external_source_id' => $sourceId,
            'type' => 'possible_duplicate',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('external_import_review_items', [
            'external_source_id' => $sourceId,
            'type' => 'missing_coordinates',
            'status' => 'pending',
        ]);
    }

    public function test_ignored_records_remain_ignored_during_classification(): void
    {
        $sourceId = $this->source();

        $this->externalRecord($sourceId, 'IGNORED', [
            'external_id' => 'IGNORED',
            'place' => ['name' => 'Clearly irrelevant', 'latitude' => 51.0, 'longitude' => 7.0],
        ]);

        DB::table('external_records')
            ->where('external_source_id', $sourceId)
            ->where('external_id', 'IGNORED')
            ->update([
                'classification' => 'ignored',
                'classified_at' => now(),
            ]);

        $result = app(ExternalRecordClassificationService::class)->classifySource($sourceId);

        $this->assertSame(0, $result['records']);
        $this->assertDatabaseHas('external_records', [
            'external_source_id' => $sourceId,
            'external_id' => 'IGNORED',
            'classification' => 'ignored',
        ]);
    }

    public function test_reclassification_does_not_accumulate_pending_review_items(): void
    {
        $sourceId = $this->source();

        $this->externalRecord($sourceId, 'NOCOORD', [
            'external_id' => 'NOCOORD',
            'place' => ['name' => 'No Coordinates', 'latitude' => null, 'longitude' => null],
        ]);

        $service = app(ExternalRecordClassificationService::class);
        $service->classifySource($sourceId);
        $service->classifySource($sourceId);

        $recordId = DB::table('external_records')->where('external_id', 'NOCOORD')->value('id');

        $this->assertSame(
            1,
            DB::table('external_import_review_items')
                ->where('external_record_id', $recordId)
                ->where('status', 'pending')
                ->count(),
        );

        $this->assertSame(
            1,
            DB::table('external_import_review_items')
                ->where('external_record_id', $recordId)
                ->where('status', 'superseded')
                ->count(),
        );
    }

    private function source(): int
    {
        return (int) DB::table('external_sources')->insertGetId([
            'slug' => 'classification-test',
            'name' => 'Classification Test',
            'source_type' => 'test',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function externalRecord(int $sourceId, string $externalId, array $data): void
    {
        DB::table('external_records')->insert([
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
