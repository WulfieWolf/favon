<?php

namespace Tests\Feature;

use App\Services\ExternalImportGuard;
use App\Services\ExternalImportRunService;
use App\Services\PlaceHistoryPresenter;
use App\Services\PlaceHistoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExternalImportFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guard_stops_obviously_broken_source_data(): void
    {
        $guard = app(ExternalImportGuard::class);

        $report = $guard->validate(
            [
                ['id' => null, 'latitude' => 'not-a-number'],
                ['id' => null, 'latitude' => 'also-text'],
            ],
            [
                'required_fields' => ['id'],
                'field_types' => ['latitude' => 'number'],
                'min_records' => 100,
            ],
            5000,
        );

        $this->assertFalse($report['valid']);
        $this->assertContains('record_count_below_minimum', array_column($report['errors'], 'code'));
        $this->assertContains('record_count_drop_too_large', array_column($report['errors'], 'code'));
        $this->assertContains('required_field_missing_too_often', array_column($report['errors'], 'code'));
        $this->assertContains('field_type_mismatch', array_column($report['errors'], 'code'));
    }

    public function test_validated_run_and_external_place_history_are_kept_separate_from_audit_log(): void
    {
        $sourceId = DB::table('external_sources')->insertGetId([
            'slug' => 'test-source',
            'name' => 'Test Source',
            'provider' => 'Official Test Provider',
            'source_type' => 'api',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $placeTypeId = DB::table('place_types')->insertGetId([
            'slug' => 'test-place-type',
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $placeId = DB::table('places')->insertGetId([
            'place_type_id' => $placeTypeId,
            'name' => 'Imported Test Place',
            'slug' => 'imported-test-place',
            'latitude' => 51.45,
            'longitude' => 7.01,
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => 'unclear',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $runs = app(ExternalImportRunService::class);
        $runId = $runs->start($sourceId);
        $runs->markFetched($runId, 1, str_repeat('a', 64));
        $runs->markValidated($runId, ['valid' => true, 'record_count' => 1, 'errors' => [], 'warnings' => []]);
        $runs->complete($runId, ['mapped' => 1, 'created' => 1]);

        app(PlaceHistoryService::class)->addExternalSource(
            $placeId,
            $sourceId,
            'external_place_created',
            'Platz aufgrund der Daten aus „Test Source“ angelegt.',
            ['import_run_id' => $runId],
        );

        $this->assertDatabaseHas('external_import_runs', [
            'id' => $runId,
            'status' => 'completed',
            'created_count' => 1,
        ]);

        $this->assertDatabaseHas('place_history', [
            'place_id' => $placeId,
            'external_source_id' => $sourceId,
            'actor_type' => 'external_source',
            'action' => 'external_place_created',
        ]);

        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_place_history_presents_author_and_source_separately(): void
    {
        $sourceId = DB::table('external_sources')->insertGetId([
            'slug' => 'research-test-source',
            'name' => 'Google Maps',
            'source_type' => 'manual_research',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $placeTypeId = DB::table('place_types')->insertGetId([
            'slug' => 'research-test-type',
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $placeId = DB::table('places')->insertGetId([
            'place_type_id' => $placeTypeId,
            'name' => 'Research Test Place',
            'slug' => 'research-test-place',
            'latitude' => 51.45,
            'longitude' => 7.01,
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => 'unclear',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(PlaceHistoryService::class)->addExternalSource(
            $placeId,
            $sourceId,
            'research_update',
            'Telefonnummer ergänzt.',
            [
                'author' => 'KI-gestützte Ergänzung',
                'source_label' => 'Betreiberwebsite',
                'source_url' => 'https://example.org/kontakt',
                'researched_at' => '2026-09-30 16:00:00',
            ],
        );

        $history = app(PlaceHistoryPresenter::class)->forPlace($placeId)->first();

        $this->assertSame('KI-gestützte Ergänzung', $history->actor);
        $this->assertSame('Betreiberwebsite', $history->source_label);
        $this->assertSame('https://example.org/kontakt', $history->source_url);
        $this->assertSame('2026-09-30 16:00:00', $history->submitted_at);
    }

    public function test_external_records_can_be_marked_missing_without_deleting_the_place(): void
    {
        $sourceId = DB::table('external_sources')->insertGetId([
            'slug' => 'missing-test-source',
            'name' => 'Missing Test Source',
            'source_type' => 'feed',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $placeTypeId = DB::table('place_types')->insertGetId([
            'slug' => 'missing-test-type',
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $placeId = DB::table('places')->insertGetId([
            'place_type_id' => $placeTypeId,
            'name' => 'Still Existing Place',
            'slug' => 'still-existing-place',
            'latitude' => 50.0,
            'longitude' => 8.0,
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => 'unclear',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('external_records')->insert([
            'external_source_id' => $sourceId,
            'external_id' => 'ABC-123',
            'place_id' => $placeId,
            'status' => 'missing',
            'first_seen_at' => now()->subMonth(),
            'last_seen_at' => now()->subMonth(),
            'missing_since' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('places', ['id' => $placeId, 'is_active' => true]);
        $this->assertDatabaseHas('external_records', [
            'external_source_id' => $sourceId,
            'external_id' => 'ABC-123',
            'status' => 'missing',
        ]);
    }
}
