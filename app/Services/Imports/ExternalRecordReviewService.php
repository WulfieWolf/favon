<?php

namespace App\Services\Imports;

use App\Models\User;
use App\Services\PlaceHistoryService;
use App\Services\PlaceTombstoneService;
use App\Services\PlaceMergeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ExternalRecordReviewService
{
    public function link(int $reviewId, int $placeId, User $actor): int
    {
        return DB::transaction(function () use ($reviewId, $placeId, $actor): int {
            [$review, $record] = $this->pendingReview($reviewId);

            $resolvedPlaceId = app(PlaceMergeService::class)->resolveActivePlaceId($placeId);

            if (! $resolvedPlaceId) {
                throw new RuntimeException(__('admin_imports.errors.invalid_place'));
            }

            $placeId = $resolvedPlaceId;
            $place = DB::table('places')
                ->where('id', $placeId)
                ->where('is_active', true)
                ->first(['id', 'name']);

            if (! $place) {
                throw new RuntimeException(__('admin_imports.errors.invalid_place'));
            }

            DB::table('external_records')->where('id', $record->id)->update([
                'place_id' => $placeId,
                'classification' => 'linked',
                'classified_at' => now(),
                'updated_at' => now(),
            ]);

            $this->resolveRecordReviews(
                (int) $record->id,
                (int) $actor->id,
                'resolved',
                __('admin_imports.notes.linked_existing', ['id' => $placeId]),
            );

            app(PlaceHistoryService::class)->addExternalSource(
                $placeId,
                (int) $review->external_source_id,
                'external_source_linked',
                __('place_profile.history.actions.external_source_linked'),
                [
                    'external_record_id' => (int) $record->id,
                    'external_id' => $record->external_id,
                ],
            );

            $this->audit(
                $actor,
                $placeId,
                'external_record_linked',
                [
                    'external_record_id' => (int) $record->id,
                    'external_id' => $record->external_id,
                    'external_source_id' => (int) $review->external_source_id,
                ],
            );

            return $placeId;
        });
    }

    public function linkDuplicateGroup(array $reviewIds, int $placeId, User $actor): array
    {
        $reviewIds = $this->validatedDuplicateGroupReviewIds($reviewIds);

        return DB::transaction(function () use ($reviewIds, $placeId, $actor): array {
            $linked = [];

            foreach ($reviewIds as $reviewId) {
                $linked[$reviewId] = $this->link($reviewId, $placeId, $actor);
            }

            return [
                'place_id' => $placeId,
                'linked' => count($linked),
            ];
        });
    }

    public function createPlaceFromDuplicateGroup(int $primaryReviewId, array $reviewIds, User $actor): array
    {
        $reviewIds = $this->validatedDuplicateGroupReviewIds($reviewIds);

        if (! in_array($primaryReviewId, $reviewIds, true)) {
            throw new RuntimeException(__('admin_imports.errors.primary_not_group'));
        }

        return DB::transaction(function () use ($primaryReviewId, $reviewIds, $actor): array {
            $placeId = $this->createPlace($primaryReviewId, $actor);
            $linked = 1;

            foreach ($reviewIds as $reviewId) {
                if ($reviewId === $primaryReviewId) {
                    continue;
                }

                $this->link($reviewId, $placeId, $actor);
                $linked++;
            }

            return [
                'place_id' => $placeId,
                'linked' => $linked,
            ];
        });
    }

    public function createSeparatePlaceFromDuplicateGroupMember(int $reviewId, User $actor): int
    {
        $review = DB::table('external_import_review_items')
            ->where('id', $reviewId)
            ->where('status', 'pending')
            ->where('type', 'external_duplicate_group')
            ->first(['id']);

        if (! $review) {
            throw new RuntimeException(__('admin_imports.errors.not_open_group'));
        }

        return $this->createPlace($reviewId, $actor);
    }

    public function createPlaceAsMainAndMerge(int $reviewId, int $duplicatePlaceId, User $actor): int
    {
        return DB::transaction(function () use ($reviewId, $duplicatePlaceId, $actor): int {
            [$review, $record] = $this->pendingReview($reviewId);

            if ($review->type !== 'possible_duplicate') {
                throw new RuntimeException(__('admin_imports.errors.not_possible_duplicate'));
            }

            $mergeService = app(PlaceMergeService::class);
            $candidateIds = collect(json_decode((string) $review->details, true)['candidates'] ?? [])
                ->pluck('place_id')
                ->filter(fn ($id) => is_numeric($id))
                ->map(fn ($id) => (int) $id)
                ->all();

            $resolvedCandidateIds = collect($candidateIds)
                ->map(fn (int $id) => $mergeService->resolveActivePlaceId($id))
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            $resolvedDuplicatePlaceId = $mergeService->resolveActivePlaceId($duplicatePlaceId);

            if (! $resolvedDuplicatePlaceId || ! in_array($resolvedDuplicatePlaceId, $resolvedCandidateIds, true)) {
                throw new RuntimeException(__('admin_imports.errors.merge_target_not_candidate'));
            }

            $duplicatePlaceId = $resolvedDuplicatePlaceId;

            $newPlaceId = $this->createPlaceFromRecordObject(
                $record,
                (int) $review->external_source_id,
                $actor,
                __('admin_imports.notes.created_place', ['id' => '{{PLACE_ID}}']),
            );

            $merges = app(PlaceMergeService::class);
            $comparison = $merges->comparison($newPlaceId, $duplicatePlaceId);

            $fieldChoices = collect($comparison['fields'])
                ->mapWithKeys(fn (array $field, string $key) => [$key => 'main'])
                ->all();

            $recordChoices = collect($comparison['records'])
                ->mapWithKeys(function (array $item): array {
                    $choice = $item['main_row_id']
                        ? 'main'
                        : ($item['duplicate_row_id'] ? 'duplicate' : 'main');

                    return [$item['token'] => $choice];
                })
                ->all();

            $merges->merge(
                $actor,
                $newPlaceId,
                $duplicatePlaceId,
                $fieldChoices,
                $recordChoices,
            );

            return $newPlaceId;
        });
    }

    public function createPlace(int $reviewId, User $actor): int
    {
        return DB::transaction(function () use ($reviewId, $actor): int {
            [$review, $record] = $this->pendingReview($reviewId);

            return $this->createPlaceFromRecordObject(
                $record,
                (int) $review->external_source_id,
                $actor,
                __('admin_imports.notes.created_place', ['id' => '{{PLACE_ID}}']),
            );
        });
    }

    public function createPlaceFromCandidate(int $recordId, User $actor): int
    {
        return DB::transaction(function () use ($recordId, $actor): int {
            $record = DB::table('external_records')
                ->where('id', $recordId)
                ->lockForUpdate()
                ->first();

            if (! $record || $record->status !== 'active') {
                throw new RuntimeException(__('admin_imports.errors.record_inactive'));
            }

            if ($record->classification !== 'new_candidate') {
                throw new RuntimeException(__('admin_imports.errors.not_candidate'));
            }

            if ($record->place_id) {
                throw new RuntimeException(__('admin_imports.errors.already_linked'));
            }

            return $this->createPlaceFromRecordObject(
                $record,
                (int) $record->external_source_id,
                $actor,
                __('admin_imports.notes.created_place', ['id' => '{{PLACE_ID}}']),
            );
        });
    }

    public function createPlacesFromCandidates(array $recordIds, User $actor, int $limit = 50): array
    {
        $recordIds = array_values(array_unique(array_map('intval', $recordIds)));

        if ($recordIds === []) {
            throw new RuntimeException(__('admin_imports.errors.no_candidates_selected'));
        }

        if (count($recordIds) > $limit) {
            throw new RuntimeException(__('admin_imports.errors.bulk_limit', ['limit' => $limit]));
        }

        return DB::transaction(function () use ($recordIds, $actor): array {
            $records = DB::table('external_records')
                ->whereIn('id', $recordIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($records->count() !== count($recordIds)) {
                throw new RuntimeException(__('admin_imports.errors.candidate_missing'));
            }

            foreach ($recordIds as $recordId) {
                $record = $records->get($recordId);

                if (! $record || $record->status !== 'active' || $record->classification !== 'new_candidate' || $record->place_id) {
                    throw new RuntimeException(__('admin_imports.errors.candidate_closed'));
                }
            }

            $created = [];

            foreach ($recordIds as $recordId) {
                $record = $records->get($recordId);
                $created[$recordId] = $this->createPlaceFromRecordObject(
                    $record,
                    (int) $record->external_source_id,
                    $actor,
                    __('admin_imports.notes.created_place_bulk', ['id' => '{{PLACE_ID}}']),
                );
            }

            return $created;
        });
    }

    public function createNextCandidateBatch(
        User $actor,
        ?string $sourceSlug = null,
        int $limit = 50,
    ): array {
        $limit = max(1, min(50, $limit));
        $sourceSlug = trim((string) $sourceSlug);

        $query = DB::table('external_records as er')
            ->join('external_sources as es', 'es.id', '=', 'er.external_source_id')
            ->where('er.status', 'active')
            ->where('er.classification', 'new_candidate')
            ->whereNull('er.place_id')
            ->when($sourceSlug !== '', fn ($builder) => $builder->where('es.slug', $sourceSlug));

        $before = (int) (clone $query)->count();

        if ($before === 0) {
            return [
                'created' => 0,
                'remaining' => 0,
                'total_before' => 0,
            ];
        }

        $recordIds = (clone $query)
            ->orderBy('er.id')
            ->limit($limit)
            ->pluck('er.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $created = $this->createPlacesFromCandidates($recordIds, $actor, $limit);
        $remaining = (int) (clone $query)->count();

        return [
            'created' => count($created),
            'remaining' => $remaining,
            'total_before' => $before,
        ];
    }

    public function ignoreCandidate(int $recordId, User $actor, ?string $note = null): void
    {
        DB::transaction(function () use ($recordId, $actor, $note): void {
            $record = DB::table('external_records')
                ->where('id', $recordId)
                ->lockForUpdate()
                ->first();

            if (! $record || $record->status !== 'active' || $record->classification !== 'new_candidate' || $record->place_id) {
                throw new RuntimeException(__('admin_imports.errors.candidate_not_open'));
            }

            DB::table('external_records')->where('id', $recordId)->update([
                'classification' => 'ignored',
                'classified_at' => now(),
                'updated_at' => now(),
            ]);

            $this->audit(
                $actor,
                null,
                'external_candidate_ignored',
                [
                    'external_record_id' => (int) $record->id,
                    'external_id' => $record->external_id,
                    'note' => trim((string) $note) ?: null,
                ],
            );
        });
    }

    private function createPlaceFromRecordObject(object $record, int $sourceId, User $actor, string $resolutionNote, bool $allowTombstoneOverride = false): int
    {
            if ($record->place_id) {
                throw new RuntimeException(__('admin_imports.errors.already_linked'));
            }

            $mapped = json_decode((string) $record->normalized_data, true);
            if (! is_array($mapped)) {
                throw new RuntimeException(__('admin_imports.errors.invalid_normalized'));
            }

            $placeData = $mapped['place'] ?? [];
            $vehicleTypeCapacities = $this->vehicleTypeCapacities($mapped);
            $name = trim((string) ($placeData['name'] ?? ''));
            $latitude = $placeData['latitude'] ?? null;
            $longitude = $placeData['longitude'] ?? null;

            if ($name === '' || ! is_numeric($latitude) || ! is_numeric($longitude)) {
                throw new RuntimeException(__('admin_imports.errors.need_name_coordinates'));
            }

            $latitude = (float) $latitude;
            $longitude = (float) $longitude;

            if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
                throw new RuntimeException(__('admin_imports.errors.coordinates_range'));
            }

            $typeSlug = trim((string) ($placeData['suggested_place_type'] ?? 'rest-area')) ?: 'rest-area';
            $placeTypeId = DB::table('place_types')
                ->where('slug', $typeSlug)
                ->where('is_active', true)
                ->value('id');

            if (! $placeTypeId) {
                $placeTypeId = DB::table('place_types')
                    ->where('slug', 'rest-area')
                    ->where('is_active', true)
                    ->value('id');
            }

            if (! $placeTypeId) {
                throw new RuntimeException(__('admin_imports.errors.no_place_type'));
            }

            $tombstone = app(PlaceTombstoneService::class)->check($latitude, $longitude, (int) $placeTypeId);
            if ($tombstone['status'] === PlaceTombstoneService::BLOCKED_SAME_TYPE && ! $allowTombstoneOverride) {
                throw new RuntimeException(__('admin_imports.errors.tombstone_blocked'));
            }

            $now = now();
            $slug = $this->uniqueSlug($name);
            $sourceType = DB::table('external_sources')->where('id', $sourceId)->value('source_type');
            $sourceOperatingStatus = trim((string) ($placeData['opening_status'] ?? ''));
            $operatingStatus = in_array($sourceOperatingStatus, ['open', 'temporarily_closed', 'seasonally_closed', 'permanently_closed', 'unclear'], true)
                ? $sourceOperatingStatus
                : ($sourceType === 'datex2' ? 'open' : 'unclear');

            $placeId = (int) DB::table('places')->insertGetId([
                'place_type_id' => $placeTypeId,
                'name' => $name,
                'slug' => $slug,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'publication_status' => 'published',
                'legal_status' => 'unclear',
                'opening_status' => $operatingStatus,
                'is_active' => true,
                'internal_comment' => 'Von einem Administrator aus einer validierten externen Quelle übernommen.',
                'created_by' => $actor->id,
                'approved_by' => $actor->id,
                'approved_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $this->createAddress($placeId, $placeData['address'] ?? [], $now);
            $this->createDetails($placeId, $placeData, $vehicleTypeCapacities, (int) $actor->id, $now);
            $this->createVehicleTypes($placeId, $vehicleTypeCapacities, $now);
            app(ExternalFeatureMaterializationService::class)->materializeForPlace($placeId, $mapped);
            $this->createDescription($placeId, $placeData, $now);
            $this->createContacts($placeId, $placeData['operator'] ?? [], (int) $actor->id, $now);

            DB::table('external_records')->where('id', $record->id)->update([
                'place_id' => $placeId,
                'classification' => 'created',
                'classified_at' => $now,
                'updated_at' => $now,
            ]);

            $this->resolveRecordReviews(
                (int) $record->id,
                (int) $actor->id,
                'resolved',
                str_replace('{{PLACE_ID}}', (string) $placeId, $resolutionNote),
            );

            app(PlaceHistoryService::class)->addExternalSource(
                $placeId,
                $sourceId,
                'external_place_created',
                __('place_profile.history.actions.external_place_created'),
                [
                    'external_record_id' => (int) $record->id,
                    'external_id' => $record->external_id,
                    'coordinate_source' => $placeData['coordinate_source'] ?? null,
                ],
            );

            $this->audit(
                $actor,
                $placeId,
                'external_place_created_by_admin',
                [
                    'external_record_id' => (int) $record->id,
                    'external_id' => $record->external_id,
                    'external_source_id' => $sourceId,
                    'name' => $name,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ],
            );

            return $placeId;
    }

    public function createPlaceFromDeletedReview(int $reviewId, User $actor): int
    {
        return DB::transaction(function () use ($reviewId, $actor): int {
            [$review, $record] = $this->pendingReview($reviewId);

            if (! in_array($review->type, ['nearby_deleted_place', 'deleted_place_changed'], true)) {
                throw new RuntimeException(__('admin_imports.errors.deleted_place_review_type'));
            }

            if ($record->place_id) {
                $linked = DB::table('places')->where('id', $record->place_id)->lockForUpdate()->first(['id', 'deleted_at']);
                if (! $linked || ! $linked->deleted_at) {
                    throw new RuntimeException(__('admin_imports.errors.deleted_place_link_missing'));
                }

                DB::table('external_records')->where('id', $record->id)->update([
                    'place_id' => null,
                    'classification' => 'new_candidate',
                    'classified_at' => now(),
                    'updated_at' => now(),
                ]);
                $record->place_id = null;
                $record->classification = 'new_candidate';
            }

            $placeId = $this->createPlaceFromRecordObject(
                $record,
                (int) $review->external_source_id,
                $actor,
                __('admin_imports.notes.recreated_after_tombstone', ['id' => '{{PLACE_ID}}']),
                true,
            );

            DB::table('audit_logs')->insert([
                'user_id' => $actor->id,
                'entity_type' => 'place',
                'entity_id' => $placeId,
                'action' => 'place_tombstone_override',
                'source' => 'admin',
                'old_values' => json_encode([
                    'review_id' => $reviewId,
                    'tombstone_place_id' => $review->place_id,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'new_values' => json_encode([
                    'created_place_id' => $placeId,
                    'external_record_id' => (int) $record->id,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'internal_comment' => null,
                'created_at' => now(),
            ]);

            return $placeId;
        });
    }

    public function ignoreDeletedPlaceReview(int $reviewId, User $actor, ?string $note = null): void
    {
        DB::transaction(function () use ($reviewId, $actor, $note): void {
            [$review, $record] = $this->pendingReview($reviewId);

            if (! in_array($review->type, ['nearby_deleted_place', 'deleted_place_changed'], true)) {
                throw new RuntimeException(__('admin_imports.errors.deleted_place_review_type'));
            }

            if ($review->type === 'nearby_deleted_place' && ! $record->place_id) {
                DB::table('external_records')->where('id', $record->id)->update([
                    'classification' => 'ignored',
                    'classified_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('external_import_review_items')->where('id', $reviewId)->update([
                'status' => 'resolved',
                'resolved_by' => $actor->id,
                'resolved_at' => now(),
                'resolution_note' => trim((string) $note) !== ''
                    ? trim((string) $note)
                    : __('admin_imports.notes.deleted_place_kept'),
                'updated_at' => now(),
            ]);

            $this->audit(
                $actor,
                null,
                'external_deleted_place_review_ignored',
                [
                    'external_record_id' => (int) $record->id,
                    'external_id' => $record->external_id,
                    'review_id' => $reviewId,
                ],
            );
        });
    }

    public function deferAllPending(User $actor): array
    {
        $reviewIds = DB::table('external_import_review_items')
            ->where('status', 'pending')
            ->whereNotIn('type', ['research_update', 'research_conflict', 'external_duplicate_group', 'source_missing', 'possible_reopen', 'nearby_deleted_place', 'deleted_place_changed'])
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $deferred = 0;
        $skipped = [];

        foreach ($reviewIds as $reviewId) {
            try {
                $this->defer($reviewId, $actor, __('admin_imports.notes.deferred_bulk'));
                $deferred++;
            } catch (RuntimeException $e) {
                $skipped[] = [
                    'review_id' => $reviewId,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return [
            'attempted' => count($reviewIds),
            'deferred' => $deferred,
            'skipped' => $skipped,
        ];
    }

    public function deferSelected(array $reviewIds, User $actor): array
    {
        return $this->processSelectedPendingReviews($reviewIds, $actor, 'defer');
    }

    public function ignoreSelected(array $reviewIds, User $actor): array
    {
        return $this->processSelectedPendingReviews($reviewIds, $actor, 'ignore');
    }

    public function ignoreAllPending(User $actor): array
    {
        $reviewIds = DB::table('external_import_review_items')
            ->where('status', 'pending')
            ->whereNotIn('type', ['research_update', 'research_conflict', 'external_duplicate_group', 'source_missing', 'possible_reopen', 'nearby_deleted_place', 'deleted_place_changed'])
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $ignored = 0;
        $skipped = [];

        foreach ($reviewIds as $reviewId) {
            try {
                $this->ignore($reviewId, $actor, __('admin_imports.notes.ignored_bulk'));
                $ignored++;
            } catch (RuntimeException $e) {
                $skipped[] = [
                    'review_id' => $reviewId,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return [
            'attempted' => count($reviewIds),
            'ignored' => $ignored,
            'skipped' => $skipped,
        ];
    }

    public function ignore(int $reviewId, User $actor, ?string $note = null): void
    {
        DB::transaction(function () use ($reviewId, $actor, $note): void {
            [, $record] = $this->pendingReview($reviewId);

            if ($record->place_id) {
                throw new RuntimeException(__('admin_imports.errors.linked_ignore'));
            }

            DB::table('external_records')->where('id', $record->id)->update([
                'classification' => 'ignored',
                'classified_at' => now(),
                'updated_at' => now(),
            ]);

            $this->resolveRecordReviews(
                (int) $record->id,
                (int) $actor->id,
                'resolved',
                trim((string) $note) !== '' ? trim((string) $note) : __('admin_imports.notes.ignored_admin'),
            );

            $this->audit(
                $actor,
                null,
                'external_record_ignored',
                [
                    'external_record_id' => (int) $record->id,
                    'external_id' => $record->external_id,
                ],
            );
        });
    }

    public function updateMissingCoordinates(
        int $reviewId,
        float $latitude,
        float $longitude,
        User $actor,
    ): array {
        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            throw new RuntimeException(__('admin_imports.errors.coordinates_range'));
        }

        return DB::transaction(function () use ($reviewId, $latitude, $longitude, $actor): array {
            [$review, $record] = $this->pendingReview($reviewId);

            if ($review->type !== 'missing_coordinates') {
                throw new RuntimeException(__('admin_imports.errors.coordinate_review_type'));
            }

            if ($record->place_id) {
                throw new RuntimeException(__('admin_imports.errors.already_linked'));
            }

            $mapped = json_decode((string) $record->normalized_data, true);
            if (! is_array($mapped)) {
                throw new RuntimeException(__('admin_imports.errors.invalid_normalized'));
            }

            $mapped['place'] = is_array($mapped['place'] ?? null) ? $mapped['place'] : [];
            $mapped['place']['latitude'] = $latitude;
            $mapped['place']['longitude'] = $longitude;
            $mapped['place']['coordinate_source'] = 'manual-review';

            $overrides = json_decode((string) ($record->manual_overrides ?? ''), true);
            $overrides = is_array($overrides) ? $overrides : [];
            $overrides['place.latitude'] = $latitude;
            $overrides['place.longitude'] = $longitude;

            $normalizedJson = $this->canonicalJson($mapped);
            $now = now();

            DB::table('external_records')->where('id', $record->id)->update([
                'normalized_data' => $normalizedJson,
                'normalized_hash' => hash('sha256', $normalizedJson),
                'manual_overrides' => json_encode($overrides, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => $now,
            ]);

            foreach ([
                'latitude' => $latitude,
                'longitude' => $longitude,
                'coordinate_source' => 'manual-review',
            ] as $field => $value) {
                $json = $this->canonicalJson($value);

                DB::table('external_record_fields')->updateOrInsert(
                    [
                        'external_record_id' => $record->id,
                        'field_key' => $field,
                    ],
                    [
                        'value' => $json,
                        'value_hash' => hash('sha256', $json),
                        'last_seen_at' => $now,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ],
                );
            }

            DB::table('external_import_review_items')->where('id', $reviewId)->update([
                'status' => 'superseded',
                'resolved_by' => $actor->id,
                'resolved_at' => $now,
                'resolution_note' => __('admin_imports.notes.coordinates_added'),
                'updated_at' => $now,
            ]);

            $matches = app(Datex2ParkingCandidateService::class)->candidates($mapped);

            if ($matches !== []) {
                DB::table('external_records')->where('id', $record->id)->update([
                    'classification' => 'possible_duplicate',
                    'classified_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('external_import_review_items')->insert([
                    'external_import_run_id' => $review->external_import_run_id,
                    'external_source_id' => $review->external_source_id,
                    'external_record_id' => $record->id,
                    'place_id' => (int) $matches[0]['place_id'],
                    'type' => 'possible_duplicate',
                    'severity' => 'warning',
                    'status' => 'pending',
                    'details' => json_encode([
                        'external_id' => $record->external_id,
                        'external_name' => $mapped['place']['name'] ?? null,
                        'candidates' => $matches,
                        'coordinates_added_manually' => true,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $classification = 'possible_duplicate';
            } else {
                DB::table('external_records')->where('id', $record->id)->update([
                    'classification' => 'new_candidate',
                    'classified_at' => $now,
                    'updated_at' => $now,
                ]);

                $classification = 'new_candidate';
            }

            $this->audit(
                $actor,
                null,
                'external_missing_coordinates_completed',
                [
                    'external_record_id' => (int) $record->id,
                    'external_id' => $record->external_id,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'classification' => $classification,
                ],
            );

            return [
                'classification' => $classification,
                'matches' => $matches,
            ];
        });
    }

    public function reopenPossibleReopen(int $reviewId, User $actor): int
    {
        return DB::transaction(function () use ($reviewId, $actor): int {
            $review = DB::table('external_import_review_items')
                ->where('id', $reviewId)
                ->where('status', 'pending')
                ->where('type', 'possible_reopen')
                ->lockForUpdate()
                ->first();

            if (! $review || ! $review->place_id || ! $review->external_record_id) {
                throw new RuntimeException(__('admin_imports.errors.reopen_closed'));
            }

            $record = DB::table('external_records')
                ->where('id', $review->external_record_id)
                ->where('status', 'active')
                ->first();

            $mapped = $record ? json_decode((string) $record->normalized_data, true) : null;
            if (! $record || ! is_array($mapped) || ($mapped['place']['opening_status'] ?? null) !== 'open') {
                throw new RuntimeException(__('admin_imports.errors.source_not_open'));
            }

            $place = DB::table('places')
                ->where('id', $review->place_id)
                ->lockForUpdate()
                ->first(['id', 'opening_status']);

            if (! $place) {
                throw new RuntimeException(__('admin_imports.errors.linked_place_missing'));
            }

            if (! in_array($place->opening_status, ['temporarily_closed', 'seasonally_closed', 'permanently_closed'], true)) {
                throw new RuntimeException(__('admin_imports.errors.place_not_closed'));
            }

            $before = (string) $place->opening_status;
            $now = now();

            DB::table('places')->where('id', $place->id)->update([
                'opening_status' => 'open',
                'updated_at' => $now,
            ]);

            DB::table('external_import_review_items')->where('id', $reviewId)->update([
                'status' => 'resolved',
                'resolved_by' => $actor->id,
                'resolved_at' => $now,
                'resolution_note' => __('admin_imports.notes.reopened'),
                'updated_at' => $now,
            ]);

            app(PlaceHistoryService::class)->addUser(
                (int) $place->id,
                $actor,
                'external_possible_reopen_confirmed',
                __('place_profile.history.actions.external_possible_reopen_confirmed'),
                [
                    'field' => 'opening_status',
                    'before' => $before,
                    'after' => 'open',
                    'external_source_id' => (int) $review->external_source_id,
                    'external_record_id' => (int) $review->external_record_id,
                ],
            );

            $this->audit(
                $actor,
                (int) $place->id,
                'external_possible_reopen_confirmed',
                [
                    'review_id' => $reviewId,
                    'external_record_id' => (int) $review->external_record_id,
                    'external_source_id' => (int) $review->external_source_id,
                    'before' => $before,
                    'after' => 'open',
                ],
            );

            return (int) $place->id;
        });
    }

    public function resolvePossibleReopen(int $reviewId, User $actor, ?string $note = null): void
    {
        DB::transaction(function () use ($reviewId, $actor, $note): void {
            $review = DB::table('external_import_review_items')
                ->where('id', $reviewId)
                ->where('status', 'pending')
                ->where('type', 'possible_reopen')
                ->lockForUpdate()
                ->first();

            if (! $review) {
                throw new RuntimeException(__('admin_imports.errors.reopen_closed'));
            }

            DB::table('external_import_review_items')->where('id', $reviewId)->update([
                'status' => 'resolved',
                'resolved_by' => $actor->id,
                'resolved_at' => now(),
                'resolution_note' => trim((string) $note) !== ''
                    ? trim((string) $note)
                    : __('admin_imports.notes.reopen_checked'),
                'updated_at' => now(),
            ]);

            $this->audit(
                $actor,
                $review->place_id ? (int) $review->place_id : null,
                'external_possible_reopen_review_resolved',
                [
                    'review_id' => $reviewId,
                    'external_record_id' => $review->external_record_id ? (int) $review->external_record_id : null,
                    'external_source_id' => (int) $review->external_source_id,
                ],
            );
        });
    }

    public function resolveSourceMissing(int $reviewId, User $actor, ?string $note = null): void
    {
        DB::transaction(function () use ($reviewId, $actor, $note): void {
            $review = DB::table('external_import_review_items')
                ->where('id', $reviewId)
                ->where('status', 'pending')
                ->where('type', 'source_missing')
                ->lockForUpdate()
                ->first();

            if (! $review) {
                throw new RuntimeException(__('admin_imports.errors.missing_closed'));
            }

            DB::table('external_import_review_items')->where('id', $reviewId)->update([
                'status' => 'resolved',
                'resolved_by' => $actor->id,
                'resolved_at' => now(),
                'resolution_note' => trim((string) $note) !== ''
                    ? trim((string) $note)
                    : __('admin_imports.notes.checked'),
                'updated_at' => now(),
            ]);

            $this->audit(
                $actor,
                $review->place_id ? (int) $review->place_id : null,
                'external_source_missing_review_resolved',
                [
                    'review_id' => $reviewId,
                    'external_record_id' => $review->external_record_id ? (int) $review->external_record_id : null,
                    'external_source_id' => (int) $review->external_source_id,
                ],
            );
        });
    }

    public function defer(int $reviewId, User $actor, ?string $note = null): void
    {
        DB::transaction(function () use ($reviewId, $actor, $note): void {
            [$review, $record] = $this->pendingReview($reviewId);

            $details = json_decode((string) $review->details, true) ?: [];
            $details['deferred'] = [
                'by' => (int) $actor->id,
                'at' => now()->toIso8601String(),
                'note' => trim((string) $note) ?: null,
            ];

            DB::table('external_import_review_items')->where('id', $reviewId)->update([
                'details' => json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => now(),
            ]);

            $this->audit(
                $actor,
                null,
                'external_review_deferred',
                [
                    'external_record_id' => (int) $record->id,
                    'external_id' => $record->external_id,
                    'review_id' => $reviewId,
                ],
            );
        });
    }

    private function processSelectedPendingReviews(array $reviewIds, User $actor, string $action): array
    {
        $reviewIds = array_values(array_unique(array_map('intval', $reviewIds)));
        $reviewIds = array_values(array_filter($reviewIds, fn (int $id) => $id > 0));

        if ($reviewIds === [] || count($reviewIds) > 200) {
            throw new RuntimeException(__('admin_imports.errors.review_selection'));
        }

        $allowedIds = DB::table('external_import_review_items')
            ->whereIn('id', $reviewIds)
            ->where('status', 'pending')
            ->whereNotIn('type', ['research_update', 'research_conflict', 'external_duplicate_group', 'source_missing', 'possible_reopen', 'nearby_deleted_place', 'deleted_place_changed'])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $processed = 0;
        $skipped = array_values(array_diff($reviewIds, $allowedIds));

        foreach ($allowedIds as $reviewId) {
            try {
                if ($action === 'ignore') {
                    $this->ignore($reviewId, $actor, __('admin_imports.notes.ignored_selected'));
                } else {
                    $this->defer($reviewId, $actor, __('admin_imports.notes.deferred_selected'));
                }
                $processed++;
            } catch (RuntimeException) {
                $skipped[] = $reviewId;
            }
        }

        return [
            'attempted' => count($reviewIds),
            'processed' => $processed,
            'skipped' => array_values(array_unique($skipped)),
        ];
    }

    private function validatedDuplicateGroupReviewIds(array $reviewIds): array
    {
        $reviewIds = array_values(array_unique(array_map('intval', $reviewIds)));

        if (count($reviewIds) < 1 || count($reviewIds) > 100) {
            throw new RuntimeException(__('admin_imports.errors.duplicate_group_size'));
        }

        $reviews = DB::table('external_import_review_items')
            ->whereIn('id', $reviewIds)
            ->where('status', 'pending')
            ->where('type', 'external_duplicate_group')
            ->get(['id', 'external_record_id', 'details']);

        if ($reviews->count() !== count($reviewIds)) {
            throw new RuntimeException(__('admin_imports.errors.duplicate_review_closed'));
        }

        $groupKeys = $reviews
            ->map(function ($review) {
                $details = json_decode((string) $review->details, true) ?: [];

                return trim((string) ($details['group_key'] ?? ''));
            })
            ->filter()
            ->unique();

        if ($groupKeys->count() !== 1) {
            throw new RuntimeException(__('admin_imports.errors.duplicate_group_mismatch'));
        }

        return $reviewIds;
    }

    private function pendingReview(int $reviewId): array
    {
        $review = DB::table('external_import_review_items')
            ->where('id', $reviewId)
            ->lockForUpdate()
            ->first();

        if (! $review || $review->status !== 'pending' || ! $review->external_record_id) {
            throw new RuntimeException(__('admin_imports.errors.review_closed'));
        }

        $record = DB::table('external_records')
            ->where('id', $review->external_record_id)
            ->lockForUpdate()
            ->first();

        if (! $record || $record->status !== 'active') {
            throw new RuntimeException(__('admin_imports.errors.record_inactive'));
        }

        return [$review, $record];
    }

    private function resolveRecordReviews(int $recordId, int $userId, string $status, string $note): void
    {
        DB::table('external_import_review_items')
            ->where('external_record_id', $recordId)
            ->where('status', 'pending')
            ->update([
                'status' => $status,
                'resolved_by' => $userId,
                'resolved_at' => now(),
                'resolution_note' => $note,
                'updated_at' => now(),
            ]);
    }

    private function createAddress(int $placeId, array $address, $now): void
    {
        $values = [
            'country_code' => $this->text($address['country_code'] ?? null),
            'region_id' => null,
            'postal_code' => $this->text($address['postal_code'] ?? null),
            'city' => $this->text($address['city'] ?? null),
            'street' => $this->text($address['street'] ?? null),
            'house_number' => $this->text($address['house_number'] ?? null),
            'address_addition' => $this->text($address['address_addition'] ?? null),
        ];

        if (! collect($values)->contains(fn ($value) => $value !== null)) {
            return;
        }

        DB::table('place_addresses')->insert($values + [
            'place_id' => $placeId,
            'is_active' => true,
            'version_valid_from' => $now,
            'version_valid_until' => null,
            'internal_comment' => 'Initial aus externer Quelle übernommen.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function vehicleTypeCapacities(array $mapped): array
    {
        $alreadyMapped = $mapped['vehicle_type_capacities'] ?? null;
        if (is_array($alreadyMapped) && $alreadyMapped !== []) {
            return collect($alreadyMapped)
                ->filter(fn ($capacity, $slug) => is_string($slug) && is_int($capacity) && $capacity > 0)
                ->all();
        }

        $sourceMap = [
            'car' => 'car',
            'carWithTrailer' => 'car-with-trailer',
            'lorry' => 'truck',
            'bus' => 'coach',
        ];

        $sourceCapacities = is_array($mapped['vehicle_capacities'] ?? null)
            ? $mapped['vehicle_capacities']
            : [];

        $result = [];
        foreach ($sourceCapacities as $sourceType => $capacity) {
            $vehicleSlug = $sourceMap[$sourceType] ?? null;
            if (! $vehicleSlug || ! is_int($capacity) || $capacity <= 0) {
                continue;
            }

            $result[$vehicleSlug] = $capacity;
        }

        return $result;
    }

    private function createDetails(int $placeId, array $placeData, array $vehicleCapacities, int $actorId, $now): void
    {
        $operator = $placeData['operator']['name'] ?? null;
        $directPitchCount = $placeData['parking_spaces_total'] ?? null;
        $directPitchCount = is_int($directPitchCount) && $directPitchCount > 0 ? $directPitchCount : null;

        $derivedPitchCount = collect($vehicleCapacities)
            ->filter(fn ($value) => is_int($value) && $value > 0)
            ->sum();
        $derivedPitchCount = $derivedPitchCount > 0 ? (int) $derivedPitchCount : null;

        $pitchCount = $directPitchCount ?? $derivedPitchCount;
        $pitchCountSource = $directPitchCount !== null
            ? 'direct'
            : ($derivedPitchCount !== null ? 'summed_vehicle_capacities' : null);

        $minimumStayNights = isset($placeData['minimum_stay_nights']) && is_numeric($placeData['minimum_stay_nights'])
            ? max(1, (int) $placeData['minimum_stay_nights'])
            : null;
        $pitchAreaMinM2 = isset($placeData['pitch_area_min_m2']) && is_numeric($placeData['pitch_area_min_m2'])
            ? max(0.01, (float) $placeData['pitch_area_min_m2'])
            : null;

        if ($this->text($operator) === null && $pitchCount === null && $minimumStayNights === null && $pitchAreaMinM2 === null) {
            return;
        }

        DB::table('place_details')->insert([
            'place_id' => $placeId,
            'operator_name' => $this->text($operator),
            'pitch_count' => $pitchCount,
            'pitch_count_source' => $pitchCountSource,
            'minimum_stay_nights' => $minimumStayNights,
            'pitch_area_min_m2' => $pitchAreaMinM2,
            'is_active' => true,
            'version_valid_from' => $now,
            'version_valid_until' => null,
            'internal_comment' => 'Initial aus externer Quelle übernommen.',
            'created_by' => $actorId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function createVehicleTypes(int $placeId, array $capacities, $now): void
    {
        $capacities = collect($capacities)
            ->filter(fn ($capacity, $slug) => is_string($slug) && is_int($capacity) && $capacity > 0);

        if ($capacities->isEmpty()) {
            return;
        }

        $vehicleIds = DB::table('vehicle_types')
            ->whereIn('slug', $capacities->keys())
            ->where('is_active', true)
            ->pluck('id', 'slug');

        foreach ($capacities as $slug => $capacity) {
            $vehicleTypeId = $vehicleIds[$slug] ?? null;
            if (! $vehicleTypeId) {
                continue;
            }

            DB::table('place_vehicle_types')->insertOrIgnore([
                'place_id' => $placeId,
                'vehicle_type_id' => $vehicleTypeId,
                'capacity' => $capacity,
                'is_active' => true,
                'version_valid_from' => $now,
                'version_valid_until' => null,
                'internal_comment' => 'Initial aus externer Quelle übernommen.',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function createDescription(int $placeId, array $placeData, $now): void
    {
        $description = $this->text($placeData['description'] ?? null);
        if ($description === null) {
            return;
        }

        DB::table('place_translations')->insert([
            'place_id' => $placeId,
            'locale' => 'de',
            'description' => $description,
            'directions' => null,
            'access_information' => null,
            'is_active' => true,
            'version_valid_from' => $now,
            'version_valid_until' => null,
            'internal_comment' => 'Initial aus externer Quelle übernommen.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function createContacts(int $placeId, array $operator, int $actorId, $now): void
    {
        $contacts = [
            'telephone' => $this->text($operator['phone'] ?? null),
            'email' => $this->text($operator['email'] ?? null),
            'website' => $this->text($operator['url'] ?? null),
        ];

        $sort = 10;
        foreach ($contacts as $type => $value) {
            if ($value === null) {
                continue;
            }

            DB::table('place_contacts')->insert([
                'place_id' => $placeId,
                'contact_type' => $type,
                'value' => $value,
                'sort_order' => $sort,
                'is_active' => true,
                'version_valid_from' => $now,
                'version_valid_until' => null,
                'internal_comment' => 'Initial aus externer Quelle übernommen.',
                'created_by' => $actorId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $sort += 10;
        }
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'place';
        $slug = $base;
        $suffix = 2;

        while (DB::table('places')->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    private function text(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function canonicalJson(mixed $value): string
    {
        return json_encode(
            $this->sortRecursive($value),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION,
        ) ?: 'null';
    }

    private function sortRecursive(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(fn ($item) => $this->sortRecursive($item), $value);
        }

        ksort($value);

        foreach ($value as $key => $item) {
            $value[$key] = $this->sortRecursive($item);
        }

        return $value;
    }

    private function audit(User $actor, ?int $placeId, string $action, array $values): void
    {
        DB::table('audit_logs')->insert([
            'user_id' => $actor->id,
            'entity_type' => $placeId ? 'place' : 'external_record',
            'entity_id' => $placeId ?? ($values['external_record_id'] ?? null),
            'action' => $action,
            'source' => 'admin',
            'old_values' => null,
            'new_values' => json_encode($values, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'internal_comment' => null,
            'created_at' => now(),
        ]);
    }
}
