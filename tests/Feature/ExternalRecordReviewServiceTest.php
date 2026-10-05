<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Imports\ExternalRecordReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExternalRecordReviewServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_can_link_external_record_to_existing_place_without_creating_a_new_place(): void
    {
        [$actor, $sourceId, $recordId, $reviewId] = $this->reviewFixture();
        $placeId = $this->place('Existing Camperwolf Place', 51.0, 7.0);
        $before = DB::table('places')->count();

        $result = app(ExternalRecordReviewService::class)->link($reviewId, $placeId, $actor);

        $this->assertSame($placeId, $result);
        $this->assertSame($before, DB::table('places')->count());
        $this->assertDatabaseHas('external_records', [
            'id' => $recordId,
            'place_id' => $placeId,
            'classification' => 'linked',
        ]);
        $this->assertDatabaseHas('external_import_review_items', [
            'id' => $reviewId,
            'status' => 'resolved',
            'resolved_by' => $actor->id,
        ]);
        $this->assertDatabaseHas('place_history', [
            'place_id' => $placeId,
            'external_source_id' => $sourceId,
            'action' => 'external_source_linked',
        ]);
    }

    public function test_review_link_follows_completed_merge_to_current_active_place(): void
    {
        [$actor, , $recordId, $reviewId] = $this->reviewFixture();

        $stalePlaceId = $this->place('Old Duplicate Place', 51.0, 7.0);
        $activePlaceId = $this->place('Current Main Place', 51.0, 7.0);

        DB::table('places')->where('id', $stalePlaceId)->update([
            'is_active' => false,
            'updated_at' => now(),
        ]);

        DB::table('place_merges')->insert([
            'source_place_id' => $stalePlaceId,
            'target_place_id' => $activePlaceId,
            'merged_by' => $actor->id,
            'status' => 'completed',
            'decisions' => json_encode([]),
            'snapshot' => json_encode([]),
            'merged_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('external_import_review_items')->where('id', $reviewId)->update([
            'details' => json_encode([
                'external_name' => 'External Place',
                'candidates' => [[
                    'place_id' => $stalePlaceId,
                    'name' => 'Old Duplicate Place',
                    'distance_m' => 10,
                    'name_similarity' => 90.0,
                ]],
            ]),
            'updated_at' => now(),
        ]);

        $result = app(ExternalRecordReviewService::class)->link($reviewId, $stalePlaceId, $actor);

        $this->assertSame($activePlaceId, $result);
        $this->assertDatabaseHas('external_records', [
            'id' => $recordId,
            'place_id' => $activePlaceId,
            'classification' => 'linked',
        ]);
        $this->assertDatabaseHas('external_import_review_items', [
            'id' => $reviewId,
            'status' => 'resolved',
        ]);
    }

    public function test_possible_duplicate_candidate_can_become_new_primary_place_and_merge_existing_place(): void
    {
        [$actor, , $recordId, $reviewId] = $this->reviewFixture([
            'external_id' => 'EXT-PROMOTE',
            'place' => [
                'suggested_place_type' => 'rest-area',
                'name' => 'Better External Place',
                'latitude' => 51.1001,
                'longitude' => 7.1001,
                'address' => [],
                'operator' => [],
                'parking_spaces_total' => null,
            ],
            'features' => [],
        ]);

        $existingPlaceId = $this->place('Older Existing Place', 51.1, 7.1);

        DB::table('place_addresses')->insert([
            'place_id' => $existingPlaceId,
            'country_code' => 'DE',
            'postal_code' => '45127',
            'city' => 'Essen',
            'street' => 'Alter Weg',
            'house_number' => '5',
            'is_active' => true,
            'version_valid_from' => now(),
            'version_valid_until' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('external_import_review_items')->where('id', $reviewId)->update([
            'details' => json_encode([
                'external_name' => 'Better External Place',
                'candidates' => [[
                    'place_id' => $existingPlaceId,
                    'name' => 'Older Existing Place',
                    'distance_m' => 15,
                    'name_similarity' => 80.0,
                ]],
            ]),
            'updated_at' => now(),
        ]);

        $newPlaceId = app(ExternalRecordReviewService::class)
            ->createPlaceAsMainAndMerge($reviewId, $existingPlaceId, $actor);

        $this->assertNotSame($existingPlaceId, $newPlaceId);
        $this->assertDatabaseHas('places', [
            'id' => $newPlaceId,
            'name' => 'Better External Place',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('places', [
            'id' => $existingPlaceId,
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('place_addresses', [
            'place_id' => $newPlaceId,
            'postal_code' => '45127',
            'city' => 'Essen',
            'street' => 'Alter Weg',
        ]);
        $this->assertDatabaseHas('external_records', [
            'id' => $recordId,
            'place_id' => $newPlaceId,
            'classification' => 'created',
        ]);
        $this->assertDatabaseHas('place_merges', [
            'source_place_id' => $existingPlaceId,
            'target_place_id' => $newPlaceId,
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('external_import_review_items', [
            'id' => $reviewId,
            'status' => 'resolved',
        ]);
    }

    public function test_review_can_create_published_place_with_source_basis_data(): void
    {
        [$actor, $sourceId, $recordId, $reviewId] = $this->reviewFixture([
            'external_id' => 'EXT-NEW',
            'place' => [
                'suggested_place_type' => 'rest-area',
                'name' => 'External Rest Area',
                'description' => 'Beschreibung aus der Quelle.',
                'latitude' => 51.1234,
                'longitude' => 7.5678,
                'coordinate_source' => 'parking_location',
                'address' => [
                    'street' => 'Autobahn',
                    'house_number' => null,
                    'postal_code' => '45127',
                    'city' => 'Essen',
                    'country_code' => 'DE',
                ],
                'operator' => [
                    'name' => 'Test Betreiber',
                    'phone' => '+49 201 123',
                    'email' => 'test@example.org',
                    'url' => 'https://example.org',
                ],
                'parking_spaces_total' => 25,
            ],
            'features' => [
                'toilet' => ['status' => 'available', 'source_type' => 'toilet', 'conflict' => false],
            ],
            'vehicle_type_capacities' => [
                'car' => 15,
                'truck' => 10,
            ],
        ]);

        $before = DB::table('places')->count();

        $placeId = app(ExternalRecordReviewService::class)->createPlace($reviewId, $actor);

        $this->assertSame($before + 1, DB::table('places')->count());
        $this->assertDatabaseHas('places', [
            'id' => $placeId,
            'name' => 'External Rest Area',
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => 'unclear',
            'created_by' => $actor->id,
            'approved_by' => $actor->id,
        ]);
        $this->assertDatabaseHas('place_addresses', [
            'place_id' => $placeId,
            'postal_code' => '45127',
            'city' => 'Essen',
            'country_code' => 'DE',
        ]);
        $this->assertDatabaseHas('place_details', [
            'place_id' => $placeId,
            'operator_name' => 'Test Betreiber',
            'pitch_count' => 25,
            'pitch_count_source' => 'direct',
        ]);

        $carId = DB::table('vehicle_types')->where('slug', 'car')->value('id');
        $truckId = DB::table('vehicle_types')->where('slug', 'truck')->value('id');
        $this->assertDatabaseHas('place_vehicle_types', [
            'place_id' => $placeId,
            'vehicle_type_id' => $carId,
            'capacity' => 15,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('place_vehicle_types', [
            'place_id' => $placeId,
            'vehicle_type_id' => $truckId,
            'capacity' => 10,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('place_contacts', [
            'place_id' => $placeId,
            'contact_type' => 'website',
            'value' => 'https://example.org',
        ]);
        $this->assertDatabaseHas('external_records', [
            'id' => $recordId,
            'place_id' => $placeId,
            'classification' => 'created',
        ]);
        $this->assertDatabaseHas('place_history', [
            'place_id' => $placeId,
            'external_source_id' => $sourceId,
            'action' => 'external_place_created',
        ]);

        // Definite source values are materialized as initial Camperwolf
        // values while the original external record remains the provenance layer.
        $toiletId = DB::table('features')->where('slug', 'toilet')->value('id');
        $this->assertDatabaseHas('place_features', [
            'place_id' => $placeId,
            'feature_id' => $toiletId,
            'status' => 'available',
            'is_active' => true,
            'internal_comment' => 'Initial aus externer Quelle übernommen.',
        ]);
    }

    public function test_vehicle_capacities_are_summed_when_source_has_no_direct_total(): void
    {
        [$actor, , , $reviewId] = $this->reviewFixture([
            'external_id' => 'EXT-DERIVED-TOTAL',
            'place' => [
                'suggested_place_type' => 'rest-area',
                'name' => 'Derived Capacity Rest Area',
                'latitude' => 51.1234,
                'longitude' => 7.5678,
                'address' => [],
                'operator' => [],
                'parking_spaces_total' => null,
            ],
            'features' => [],
            // Compatibility path for records staged before the
            // Camperwolf vehicle-type mapping was added.
            'vehicle_capacities' => [
                'car' => 15,
                'lorry' => 3,
                'bus' => 2,
            ],
        ]);

        $placeId = app(ExternalRecordReviewService::class)->createPlace($reviewId, $actor);

        $this->assertDatabaseHas('place_details', [
            'place_id' => $placeId,
            'pitch_count' => 20,
            'pitch_count_source' => 'summed_vehicle_capacities',
        ]);

        $coachId = DB::table('vehicle_types')->where('slug', 'coach')->value('id');
        $this->assertDatabaseHas('place_vehicle_types', [
            'place_id' => $placeId,
            'vehicle_type_id' => $coachId,
            'capacity' => 2,
        ]);
    }

    public function test_missing_coordinates_cannot_create_a_place(): void
    {
        [$actor, , , $reviewId] = $this->reviewFixture([
            'external_id' => 'NOCOORD',
            'place' => [
                'suggested_place_type' => 'rest-area',
                'name' => 'No Coordinates',
                'latitude' => null,
                'longitude' => null,
            ],
        ], 'missing_coordinates');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Name und gültige Koordinaten');

        app(ExternalRecordReviewService::class)->createPlace($reviewId, $actor);
    }

    public function test_ignore_closes_review_without_touching_places(): void
    {
        [$actor, , $recordId, $reviewId] = $this->reviewFixture();
        $before = DB::table('places')->count();

        app(ExternalRecordReviewService::class)->ignore($reviewId, $actor, 'Nicht relevant.');

        $this->assertSame($before, DB::table('places')->count());
        $this->assertDatabaseHas('external_records', [
            'id' => $recordId,
            'classification' => 'ignored',
            'place_id' => null,
        ]);
        $this->assertDatabaseHas('external_import_review_items', [
            'id' => $reviewId,
            'status' => 'resolved',
            'resolution_note' => 'Nicht relevant.',
        ]);
    }

    public function test_defer_keeps_review_pending(): void
    {
        [$actor, , , $reviewId] = $this->reviewFixture();

        app(ExternalRecordReviewService::class)->defer($reviewId, $actor, 'Später prüfen.');

        $review = DB::table('external_import_review_items')->where('id', $reviewId)->first();

        $this->assertSame('pending', $review->status);
        $details = json_decode($review->details, true);
        $this->assertSame('Später prüfen.', $details['deferred']['note']);
    }

    public function test_bulk_defer_marks_all_non_research_reviews_and_leaves_research_reviews_untouched(): void
    {
        [$actor, , , $firstReviewId] = $this->reviewFixture();
        [, , , $secondReviewId] = $this->reviewFixture(null, 'missing_coordinates');
        [, , , $researchReviewId] = $this->reviewFixture(null, 'research_update');

        $result = app(ExternalRecordReviewService::class)->deferAllPending($actor);

        $this->assertSame(2, $result['attempted']);
        $this->assertSame(2, $result['deferred']);
        $this->assertSame([], $result['skipped']);

        foreach ([$firstReviewId, $secondReviewId] as $reviewId) {
            $review = DB::table('external_import_review_items')->where('id', $reviewId)->first();
            $details = json_decode((string) $review->details, true);

            $this->assertSame('pending', $review->status);
            $this->assertSame('Per Sammelaktion zurückgestellt.', $details['deferred']['note']);
        }

        $research = DB::table('external_import_review_items')->where('id', $researchReviewId)->first();
        $researchDetails = json_decode((string) $research->details, true);
        $this->assertSame('pending', $research->status);
        $this->assertArrayNotHasKey('deferred', $researchDetails);
    }

    public function test_bulk_ignore_resolves_all_ignorable_non_research_reviews_and_leaves_research_reviews_open(): void
    {
        [$actor, , $firstRecordId, $firstReviewId] = $this->reviewFixture();
        [, , $secondRecordId, $secondReviewId] = $this->reviewFixture(null, 'missing_coordinates');
        [, , $researchRecordId, $researchReviewId] = $this->reviewFixture(null, 'research_update');

        $result = app(ExternalRecordReviewService::class)->ignoreAllPending($actor);

        $this->assertSame(2, $result['attempted']);
        $this->assertSame(2, $result['ignored']);
        $this->assertSame([], $result['skipped']);

        foreach ([[$firstRecordId, $firstReviewId], [$secondRecordId, $secondReviewId]] as [$recordId, $reviewId]) {
            $this->assertDatabaseHas('external_records', [
                'id' => $recordId,
                'classification' => 'ignored',
            ]);
            $this->assertDatabaseHas('external_import_review_items', [
                'id' => $reviewId,
                'status' => 'resolved',
            ]);
        }

        $this->assertDatabaseHas('external_records', [
            'id' => $researchRecordId,
            'classification' => 'possible_duplicate',
        ]);
        $this->assertDatabaseHas('external_import_review_items', [
            'id' => $researchReviewId,
            'status' => 'pending',
        ]);
    }

    public function test_selected_reviews_can_be_deferred_without_touching_unselected_or_protected_reviews(): void
    {
        [$actor, , , $firstReviewId] = $this->reviewFixture();
        [, , , $secondReviewId] = $this->reviewFixture(null, 'missing_coordinates');
        [, , , $unselectedReviewId] = $this->reviewFixture();
        [, , , $researchReviewId] = $this->reviewFixture(null, 'research_update');

        $result = app(ExternalRecordReviewService::class)->deferSelected(
            [$firstReviewId, $secondReviewId, $researchReviewId],
            $actor,
        );

        $this->assertSame(3, $result['attempted']);
        $this->assertSame(2, $result['processed']);
        $this->assertSame([$researchReviewId], $result['skipped']);

        foreach ([$firstReviewId, $secondReviewId] as $reviewId) {
            $review = DB::table('external_import_review_items')->where('id', $reviewId)->first();
            $details = json_decode((string) $review->details, true);

            $this->assertSame('pending', $review->status);
            $this->assertSame('Per Auswahl zurückgestellt.', $details['deferred']['note']);
        }

        foreach ([$unselectedReviewId, $researchReviewId] as $reviewId) {
            $review = DB::table('external_import_review_items')->where('id', $reviewId)->first();
            $details = json_decode((string) $review->details, true);
            $this->assertArrayNotHasKey('deferred', $details);
        }
    }

    public function test_selected_reviews_can_be_ignored_without_touching_unselected_or_protected_reviews(): void
    {
        [$actor, , $firstRecordId, $firstReviewId] = $this->reviewFixture();
        [, , $secondRecordId, $secondReviewId] = $this->reviewFixture(null, 'missing_coordinates');
        [, , $unselectedRecordId, $unselectedReviewId] = $this->reviewFixture();
        [, , $researchRecordId, $researchReviewId] = $this->reviewFixture(null, 'research_update');

        $result = app(ExternalRecordReviewService::class)->ignoreSelected(
            [$firstReviewId, $secondReviewId, $researchReviewId],
            $actor,
        );

        $this->assertSame(3, $result['attempted']);
        $this->assertSame(2, $result['processed']);
        $this->assertSame([$researchReviewId], $result['skipped']);

        foreach ([[$firstRecordId, $firstReviewId], [$secondRecordId, $secondReviewId]] as [$recordId, $reviewId]) {
            $this->assertDatabaseHas('external_records', [
                'id' => $recordId,
                'classification' => 'ignored',
            ]);
            $this->assertDatabaseHas('external_import_review_items', [
                'id' => $reviewId,
                'status' => 'resolved',
            ]);
        }

        foreach ([[$unselectedRecordId, $unselectedReviewId], [$researchRecordId, $researchReviewId]] as [$recordId, $reviewId]) {
            $this->assertDatabaseHas('external_records', [
                'id' => $recordId,
                'classification' => 'possible_duplicate',
            ]);
            $this->assertDatabaseHas('external_import_review_items', [
                'id' => $reviewId,
                'status' => 'pending',
            ]);
        }
    }

    public function test_missing_coordinates_can_be_completed_and_reclassified_as_new_candidate(): void
    {
        [$actor, , $recordId, $reviewId] = $this->reviewFixture([
            'external_id' => 'NOCOORD-FIX',
            'place' => [
                'suggested_place_type' => 'rest-area',
                'name' => 'Coordinate Fix Candidate',
                'latitude' => null,
                'longitude' => null,
            ],
            'features' => [],
        ], 'missing_coordinates');

        $result = app(ExternalRecordReviewService::class)->updateMissingCoordinates(
            $reviewId,
            53.1234567,
            9.7654321,
            $actor,
        );

        $this->assertSame('new_candidate', $result['classification']);
        $this->assertDatabaseHas('external_records', [
            'id' => $recordId,
            'classification' => 'new_candidate',
        ]);
        $this->assertDatabaseHas('external_import_review_items', [
            'id' => $reviewId,
            'status' => 'superseded',
        ]);

        $record = DB::table('external_records')->where('id', $recordId)->first();
        $mapped = json_decode((string) $record->normalized_data, true);
        $overrides = json_decode((string) $record->manual_overrides, true);

        $this->assertSame(53.1234567, $mapped['place']['latitude']);
        $this->assertSame(9.7654321, $mapped['place']['longitude']);
        $this->assertSame('manual-review', $mapped['place']['coordinate_source']);
        $this->assertSame(53.1234567, $overrides['place.latitude']);
        $this->assertSame(9.7654321, $overrides['place.longitude']);
    }

    public function test_possible_reopen_can_set_linked_place_back_to_open(): void
    {
        $actor = User::factory()->create(['email_verified_at' => now()]);
        $sourceId = (int) DB::table('external_sources')->insertGetId([
            'slug' => 'review-reopen-test',
            'name' => 'Review Reopen Test',
            'source_type' => 'test',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $placeId = $this->place('Reopen Review Place', 51.0, 7.0);
        DB::table('places')->where('id', $placeId)->update([
            'opening_status' => 'permanently_closed',
            'updated_at' => now(),
        ]);

        $mapped = [
            'external_id' => 'REOPEN-REVIEW-1',
            'place' => [
                'name' => 'Reopen Review Place',
                'latitude' => 51.0,
                'longitude' => 7.0,
                'opening_status' => 'open',
            ],
        ];

        $recordId = (int) DB::table('external_records')->insertGetId([
            'external_source_id' => $sourceId,
            'external_id' => 'REOPEN-REVIEW-1',
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

        $runId = (int) DB::table('external_import_runs')->insertGetId([
            'external_source_id' => $sourceId,
            'mode' => 'sync',
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $reviewId = (int) DB::table('external_import_review_items')->insertGetId([
            'external_import_run_id' => $runId,
            'external_source_id' => $sourceId,
            'external_record_id' => $recordId,
            'place_id' => $placeId,
            'type' => 'possible_reopen',
            'severity' => 'warning',
            'status' => 'pending',
            'details' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        self::assertSame(
            $placeId,
            app(ExternalRecordReviewService::class)->reopenPossibleReopen($reviewId, $actor),
        );

        $this->assertDatabaseHas('places', [
            'id' => $placeId,
            'opening_status' => 'open',
        ]);
        $this->assertDatabaseHas('external_import_review_items', [
            'id' => $reviewId,
            'status' => 'resolved',
            'resolved_by' => $actor->id,
        ]);
        $this->assertDatabaseHas('place_history', [
            'place_id' => $placeId,
            'action' => 'external_possible_reopen_confirmed',
        ]);
    }

    public function test_new_candidate_can_be_created_directly(): void
    {
        $actor = User::factory()->create(['email_verified_at' => now()]);

        $sourceId = (int) DB::table('external_sources')->insertGetId([
            'slug' => 'candidate-create-test',
            'name' => 'Candidate Create Test',
            'source_type' => 'test',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $normalized = [
            'external_id' => 'CANDIDATE-1',
            'place' => [
                'suggested_place_type' => 'rest-area',
                'name' => 'Direct Candidate Place',
                'latitude' => 51.2,
                'longitude' => 7.2,
                'address' => [],
                'operator' => [],
                'parking_spaces_total' => null,
            ],
            'features' => [],
        ];

        $recordId = (int) DB::table('external_records')->insertGetId([
            'external_source_id' => $sourceId,
            'external_id' => 'CANDIDATE-1',
            'status' => 'active',
            'classification' => 'new_candidate',
            'normalized_data' => json_encode($normalized),
            'normalized_hash' => hash('sha256', json_encode($normalized)),
            'first_seen_at' => now(),
            'last_seen_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $placeId = app(ExternalRecordReviewService::class)->createPlaceFromCandidate($recordId, $actor);

        $this->assertDatabaseHas('places', [
            'id' => $placeId,
            'name' => 'Direct Candidate Place',
            'publication_status' => 'published',
        ]);
        $this->assertDatabaseHas('external_records', [
            'id' => $recordId,
            'place_id' => $placeId,
            'classification' => 'created',
        ]);
        $this->assertDatabaseHas('place_history', [
            'place_id' => $placeId,
            'external_source_id' => $sourceId,
            'action' => 'external_place_created',
        ]);
    }

    public function test_selected_new_candidates_can_be_created_as_atomic_bulk(): void
    {
        $actor = User::factory()->create(['email_verified_at' => now()]);
        $sourceId = $this->candidateSource('candidate-bulk-create');

        $first = $this->candidateRecord($sourceId, 'BULK-1', 'Bulk Place One', 'new_candidate');
        $second = $this->candidateRecord($sourceId, 'BULK-2', 'Bulk Place Two', 'new_candidate');
        $unselected = $this->candidateRecord($sourceId, 'BULK-3', 'Bulk Place Three', 'new_candidate');

        $created = app(ExternalRecordReviewService::class)->createPlacesFromCandidates(
            [$first, $second],
            $actor,
        );

        $this->assertCount(2, $created);
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
        $this->assertDatabaseHas('places', ['name' => 'Bulk Place One']);
        $this->assertDatabaseHas('places', ['name' => 'Bulk Place Two']);
        $this->assertDatabaseMissing('places', ['name' => 'Bulk Place Three']);
    }

    public function test_create_all_processes_open_candidates_in_bounded_source_filtered_batches(): void
    {
        $actor = User::factory()->create(['email_verified_at' => now()]);
        $sourceId = $this->candidateSource('candidate-create-all');
        $otherSourceId = $this->candidateSource('candidate-create-all-other');
        $sourceSlug = (string) DB::table('external_sources')->where('id', $sourceId)->value('slug');

        $first = $this->candidateRecord($sourceId, 'ALL-1', 'All Place One', 'new_candidate');
        $second = $this->candidateRecord($sourceId, 'ALL-2', 'All Place Two', 'new_candidate');
        $third = $this->candidateRecord($sourceId, 'ALL-3', 'All Place Three', 'new_candidate');
        $other = $this->candidateRecord($otherSourceId, 'ALL-OTHER', 'Other Source Place', 'new_candidate');

        $service = app(ExternalRecordReviewService::class);

        $firstBatch = $service->createNextCandidateBatch($actor, $sourceSlug, 2);

        $this->assertSame(2, $firstBatch['created']);
        $this->assertSame(1, $firstBatch['remaining']);
        $this->assertSame(3, $firstBatch['total_before']);

        $createdInFirstBatch = DB::table('external_records')
            ->whereIn('id', [$first, $second, $third])
            ->where('classification', 'created')
            ->count();
        $this->assertSame(2, $createdInFirstBatch);

        $this->assertDatabaseHas('external_records', [
            'id' => $other,
            'classification' => 'new_candidate',
            'place_id' => null,
        ]);

        $secondBatch = $service->createNextCandidateBatch($actor, $sourceSlug, 2);

        $this->assertSame(1, $secondBatch['created']);
        $this->assertSame(0, $secondBatch['remaining']);
        $this->assertSame(1, $secondBatch['total_before']);

        $this->assertSame(
            3,
            DB::table('external_records')
                ->whereIn('id', [$first, $second, $third])
                ->where('classification', 'created')
                ->count(),
        );
    }

    public function test_bulk_creation_rolls_back_everything_when_one_candidate_is_no_longer_open(): void
    {
        $actor = User::factory()->create(['email_verified_at' => now()]);
        $sourceId = $this->candidateSource('candidate-bulk-atomic');

        $valid = $this->candidateRecord($sourceId, 'BULK-VALID', 'Bulk Valid Place', 'new_candidate');
        $invalid = $this->candidateRecord($sourceId, 'BULK-INVALID', 'Bulk Invalid Place', 'possible_duplicate');
        $beforePlaces = DB::table('places')->count();

        try {
            app(ExternalRecordReviewService::class)->createPlacesFromCandidates(
                [$valid, $invalid],
                $actor,
            );
            $this->fail('Expected bulk creation to reject a non-new_candidate record.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('kein Platz übernommen', $e->getMessage());
        }

        $this->assertSame($beforePlaces, DB::table('places')->count());
        $this->assertDatabaseHas('external_records', [
            'id' => $valid,
            'classification' => 'new_candidate',
            'place_id' => null,
        ]);
        $this->assertDatabaseHas('external_records', [
            'id' => $invalid,
            'classification' => 'possible_duplicate',
            'place_id' => null,
        ]);
    }

    public function test_new_candidate_can_be_ignored_without_creating_place(): void
    {
        $actor = User::factory()->create(['email_verified_at' => now()]);

        $sourceId = (int) DB::table('external_sources')->insertGetId([
            'slug' => 'candidate-ignore-test',
            'name' => 'Candidate Ignore Test',
            'source_type' => 'test',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $normalized = [
            'external_id' => 'CANDIDATE-IGNORE',
            'place' => [
                'suggested_place_type' => 'rest-area',
                'name' => 'Ignored Candidate',
                'latitude' => 51.3,
                'longitude' => 7.3,
            ],
            'features' => [],
        ];

        $recordId = (int) DB::table('external_records')->insertGetId([
            'external_source_id' => $sourceId,
            'external_id' => 'CANDIDATE-IGNORE',
            'status' => 'active',
            'classification' => 'new_candidate',
            'normalized_data' => json_encode($normalized),
            'normalized_hash' => hash('sha256', json_encode($normalized)),
            'first_seen_at' => now(),
            'last_seen_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $before = DB::table('places')->count();

        app(ExternalRecordReviewService::class)->ignoreCandidate($recordId, $actor, 'Nicht relevant.');

        $this->assertSame($before, DB::table('places')->count());
        $this->assertDatabaseHas('external_records', [
            'id' => $recordId,
            'classification' => 'ignored',
            'place_id' => null,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'external_record',
            'entity_id' => $recordId,
            'action' => 'external_candidate_ignored',
        ]);
    }

    private function candidateSource(string $slug): int
    {
        return (int) DB::table('external_sources')->insertGetId([
            'slug' => $slug.'-'.uniqid(),
            'name' => 'Candidate Bulk Source',
            'source_type' => 'test',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function candidateRecord(int $sourceId, string $externalId, string $name, string $classification): int
    {
        $normalized = [
            'external_id' => $externalId,
            'place' => [
                'suggested_place_type' => 'rest-area',
                'name' => $name,
                'latitude' => 51.4 + (DB::table('external_records')->count() / 1000),
                'longitude' => 7.4,
                'address' => [],
                'operator' => [],
                'parking_spaces_total' => null,
            ],
            'features' => [
                'toilet' => ['status' => 'available', 'source_type' => 'toilet', 'conflict' => false],
            ],
        ];

        return (int) DB::table('external_records')->insertGetId([
            'external_source_id' => $sourceId,
            'external_id' => $externalId,
            'status' => 'active',
            'classification' => $classification,
            'normalized_data' => json_encode($normalized),
            'normalized_hash' => hash('sha256', json_encode($normalized)),
            'first_seen_at' => now(),
            'last_seen_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function reviewFixture(?array $normalized = null, string $type = 'possible_duplicate'): array
    {
        $actor = User::factory()->create(['email_verified_at' => now()]);

        $sourceId = (int) DB::table('external_sources')->insertGetId([
            'slug' => 'review-test-source-'.uniqid(),
            'name' => 'Review Test Source',
            'source_type' => 'test',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $normalized ??= [
            'external_id' => 'EXT-1',
            'place' => [
                'suggested_place_type' => 'rest-area',
                'name' => 'External Place',
                'latitude' => 51.1,
                'longitude' => 7.1,
                'address' => [],
                'operator' => [],
                'parking_spaces_total' => null,
            ],
            'features' => [],
        ];

        $recordId = (int) DB::table('external_records')->insertGetId([
            'external_source_id' => $sourceId,
            'external_id' => $normalized['external_id'],
            'status' => 'active',
            'classification' => $type === 'missing_coordinates' ? 'needs_review' : 'possible_duplicate',
            'normalized_data' => json_encode($normalized),
            'normalized_hash' => hash('sha256', json_encode($normalized)),
            'first_seen_at' => now(),
            'last_seen_at' => now(),
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

        $reviewId = (int) DB::table('external_import_review_items')->insertGetId([
            'external_import_run_id' => $runId,
            'external_source_id' => $sourceId,
            'external_record_id' => $recordId,
            'type' => $type,
            'severity' => 'warning',
            'status' => 'pending',
            'details' => json_encode(['external_name' => $normalized['place']['name'] ?? null]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$actor, $sourceId, $recordId, $reviewId];
    }

    private function place(string $name, float $lat, float $lon): int
    {
        $placeTypeId = DB::table('place_types')->where('slug', 'rest-area')->value('id')
            ?: DB::table('place_types')->where('is_active', true)->value('id');

        return (int) DB::table('places')->insertGetId([
            'place_type_id' => $placeTypeId,
            'name' => $name,
            'slug' => 'review-existing-'.uniqid(),
            'latitude' => $lat,
            'longitude' => $lon,
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => 'unclear',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
