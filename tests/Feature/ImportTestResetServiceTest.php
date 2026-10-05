<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Imports\ImportTestResetService;
use App\Services\PerformanceDataService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportTestResetServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_removes_performance_and_import_test_data_but_preserves_linked_normal_places_and_source(): void
    {
        $this->seed(DatabaseSeeder::class);
        Storage::fake('local');

        $actor = User::factory()->create();
        $perfUser = User::factory()->create([
            'email' => 'perf-user-reset'.PerformanceDataService::USER_EMAIL_DOMAIN,
        ]);

        $placeTypeId = (int) DB::table('place_types')->where('is_active', true)->value('id');

        $normalPlaceId = $this->place($placeTypeId, $actor->id, 'normaler-platz', null);
        $importedPlaceId = $this->place(
            $placeTypeId,
            $actor->id,
            'importierter-platz',
            'Von einem Administrator aus einer validierten externen Quelle übernommen.',
        );
        $performancePlaceId = $this->place(
            $placeTypeId,
            $perfUser->id,
            PerformanceDataService::PLACE_SLUG_PREFIX.'reset',
            PerformanceDataService::MARKER,
        );

        $sourceId = DB::table('external_sources')->insertGetId([
            'slug' => 'reset-test-source',
            'name' => 'Reset Test Source',
            'source_type' => 'file',
            'is_active' => true,
            'last_checked_at' => now(),
            'last_success_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $runId = DB::table('external_import_runs')->insertGetId([
            'external_source_id' => $sourceId,
            'mode' => 'manual_upload',
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $createdRecordId = DB::table('external_records')->insertGetId([
            'external_source_id' => $sourceId,
            'external_id' => 'created-record',
            'place_id' => $importedPlaceId,
            'status' => 'active',
            'classification' => 'created',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $linkedRecordId = DB::table('external_records')->insertGetId([
            'external_source_id' => $sourceId,
            'external_id' => 'linked-record',
            'place_id' => $normalPlaceId,
            'status' => 'active',
            'classification' => 'linked',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('external_record_fields')->insert([
            'external_record_id' => $createdRecordId,
            'field_key' => 'name',
            'value' => json_encode('Importierter Platz'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('external_import_review_items')->insert([
            'external_import_run_id' => $runId,
            'external_source_id' => $sourceId,
            'external_record_id' => $linkedRecordId,
            'place_id' => $normalPlaceId,
            'type' => 'possible_duplicate',
            'severity' => 'warning',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $snapshotPath = 'imports/manual/reset-test.xml';
        Storage::disk('local')->put($snapshotPath, '<xml/>');

        DB::table('external_raw_snapshots')->insert([
            'external_source_id' => $sourceId,
            'external_import_run_id' => $runId,
            'storage_path' => $snapshotPath,
            'sha256' => hash('sha256', '<xml/>'),
            'content_type' => 'application/xml',
            'byte_size' => 6,
            'fetched_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = app(ImportTestResetService::class)->reset();

        $this->assertSame(1, $result['performance_places']);
        $this->assertSame(1, $result['performance_users']);
        $this->assertSame(1, $result['imported_places']);
        $this->assertSame(2, $result['external_records']);

        $this->assertDatabaseMissing('places', ['id' => $importedPlaceId]);
        $this->assertDatabaseMissing('places', ['id' => $performancePlaceId]);
        $this->assertDatabaseHas('places', ['id' => $normalPlaceId]);

        $this->assertDatabaseCount('external_records', 0);
        $this->assertDatabaseCount('external_import_runs', 0);
        $this->assertDatabaseCount('external_import_review_items', 0);
        $this->assertDatabaseCount('external_raw_snapshots', 0);

        $this->assertDatabaseHas('external_sources', [
            'id' => $sourceId,
            'slug' => 'reset-test-source',
        ]);
        $source = DB::table('external_sources')->where('id', $sourceId)->first();
        $this->assertNull($source->last_checked_at);
        $this->assertNull($source->last_success_at);

        Storage::disk('local')->assertMissing($snapshotPath);
    }

    private function place(int $placeTypeId, int $creatorId, string $slug, ?string $internalComment): int
    {
        return (int) DB::table('places')->insertGetId([
            'place_type_id' => $placeTypeId,
            'name' => $slug,
            'slug' => $slug,
            'latitude' => 51.45,
            'longitude' => 7.01,
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => 'unclear',
            'is_active' => true,
            'internal_comment' => $internalComment,
            'created_by' => $creatorId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
