<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Imports\ManualCsvPlaceParser;
use App\Services\Imports\ExternalDuplicateGroupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminImportCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_open_import_center_and_download_csv_template(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        config(['camperwolf.owner_email' => $owner->email]);

        $this->actingAs($owner)
            ->withSession(['locale' => 'de'])
            ->get(route('admin.imports.index'))
            ->assertOk()
            ->assertSee('Import-Zentrale')
            ->assertSee('Review-Queue');

        $this->actingAs($owner)
            ->get(route('admin.imports.template'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_owner_can_export_research_csv_with_current_place_data_and_dynamic_vehicle_columns(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        config(['camperwolf.owner_email' => $owner->email]);

        $placeTypeId = (int) DB::table('place_types')->insertGetId([
            'slug' => 'research-campground',
            'is_active' => true,
            'is_searchable' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $vehicleTypeId = (int) DB::table('vehicle_types')->insertGetId([
            'slug' => 'research-van',
            'is_active' => true,
            'is_searchable' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $placeId = (int) DB::table('places')->insertGetId([
            'place_type_id' => $placeTypeId,
            'name' => 'Recherche Platz',
            'slug' => 'recherche-platz',
            'latitude' => 51.45,
            'longitude' => 7.01,
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => 'open',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('place_addresses')->insert([
            'place_id' => $placeId,
            'country_code' => 'DE',
            'postal_code' => '45127',
            'city' => 'Essen',
            'street' => 'Musterstraße',
            'house_number' => '1',
            'address_addition' => null,
            'is_active' => true,
            'version_valid_from' => now(),
            'version_valid_until' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('place_details')->insert([
            'place_id' => $placeId,
            'operator_name' => 'Test Betreiber',
            'pitch_count' => 12,
            'pitch_count_source' => 'manual',
            'is_active' => true,
            'version_valid_from' => now(),
            'version_valid_until' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('place_contacts')->insert([
            'place_id' => $placeId,
            'contact_type' => 'website',
            'value' => 'https://example.org',
            'sort_order' => 10,
            'is_active' => true,
            'version_valid_from' => now(),
            'version_valid_until' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('place_vehicle_types')->insert([
            'place_id' => $placeId,
            'vehicle_type_id' => $vehicleTypeId,
            'capacity' => 7,
            'is_active' => true,
            'version_valid_from' => now(),
            'version_valid_until' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($owner)
            ->get(route('admin.imports.research-export', ['scope' => 'all']))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->getContent();

        $this->assertStringContainsString('place_id;missing_fields;author;source_label;source_url', $content);
        $this->assertStringContainsString('current_suitable_research-van;suitable_research-van', $content);
        $this->assertStringContainsString('current_name', $content);
        $this->assertStringContainsString('Recherche Platz', $content);
        $this->assertStringContainsString('51,45', $content);
        $this->assertStringContainsString('7,01', $content);
        $this->assertStringContainsString('Test Betreiber', $content);
        $this->assertStringContainsString('https://example.org', $content);
        $this->assertStringContainsString(';7;', $content);
    }

    public function test_research_export_default_scope_only_contains_places_with_missing_research_fields(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        config(['camperwolf.owner_email' => $owner->email]);

        $placeTypeId = (int) DB::table('place_types')->insertGetId([
            'slug' => 'research-filter-type',
            'is_active' => true,
            'is_searchable' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('places')->insert([
            'place_type_id' => $placeTypeId,
            'name' => 'Unvollständiger Rechercheplatz',
            'slug' => 'unvollstaendiger-rechercheplatz',
            'latitude' => 51.0,
            'longitude' => 7.0,
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => 'unclear',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($owner)
            ->get(route('admin.imports.research-export'))
            ->assertOk();

        $content = $response->getContent();

        $this->assertStringContainsString('Unvollständiger Rechercheplatz', $content);
        $this->assertStringContainsString('country_code|postal_code|city|street|operator|parking_spaces|website|phone|email|suitable_for', $content);
    }

    public function test_csv_upload_is_staged_classified_and_logged_without_creating_places(): void
    {
        Storage::fake('local');

        $owner = User::factory()->create(['email_verified_at' => now()]);
        config(['camperwolf.owner_email' => $owner->email]);

        $csv = app(ManualCsvPlaceParser::class)->template();
        $upload = UploadedFile::fake()->createWithContent('places.csv', $csv);
        $beforePlaces = DB::table('places')->count();

        $this->actingAs($owner)
            ->post(route('admin.imports.upload'), [
                'adapter' => 'csv',
                'source_name' => 'Test Kommune',
                'file' => $upload,
                'complete_snapshot' => '1',
            ])
            ->assertRedirect(route('admin.imports.index'))
            ->assertSessionHasNoErrors();

        $source = DB::table('external_sources')->where('slug', 'manual-csv-test-kommune')->first();

        $this->assertNotNull($source);
        $this->assertDatabaseHas('external_records', [
            'external_source_id' => $source->id,
            'external_id' => 'example-001',
            'status' => 'active',
            'classification' => 'new_candidate',
        ]);
        $this->assertDatabaseHas('external_import_runs', [
            'external_source_id' => $source->id,
            'mode' => 'manual_upload',
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('external_import_runs', [
            'external_source_id' => $source->id,
            'mode' => 'classify',
            'status' => 'completed',
        ]);
        $this->assertDatabaseCount('external_raw_snapshots', 1);
        $this->assertSame($beforePlaces, DB::table('places')->count());
    }

    public function test_import_center_lists_new_candidates_with_source_data(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        config(['camperwolf.owner_email' => $owner->email]);

        $sourceId = (int) DB::table('external_sources')->insertGetId([
            'slug' => 'candidate-ui-source',
            'name' => 'Candidate UI Source',
            'source_type' => 'test',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $normalized = [
            'external_id' => 'UI-CAND-1',
            'place' => [
                'suggested_place_type' => 'rest-area',
                'name' => 'UI Candidate Rest Area',
                'latitude' => 51.5,
                'longitude' => 7.1,
                'address' => [
                    'street' => 'Teststraße',
                    'house_number' => '5',
                    'postal_code' => '45127',
                    'city' => 'Essen',
                ],
                'operator' => ['name' => 'UI Betreiber'],
                'parking_spaces_total' => 42,
            ],
            'features' => [
                'toilet' => ['status' => 'available'],
                'shower' => ['status' => 'unknown'],
            ],
        ];

        DB::table('external_records')->insert([
            'external_source_id' => $sourceId,
            'external_id' => 'UI-CAND-1',
            'status' => 'active',
            'classification' => 'new_candidate',
            'normalized_data' => json_encode($normalized),
            'normalized_hash' => hash('sha256', json_encode($normalized)),
            'first_seen_at' => now(),
            'last_seen_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($owner)
            ->withSession(['locale' => 'de'])
            ->get(route('admin.imports.index'))
            ->assertOk()
            ->assertSee('Praxistest-Kandidaten')
            ->assertSee('Neue Kandidaten')
            ->assertSee('UI Candidate Rest Area')
            ->assertSee('UI Betreiber')
            ->assertSee('42 Stellplätze')
            ->assertSee('Toilette')
            ->assertSee('Alle auf dieser Seite')
            ->assertSee('Ausgewählte übernehmen')
            ->assertSee(route('admin.imports.candidates.bulk-create'), false);
    }

    public function test_bulk_candidate_endpoint_creates_only_submitted_new_candidates(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        config(['camperwolf.owner_email' => $owner->email]);

        $sourceId = (int) DB::table('external_sources')->insertGetId([
            'slug' => 'bulk-ui-source',
            'name' => 'Bulk UI Source',
            'source_type' => 'test',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $makeRecord = function (string $externalId, string $name) use ($sourceId): int {
            $normalized = [
                'external_id' => $externalId,
                'place' => [
                    'suggested_place_type' => 'rest-area',
                    'name' => $name,
                    'latitude' => 51.5,
                    'longitude' => 7.1,
                    'address' => [],
                    'operator' => [],
                    'parking_spaces_total' => null,
                ],
                'features' => [
                    'toilet' => ['status' => 'available', 'conflict' => false],
                ],
            ];

            return (int) DB::table('external_records')->insertGetId([
                'external_source_id' => $sourceId,
                'external_id' => $externalId,
                'status' => 'active',
                'classification' => 'new_candidate',
                'normalized_data' => json_encode($normalized),
                'normalized_hash' => hash('sha256', json_encode($normalized)),
                'first_seen_at' => now(),
                'last_seen_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        };

        $first = $makeRecord('BULK-UI-1', 'Bulk UI One');
        $second = $makeRecord('BULK-UI-2', 'Bulk UI Two');
        $unselected = $makeRecord('BULK-UI-3', 'Bulk UI Three');

        $this->actingAs($owner)
            ->post(route('admin.imports.candidates.bulk-create'), [
                'candidate_ids' => [$first, $second],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('external_records', [
            'id' => $first,
            'classification' => 'created',
        ]);
        $this->assertDatabaseHas('external_records', [
            'id' => $second,
            'classification' => 'created',
        ]);
        $this->assertDatabaseHas('external_records', [
            'id' => $unselected,
            'classification' => 'new_candidate',
            'place_id' => null,
        ]);
    }

    public function test_duplicate_group_ui_and_lazy_map_endpoint_work_across_sources(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        config(['camperwolf.owner_email' => $owner->email]);

        $sourceA = (int) DB::table('external_sources')->insertGetId([
            'slug' => 'group-ui-a',
            'name' => 'Group UI A',
            'source_type' => 'test',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $sourceB = (int) DB::table('external_sources')->insertGetId([
            'slug' => 'group-ui-b',
            'name' => 'Group UI B',
            'source_type' => 'test',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $runId = (int) DB::table('external_import_runs')->insertGetId([
            'external_source_id' => $sourceA,
            'mode' => 'classify',
            'status' => 'completed',
            'started_at' => now(),
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $recordIds = [];
        foreach ([
            [$sourceA, 'GROUP-A', 49.1000, 11.1000],
            [$sourceB, 'GROUP-B', 49.1002, 11.1002],
        ] as [$sourceId, $externalId, $lat, $lon]) {
            $normalized = [
                'external_id' => $externalId,
                'place' => [
                    'suggested_place_type' => 'campground',
                    'name' => 'Gemeinsamer Campingplatz',
                    'latitude' => $lat,
                    'longitude' => $lon,
                ],
            ];

            $recordIds[] = (int) DB::table('external_records')->insertGetId([
                'external_source_id' => $sourceId,
                'external_id' => $externalId,
                'status' => 'active',
                'normalized_data' => json_encode($normalized),
                'normalized_hash' => hash('sha256', json_encode($normalized)),
                'first_seen_at' => now(),
                'last_seen_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        app(ExternalDuplicateGroupService::class)->detectForSource($sourceA, $runId);

        $reviewId = (int) DB::table('external_import_review_items')
            ->where('external_record_id', $recordIds[0])
            ->where('type', 'external_duplicate_group')
            ->value('id');

        $this->actingAs($owner)
            ->withSession(['locale' => 'de'])
            ->get(route('admin.imports.index'))
            ->assertOk()
            ->assertSee('Import-Dubletten-Gruppen')
            ->assertSee('Gemeinsamer Campingplatz')
            ->assertSee('Group UI A')
            ->assertSee('Group UI B')
            ->assertSee('Auf Karte prüfen')
            ->assertSee('Als eigenen Platz anlegen');

        $this->actingAs($owner)
            ->getJson(route('admin.imports.duplicate-groups.map', $reviewId))
            ->assertOk()
            ->assertJsonPath('name', 'Gemeinsamer Campingplatz')
            ->assertJsonCount(2, 'members')
            ->assertJsonCount(2, 'review_ids')
            ->assertJsonPath('create_place_url', route('admin.imports.duplicate-groups.create-place'))
            ->assertJsonStructure([
                'members' => [
                    '*' => ['review_id', 'record_id', 'external_id', 'source_name', 'latitude', 'longitude'],
                ],
            ]);
    }


    public function test_import_center_replaces_stale_duplicate_candidate_with_current_merge_target(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        config(['camperwolf.owner_email' => $owner->email]);

        $placeTypeId = (int) DB::table('place_types')->insertGetId([
            'slug' => 'stale-review-type',
            'is_active' => true,
            'is_searchable' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $stalePlaceId = (int) DB::table('places')->insertGetId([
            'place_type_id' => $placeTypeId,
            'name' => 'Alter Dublettenplatz',
            'slug' => 'alter-dublettenplatz',
            'latitude' => 51.0,
            'longitude' => 7.0,
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => 'unclear',
            'is_active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $activePlaceId = (int) DB::table('places')->insertGetId([
            'place_type_id' => $placeTypeId,
            'name' => 'Aktueller Hauptplatz',
            'slug' => 'aktueller-hauptplatz',
            'latitude' => 51.0,
            'longitude' => 7.0,
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => 'unclear',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('place_merges')->insert([
            'source_place_id' => $stalePlaceId,
            'target_place_id' => $activePlaceId,
            'merged_by' => $owner->id,
            'status' => 'completed',
            'decisions' => json_encode([]),
            'snapshot' => json_encode([]),
            'merged_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sourceId = (int) DB::table('external_sources')->insertGetId([
            'slug' => 'stale-review-source',
            'name' => 'Stale Review Source',
            'source_type' => 'test',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $runId = (int) DB::table('external_import_runs')->insertGetId([
            'external_source_id' => $sourceId,
            'mode' => 'classify',
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $normalized = [
            'external_id' => 'STALE-REVIEW-1',
            'place' => [
                'name' => 'Externer Kandidat',
                'latitude' => 51.0001,
                'longitude' => 7.0001,
                'suggested_place_type' => 'stale-review-type',
            ],
        ];

        $recordId = (int) DB::table('external_records')->insertGetId([
            'external_source_id' => $sourceId,
            'external_id' => 'STALE-REVIEW-1',
            'status' => 'active',
            'classification' => 'possible_duplicate',
            'normalized_data' => json_encode($normalized),
            'normalized_hash' => hash('sha256', json_encode($normalized)),
            'first_seen_at' => now(),
            'last_seen_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('external_import_review_items')->insert([
            'external_import_run_id' => $runId,
            'external_source_id' => $sourceId,
            'external_record_id' => $recordId,
            'type' => 'possible_duplicate',
            'severity' => 'warning',
            'status' => 'pending',
            'details' => json_encode([
                'external_name' => 'Externer Kandidat',
                'candidates' => [[
                    'place_id' => $stalePlaceId,
                    'name' => 'Alter Dublettenplatz',
                    'distance_m' => 12,
                    'name_similarity' => 88.0,
                ]],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($owner)
            ->withSession(['locale' => 'de'])
            ->get(route('admin.imports.index', ['review_status' => 'pending']))
            ->assertOk()
            ->assertSee('Aktueller Hauptplatz')
            ->assertSee('#'.$activePlaceId)
            ->assertDontSee('Alter Dublettenplatz');

        $response->assertSee('value="'.$activePlaceId.'"', false);
    }

    public function test_single_review_action_returns_to_review_queue_anchor(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        config(['camperwolf.owner_email' => $owner->email]);

        $sourceId = (int) DB::table('external_sources')->insertGetId([
            'slug' => 'anchor-review-source',
            'name' => 'Anchor Review Source',
            'source_type' => 'test',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $runId = (int) DB::table('external_import_runs')->insertGetId([
            'external_source_id' => $sourceId,
            'mode' => 'classify',
            'status' => 'completed',
            'started_at' => now(),
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $normalized = [
            'external_id' => 'ANCHOR-1',
            'place' => [
                'name' => 'Anchor Review',
                'latitude' => 51.0,
                'longitude' => 7.0,
                'suggested_place_type' => 'parking',
            ],
        ];

        $recordId = (int) DB::table('external_records')->insertGetId([
            'external_source_id' => $sourceId,
            'external_id' => 'ANCHOR-1',
            'status' => 'active',
            'classification' => 'possible_duplicate',
            'normalized_hash' => hash('sha256', json_encode($normalized)),
            'normalized_data' => json_encode($normalized),
            'first_seen_at' => now(),
            'last_seen_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $reviewId = (int) DB::table('external_import_review_items')->insertGetId([
            'external_import_run_id' => $runId,
            'external_source_id' => $sourceId,
            'external_record_id' => $recordId,
            'type' => 'possible_duplicate',
            'severity' => 'warning',
            'status' => 'pending',
            'details' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $url = route('admin.imports.index', ['review_status' => 'pending']);

        $this->actingAs($owner)
            ->from($url)
            ->post(route('admin.imports.reviews.defer', $reviewId), ['note' => 'später'])
            ->assertRedirect($url.'#review-queue');
    }

    public function test_manual_atkis_bulk_action_returns_to_duplicate_groups_anchor(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        config(['camperwolf.owner_email' => $owner->email]);

        $url = route('admin.imports.index');

        $this->actingAs($owner)
            ->from($url)
            ->post(route('admin.imports.duplicate-groups.link-eligible-atkis'), ['confirmed' => '1'])
            ->assertRedirect($url.'#duplicate-groups');
    }


    public function test_csv_upload_requires_a_source_name(): void
    {
        Storage::fake('local');

        $owner = User::factory()->create(['email_verified_at' => now()]);
        config(['camperwolf.owner_email' => $owner->email]);

        $upload = UploadedFile::fake()->createWithContent(
            'places.csv',
            app(ManualCsvPlaceParser::class)->template(),
        );

        $this->actingAs($owner)
            ->post(route('admin.imports.upload'), [
                'adapter' => 'csv',
                'source_name' => '',
                'file' => $upload,
            ])
            ->assertSessionHasErrors('source_name');

        $this->assertDatabaseCount('external_records', 0);
    }
}
