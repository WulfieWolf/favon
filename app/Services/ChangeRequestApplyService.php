<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ChangeRequestApplyService
{
    private const VERSIONED_TABLES = [
        'place_translations' => [
            'place_key' => 'place_id',
            'valid_from' => 'version_valid_from',
            'valid_until' => 'version_valid_until',
            'audit_type' => 'place_translation',
        ],
        'place_contacts' => [
            'place_key' => 'place_id',
            'valid_from' => 'version_valid_from',
            'valid_until' => 'version_valid_until',
            'audit_type' => 'place_contact',
        ],
        'place_details' => [
            'place_key' => 'place_id',
            'valid_from' => 'version_valid_from',
            'valid_until' => 'version_valid_until',
            'audit_type' => 'place_detail',
        ],
    ];

    public function __construct(
        private PermissionService $permissions,
        private XpService $xpService,
        private BadgeService $badgeService,
    ) {
    }

    public function approveAndApply(User $reviewer, int $changeRequestId, ?string $comment = null): void
    {
        if (! $this->permissions->can($reviewer, 'places.approve_changes')) {
            throw new RuntimeException('Missing permission places.approve_changes.');
        }

        DB::transaction(function () use ($reviewer, $changeRequestId, $comment): void {
            $anchor = DB::table('change_requests')
                ->where('id', $changeRequestId)
                ->lockForUpdate()
                ->first();

            if (! $anchor) {
                throw new RuntimeException('Change request not found.');
            }

            $query = DB::table('change_requests as cr')
                ->join('suggestable_fields as sf', 'sf.id', '=', 'cr.suggestable_field_id')
                ->where('cr.place_id', $anchor->place_id);

            if ($anchor->group_uuid) {
                $query->where('cr.group_uuid', $anchor->group_uuid);
            } else {
                $query->where('cr.id', $anchor->id);
            }

            $requests = $query
                ->orderBy('cr.id')
                ->lockForUpdate()
                ->get([
                    'cr.*',
                    'sf.target_table',
                    'sf.target_field',
                    'sf.is_suggestable',
                    'sf.allow_create',
                    'sf.allow_update',
                    'sf.allow_deactivate',
                    'sf.is_active as suggestable_field_active',
                ]);

            if ($requests->isEmpty()) {
                throw new RuntimeException('No change requests found for this review group.');
            }

            if ($requests->contains(fn ($request) => $request->status !== 'pending')) {
                throw new RuntimeException('At least one request in this group has already been reviewed.');
            }

            foreach ($requests as $request) {
                $this->validateRequestDefinition($request);
            }

            $resultIds = [];
            $now = now();
            $placeName = (string) DB::table('places')->where('id', $anchor->place_id)->value('name');

            foreach ($requests->groupBy(function ($request) {
                $recordKey = $request->target_record_id ?? 'new';


                // New contacts submitted by the place-info editor use one
                // composite request per contact so several contacts can be
                // created in the same overall change-request group.
                if ($request->target_table === 'place_contacts' && $request->operation === 'create') {
                    $proposed = $this->decodeJsonValue($request->proposed_value);
                    if (
                        is_array($proposed)
                        && array_key_exists('contact_type', $proposed)
                        && array_key_exists('value', $proposed)
                    ) {
                        $recordKey = 'new-'.$request->id;
                    }
                }

                return $request->target_table.'|'.$recordKey.'|'.$request->operation;
            }) as $group) {
                $table = $group->first()->target_table;

                $groupResults = match ($table) {
                    'places' => $this->applyPlaceUpdates($group, $anchor->place_id, $reviewer, $now),
                    'place_addresses' => $this->applyVersionedPlaceAddressUpdates($group, $anchor->place_id, $reviewer, $now),
                    'place_translations',
                    'place_contacts',
                    'place_details' => $this->applyStandardVersionedRecord($group, $anchor->place_id, $reviewer, $now),
                    'place_features' => $this->applyPlaceFeatureRecord($group, $anchor->place_id, $reviewer, $now),
                    default => throw new RuntimeException("Applying changes to {$table} is not implemented yet."),
                };

                $resultIds += $groupResults;
            }

            $submitterId = (int) ($requests->first()->submitted_by ?? 0);
            $submitter = $submitterId > 0 ? User::find($submitterId) : null;
            if ($submitter) {
                $changes = app(PlaceHistoryPresenter::class)->changesFromRequests($requests);
                $isPublication = $requests->contains(fn ($request) => $request->target_table === 'places' && $request->target_field === 'publication_status' && $this->decodeJsonValue($request->proposed_value) === 'published');

                app(PlaceHistoryService::class)->addUser(
                    (int) $anchor->place_id,
                    $submitter,
                    $isPublication ? 'place_created' : 'place_data_changed',
                    $isPublication ? __('place_profile.history.actions.place_created') : __('place_profile.history.actions.place_data_changed'),
                    [
                        'submitted_at' => $requests->min('submitted_at'),
                        'approved_at' => $now->toIso8601String(),
                        'changes' => $changes,
                        'change_request_ids' => $requests->pluck('id')->map(fn ($id) => (int) $id)->all(),
                        'reviewed_by' => (int) $reviewer->id,
                    ],
                );
            }

            foreach ($requests as $request) {
                $resultRecordId = $resultIds[$request->id] ?? null;
                if (! $resultRecordId) {
                    throw new RuntimeException("No result record was produced for change request {$request->id}.");
                }

                DB::table('change_requests')
                    ->where('id', $request->id)
                    ->update([
                        'status' => 'approved',
                        'reviewed_by' => $reviewer->id,
                        'reviewed_at' => $now,
                        'moderator_comment' => $comment,
                        'result_record_id' => $resultRecordId,
                        'applied_at' => $now,
                        'updated_at' => $now,
                    ]);

                DB::table('audit_logs')->insert([
                    'user_id' => $reviewer->id,
                    'entity_type' => 'change_request',
                    'entity_id' => $request->id,
                    'action' => 'change_request_approved_applied',
                    'source' => 'admin',
                    'old_values' => $this->json([
                        'status' => $request->status,
                        'result_record_id' => $request->result_record_id,
                        'applied_at' => $request->applied_at,
                    ]),
                    'new_values' => $this->json([
                        'status' => 'approved',
                        'reviewed_by' => $reviewer->id,
                        'reviewed_at' => $now->toIso8601String(),
                        'moderator_comment' => $comment,
                        'result_record_id' => $resultRecordId,
                        'applied_at' => $now->toIso8601String(),
                        'group_uuid' => $request->group_uuid,
                    ]),
                    'internal_comment' => null,
                    'created_at' => $now,
                ]);

                $this->xpService->awardApprovedPlaceChange($request, (int) $resultRecordId, $placeName);
                $this->badgeService->recordApprovedPlaceChange($request, (int) $resultRecordId, $placeName);
            }
        });
    }

    private function validateRequestDefinition(object $request): void
    {
        if (! $request->suggestable_field_active || ! $request->is_suggestable) {
            throw new RuntimeException("Suggestable field {$request->target_table}.{$request->target_field} is inactive.");
        }

        if (! in_array($request->operation, ['create', 'update', 'deactivate'], true)) {
            throw new RuntimeException("Unsupported change operation: {$request->operation}.");
        }

        $allowed = match ($request->operation) {
            'create' => (bool) $request->allow_create,
            'update' => (bool) $request->allow_update,
            'deactivate' => (bool) $request->allow_deactivate,
        };

        if (! $allowed) {
            throw new RuntimeException("Operation {$request->operation} is not allowed for {$request->target_table}.{$request->target_field}.");
        }

        if ($request->operation === 'update' && ! $request->target_record_id) {
            throw new RuntimeException('Update requests require a target_record_id.');
        }

        if ($request->operation === 'deactivate' && ! $request->target_record_id) {
            throw new RuntimeException('Deactivate requests require a target_record_id.');
        }

        if ($request->operation === 'create' && $request->target_record_id) {
            throw new RuntimeException('Create requests must not have a target_record_id.');
        }
    }

    private function applyPlaceUpdates(Collection $requests, int $placeId, User $reviewer, $now): array
    {
        if ($requests->contains(fn ($request) => $request->operation !== 'update')) {
            throw new RuntimeException('Core place fields currently support update operations only.');
        }

        $targetIds = $requests->pluck('target_record_id')->unique();
        if ($targetIds->count() !== 1 || (int) $targetIds->first() !== $placeId) {
            throw new RuntimeException('Core place change target does not match the request place.');
        }

        $place = DB::table('places')->where('id', $placeId)->lockForUpdate()->first();
        if (! $place) {
            throw new RuntimeException('Target place no longer exists.');
        }

        $updates = [];
        $oldValues = [];
        foreach ($requests as $request) {
            if (! property_exists($place, $request->target_field)) {
                throw new RuntimeException("Field places.{$request->target_field} does not exist.");
            }

            $current = $place->{$request->target_field};
            $original = $this->decodeJsonValue($request->original_value);
            if (! $this->valuesEquivalent($current, $original)) {
                throw new RuntimeException("Stale change request: places.{$request->target_field} changed after submission.");
            }

            $oldValues[$request->target_field] = $current;
            $updates[$request->target_field] = $this->decodeJsonValue($request->proposed_value);
        }

        $updates['updated_at'] = $now;
        DB::table('places')->where('id', $placeId)->update($updates);

        $this->auditDomainChange($reviewer, 'place', $placeId, 'change_request_applied', $oldValues, array_diff_key($updates, ['updated_at' => true]), $requests, $now);

        return $requests->mapWithKeys(fn ($request) => [$request->id => $placeId])->all();
    }

    private function applyVersionedPlaceAddressUpdates(Collection $requests, int $placeId, User $reviewer, $now): array
    {
        $operations = $requests->pluck('operation')->unique();
        if ($operations->count() !== 1) {
            throw new RuntimeException('An address change group cannot mix operations.');
        }

        if ($operations->first() === 'create') {
            $values = [
                'place_id' => $placeId,
                'country_code' => null,
                'region_id' => null,
                'postal_code' => null,
                'city' => null,
                'street' => null,
                'house_number' => null,
                'address_addition' => null,
                'is_active' => true,
                'version_valid_from' => $now,
                'version_valid_until' => null,
                'internal_comment' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $changed = [];
            foreach ($requests as $request) {
                $proposed = $this->decodeJsonValue($request->proposed_value);
                $values[$request->target_field] = $proposed;
                $changed[$request->target_field] = $proposed;
            }

            $newId = DB::table('place_addresses')->insertGetId($values);

            $this->auditDomainChange(
                $reviewer,
                'place_address',
                $newId,
                'change_request_applied_create',
                null,
                ['record_id' => $newId, 'values' => $changed],
                $requests,
                $now,
            );

            return $requests->mapWithKeys(fn ($request) => [$request->id => $newId])->all();
        }

        if ($operations->first() !== 'update') {
            throw new RuntimeException('Unsupported address change operation.');
        }

        $targetIds = $requests->pluck('target_record_id')->unique();
        if ($targetIds->count() !== 1) {
            throw new RuntimeException('Grouped address changes must target exactly one address version.');
        }

        $targetId = (int) $targetIds->first();
        $address = DB::table('place_addresses')->where('id', $targetId)->lockForUpdate()->first();

        if (! $address || (int) $address->place_id !== $placeId) {
            throw new RuntimeException('Target address does not belong to the request place.');
        }

        if (! $address->is_active || $address->version_valid_until !== null) {
            throw new RuntimeException('Target address is no longer the current active version.');
        }

        $newValues = (array) $address;
        unset($newValues['id']);

        [$oldChangedValues, $newChangedValues] = $this->applyFieldsToClone($requests, $address, $newValues, 'place_addresses');

        DB::table('place_addresses')->where('id', $targetId)->update([
            'is_active' => false,
            'version_valid_until' => $now,
            'updated_at' => $now,
        ]);

        $newValues['is_active'] = true;
        $newValues['version_valid_from'] = $now;
        $newValues['version_valid_until'] = null;
        $newValues['created_at'] = $now;
        $newValues['updated_at'] = $now;

        $newId = DB::table('place_addresses')->insertGetId($newValues);

        $this->auditDomainChange(
            $reviewer,
            'place_address',
            $newId,
            'change_request_applied_version',
            ['record_id' => $targetId, 'values' => $oldChangedValues],
            ['record_id' => $newId, 'values' => $newChangedValues],
            $requests,
            $now,
        );

        return $requests->mapWithKeys(fn ($request) => [$request->id => $newId])->all();
    }

    private function applyStandardVersionedRecord(Collection $requests, int $placeId, User $reviewer, $now): array
    {
        $table = $requests->first()->target_table;
        $config = self::VERSIONED_TABLES[$table] ?? null;
        if (! $config) {
            throw new RuntimeException("No versioning configuration exists for {$table}.");
        }

        $operations = $requests->pluck('operation')->unique();
        if ($operations->count() !== 1) {
            throw new RuntimeException("A {$table} record group cannot mix operations.");
        }

        $operation = $operations->first();

        if ($operation === 'create') {
            return $this->createStandardVersionedRecord($requests, $placeId, $reviewer, $now, $config);
        }

        $targetIds = $requests->pluck('target_record_id')->unique();
        if ($targetIds->count() !== 1) {
            throw new RuntimeException("Grouped {$table} changes must target exactly one record version.");
        }

        $targetId = (int) $targetIds->first();
        $record = DB::table($table)->where('id', $targetId)->lockForUpdate()->first();

        if (! $record || (int) $record->{$config['place_key']} !== $placeId) {
            throw new RuntimeException("Target {$table} record does not belong to the request place.");
        }

        if (! $record->is_active || $record->{$config['valid_until']} !== null) {
            throw new RuntimeException("Target {$table} record is no longer the current active version.");
        }

        foreach ($requests as $request) {
            $this->assertCurrentValue($record, $request, $table);
        }

        if ($operation === 'deactivate') {
            DB::table($table)->where('id', $targetId)->update([
                'is_active' => false,
                $config['valid_until'] => $now,
                'updated_at' => $now,
            ]);

            $this->auditDomainChange(
                $reviewer,
                $config['audit_type'],
                $targetId,
                'change_request_applied_deactivate',
                ['record_id' => $targetId, 'is_active' => true],
                ['record_id' => $targetId, 'is_active' => false],
                $requests,
                $now,
            );

            return $requests->mapWithKeys(fn ($request) => [$request->id => $targetId])->all();
        }

        $newValues = (array) $record;
        unset($newValues['id']);
        [$oldChangedValues, $newChangedValues] = $this->applyFieldsToClone($requests, $record, $newValues, $table);

        DB::table($table)->where('id', $targetId)->update([
            'is_active' => false,
            $config['valid_until'] => $now,
            'updated_at' => $now,
        ]);

        $newValues['is_active'] = true;
        $newValues[$config['valid_from']] = $now;
        $newValues[$config['valid_until']] = null;
        $newValues['created_at'] = $now;
        $newValues['updated_at'] = $now;
        $newId = DB::table($table)->insertGetId($newValues);

        $this->copyVersionChildren($table, $targetId, $newId, $now);

        $this->auditDomainChange(
            $reviewer,
            $config['audit_type'],
            $newId,
            'change_request_applied_version',
            ['record_id' => $targetId, 'values' => $oldChangedValues],
            ['record_id' => $newId, 'values' => $newChangedValues],
            $requests,
            $now,
        );

        return $requests->mapWithKeys(fn ($request) => [$request->id => $newId])->all();
    }

    private function createStandardVersionedRecord(Collection $requests, int $placeId, User $reviewer, $now, array $config): array
    {
        $table = $requests->first()->target_table;

        if ($table === 'place_translations') {
            $values = [
                $config['place_key'] => $placeId,
                'locale' => null,
                'description' => null,
                'directions' => null,
                'access_information' => null,
                'is_active' => true,
                $config['valid_from'] => $now,
                $config['valid_until'] => null,
                'internal_comment' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $changed = [];
            foreach ($requests as $request) {
                $payload = $this->decodeJsonValue($request->proposed_value);
                if (! is_array($payload) || ! isset($payload['locale'])) {
                    throw new RuntimeException('Creating a place translation requires locale metadata.');
                }

                if ($values['locale'] !== null && $values['locale'] !== $payload['locale']) {
                    throw new RuntimeException('A translation create group cannot mix locales.');
                }

                $values['locale'] = $payload['locale'];
                $values[$request->target_field] = $payload['value'] ?? null;
                $changed[$request->target_field] = $payload['value'] ?? null;
            }

            if (! $values['locale']) {
                throw new RuntimeException('Creating a place translation requires a locale.');
            }

            $newId = DB::table('place_translations')->insertGetId($values);

            $this->auditDomainChange(
                $reviewer,
                $config['audit_type'],
                $newId,
                'change_request_applied_create',
                null,
                ['record_id' => $newId, 'values' => $changed, 'locale' => $values['locale']],
                $requests,
                $now,
            );

            return $requests->mapWithKeys(fn ($request) => [$request->id => $newId])->all();
        }

        $values = [
            $config['place_key'] => $placeId,
            'is_active' => true,
            $config['valid_from'] => $now,
            $config['valid_until'] => null,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        if ($table === 'place_contacts') {
            $values += [
                'sort_order' => 10,
                'internal_comment' => null,
                'created_by' => $requests->first()->submitted_by,
            ];
        } elseif ($table === 'place_vehicle_types') {
            $values += [
                'capacity' => null,
                'internal_comment' => null,
            ];
        } elseif ($table === 'place_details') {
            $values += [
                'operator_name' => null,
                'pitch_count' => null,
                'internal_comment' => null,
                'created_by' => $requests->first()->submitted_by,
            ];
        } elseif ($table === 'opening_hours') {
            $values += [
                'feature_id' => null,
                'day_type' => 'weekday',
                'weekday' => null,
                'opens_at' => null,
                'closes_at' => null,
                'is_closed' => false,
                'is_24_hours' => false,
                'by_appointment_only' => false,
                'valid_from' => null,
                'valid_until' => null,
                'internal_comment' => 'Applied from approved opening-hours change request.',
                'created_by' => $requests->first()->submitted_by,
            ];
        }

        $changed = [];
        foreach ($requests as $request) {
            $proposed = $this->decodeJsonValue($request->proposed_value);

            if (
                $table === 'place_contacts'
                && $request->target_field === 'contact_type'
                && is_array($proposed)
                && array_key_exists('contact_type', $proposed)
                && array_key_exists('value', $proposed)
            ) {
                $values['contact_type'] = (string) $proposed['contact_type'];
                $values['value'] = (string) $proposed['value'];
                $changed['contact_type'] = $values['contact_type'];
                $changed['value'] = $values['value'];

                continue;
            }

            if (
                $table === 'place_vehicle_types'
                && $request->target_field === 'vehicle_type_id'
                && is_array($proposed)
            ) {
                $values['vehicle_type_id'] = (int) ($proposed['vehicle_type_id'] ?? 0);
                $capacity = $proposed['capacity'] ?? null;
                $values['capacity'] = is_numeric($capacity) && (int) $capacity > 0 ? (int) $capacity : null;
                $changed['vehicle_type_id'] = $values['vehicle_type_id'];
                $changed['capacity'] = $values['capacity'];
                continue;
            }

            $values[$request->target_field] = $proposed;
            $changed[$request->target_field] = $proposed;
        }

        if ($table === 'place_contacts' && (! isset($values['contact_type']) || ! isset($values['value']))) {
            throw new RuntimeException('Creating a contact requires contact_type and value in the same change-request group.');
        }

        if ($table === 'place_vehicle_types' && (! isset($values['vehicle_type_id']) || (int) $values['vehicle_type_id'] <= 0)) {
            throw new RuntimeException('Creating vehicle suitability requires vehicle_type_id.');
        }

        $newId = DB::table($table)->insertGetId($values);

        $this->auditDomainChange(
            $reviewer,
            $config['audit_type'],
            $newId,
            'change_request_applied_create',
            null,
            ['record_id' => $newId, 'values' => $changed],
            $requests,
            $now,
        );

        return $requests->mapWithKeys(fn ($request) => [$request->id => $newId])->all();
    }

    private function applyOpeningHoursGroup(Collection $requests, int $placeId, User $reviewer, $now): array
    {
        if (
            $requests->count() === 1
            && $requests->first()->target_field === 'period_schedule'
            && $requests->first()->operation === 'create'
        ) {
            $request = $requests->first();
            $newPeriodId = $this->openingHoursPeriods->applyProposal($request, (int) $reviewer->id, $now);

            if (! $newPeriodId) {
                throw new RuntimeException('Opening-hours proposal did not create a period.');
            }

            $this->auditDomainChange(
                $reviewer,
                'opening_hour_period',
                $newPeriodId,
                'change_request_applied_period_schedule',
                $this->decodeJsonValue($request->original_value),
                $this->decodeJsonValue($request->proposed_value),
                $requests,
                $now,
            );

            return [$request->id => $newPeriodId];
        }

        return $this->applyStandardVersionedRecord($requests, $placeId, $reviewer, $now);
    }

    private function applyPriceGroup(Collection $requests, int $placeId, User $reviewer, $now): array
    {
        if (
            $requests->count() !== 1
            || $requests->first()->target_field !== 'period_pricing'
            || $requests->first()->operation !== 'create'
        ) {
            throw new RuntimeException('Structured price proposals must use place_price_offers.period_pricing.');
        }

        $request = $requests->first();
        $offerId = $this->pricePeriods->applyProposal($request, (int) $reviewer->id, $now);

        if (! $offerId) {
            throw new RuntimeException('Price proposal did not create or update an offer.');
        }

        $this->auditDomainChange(
            $reviewer,
            'place_price_offer',
            $offerId,
            'change_request_applied_period_pricing',
            $this->decodeJsonValue($request->original_value),
            $this->decodeJsonValue($request->proposed_value),
            $requests,
            $now,
        );

        return [$request->id => $offerId];
    }

    private function applyPlaceFeatureRecord(Collection $requests, int $placeId, User $reviewer, $now): array
    {
        $operations = $requests->pluck('operation')->unique();
        if ($operations->count() !== 1) {
            throw new RuntimeException('A place feature record group cannot mix operations.');
        }

        $operation = $operations->first();
        $commentRequest = $requests->first(fn ($request) => $request->target_field === 'comment');
        $fieldRequests = $requests->reject(fn ($request) => $request->target_field === 'comment')->values();

        if ($operation === 'create') {
            $values = [
                'place_id' => $placeId,
                'feature_id' => null,
                'feature_option_id' => null,
                'value_number' => null,
                'value_text' => null,
                'unit_key' => null,
                'unit_id' => null,
                'rate_quantity' => null,
                'rate_unit_id' => null,
                'status' => 'unknown',
                'metadata' => null,
                'is_active' => true,
                'internal_comment' => null,
                'valid_from' => $now,
                'valid_until' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $changed = [];
            foreach ($fieldRequests as $request) {
                $proposed = $this->decodeJsonValue($request->proposed_value);
                $values[$request->target_field] = $proposed;
                $changed[$request->target_field] = $proposed;
            }

            if (! $values['feature_id']) {
                throw new RuntimeException('Creating a place feature requires feature_id.');
            }

            $newId = DB::table('place_features')->insertGetId($values);
            if ($commentRequest) {
                $this->applyPlaceFeatureComment($commentRequest, null, $newId, $now);
                $changed['comment'] = $this->decodeJsonValue($commentRequest->proposed_value);
            }

            $this->auditDomainChange($reviewer, 'place_feature', $newId, 'change_request_applied_create', null, ['record_id' => $newId, 'values' => $changed], $requests, $now);

            return $requests->mapWithKeys(fn ($request) => [$request->id => $newId])->all();
        }

        $targetIds = $requests->pluck('target_record_id')->unique();
        if ($targetIds->count() !== 1) {
            throw new RuntimeException('Grouped feature changes must target exactly one feature version.');
        }

        $targetId = (int) $targetIds->first();
        $record = DB::table('place_features')->where('id', $targetId)->lockForUpdate()->first();

        if (! $record || (int) $record->place_id !== $placeId) {
            throw new RuntimeException('Target feature does not belong to the request place.');
        }

        if (! $record->is_active || $record->valid_until !== null) {
            throw new RuntimeException('Target feature is no longer the current active version.');
        }

        foreach ($fieldRequests as $request) {
            $this->assertCurrentValue($record, $request, 'place_features');
        }
        if ($commentRequest) {
            $this->assertPlaceFeatureCommentCurrent($commentRequest, $targetId);
        }

        if ($operation === 'deactivate') {
            DB::table('place_features')->where('id', $targetId)->update([
                'is_active' => false,
                'valid_until' => $now,
                'updated_at' => $now,
            ]);

            $this->auditDomainChange($reviewer, 'place_feature', $targetId, 'change_request_applied_deactivate', ['record_id' => $targetId, 'is_active' => true], ['record_id' => $targetId, 'is_active' => false], $requests, $now);

            return $requests->mapWithKeys(fn ($request) => [$request->id => $targetId])->all();
        }

        $newValues = (array) $record;
        unset($newValues['id']);
        [$oldChangedValues, $newChangedValues] = $this->applyFieldsToClone($fieldRequests, $record, $newValues, 'place_features');

        DB::table('place_features')->where('id', $targetId)->update([
            'is_active' => false,
            'valid_until' => $now,
            'updated_at' => $now,
        ]);

        $newValues['is_active'] = true;
        $newValues['valid_from'] = $now;
        $newValues['valid_until'] = null;
        $newValues['created_at'] = $now;
        $newValues['updated_at'] = $now;
        $newId = DB::table('place_features')->insertGetId($newValues);

        $this->copyVersionChildren('place_features', $targetId, $newId, $now);

        if ($commentRequest) {
            $originalComment = $this->decodeJsonValue($commentRequest->original_value);
            $proposedComment = $this->decodeJsonValue($commentRequest->proposed_value);
            $oldChangedValues['comment'] = $originalComment;
            $newChangedValues['comment'] = $proposedComment;
            $this->applyPlaceFeatureComment($commentRequest, $targetId, $newId, $now);
        }

        $this->auditDomainChange(
            $reviewer,
            'place_feature',
            $newId,
            'change_request_applied_version',
            ['record_id' => $targetId, 'values' => $oldChangedValues],
            ['record_id' => $newId, 'values' => $newChangedValues],
            $requests,
            $now,
        );

        return $requests->mapWithKeys(fn ($request) => [$request->id => $newId])->all();
    }

    private function assertPlaceFeatureCommentCurrent(object $request, int $placeFeatureId): void
    {
        $original = $this->decodeJsonValue($request->original_value);
        $locale = is_array($original) ? ($original['locale'] ?? null) : null;
        $expected = is_array($original) ? ($original['note'] ?? null) : null;

        if (! is_string($locale) || $locale === '') {
            throw new RuntimeException('Feature comment change request has no locale.');
        }

        $current = DB::table('place_feature_notes')
            ->where('place_feature_id', $placeFeatureId)
            ->where('locale', $locale)
            ->where('is_active', true)
            ->value('note');

        if (! $this->valuesEquivalent($current, $expected)) {
            throw new RuntimeException('Stale change request: feature comment changed after submission.');
        }
    }

    private function applyPlaceFeatureComment(object $request, ?int $oldPlaceFeatureId, int $newPlaceFeatureId, $now): void
    {
        $proposed = $this->decodeJsonValue($request->proposed_value);
        $locale = is_array($proposed) ? ($proposed['locale'] ?? null) : null;
        $note = is_array($proposed) ? ($proposed['note'] ?? null) : null;

        if (! is_string($locale) || $locale === '') {
            throw new RuntimeException('Feature comment change request has no locale.');
        }

        DB::table('place_feature_notes')
            ->where('place_feature_id', $newPlaceFeatureId)
            ->where('locale', $locale)
            ->delete();

        if ($note !== null && trim((string) $note) !== '') {
            DB::table('place_feature_notes')->insert([
                'place_feature_id' => $newPlaceFeatureId,
                'locale' => $locale,
                'note' => trim((string) $note),
                'is_active' => true,
                'internal_comment' => 'Applied from approved feature change request.',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function applyFieldsToClone(Collection $requests, object $record, array &$newValues, string $table): array
    {
        $oldChangedValues = [];
        $newChangedValues = [];

        foreach ($requests as $request) {
            $this->assertCurrentValue($record, $request, $table);
            $proposed = $this->decodeJsonValue($request->proposed_value);
            $oldChangedValues[$request->target_field] = $record->{$request->target_field};
            $newChangedValues[$request->target_field] = $proposed;
            $newValues[$request->target_field] = $proposed;
        }

        return [$oldChangedValues, $newChangedValues];
    }

    private function assertCurrentValue(object $record, object $request, string $table): void
    {
        if (! property_exists($record, $request->target_field)) {
            throw new RuntimeException("Field {$table}.{$request->target_field} does not exist.");
        }

        $current = $record->{$request->target_field};
        $original = $this->decodeJsonValue($request->original_value);
        if (! $this->valuesEquivalent($current, $original)) {
            throw new RuntimeException("Stale change request: {$table}.{$request->target_field} changed after submission.");
        }
    }

    private function copyVersionChildren(string $table, int $oldId, int $newId, $now): void
    {
        $child = match ($table) {
            'place_contacts' => ['table' => 'place_contact_translations', 'parent_key' => 'place_contact_id'],
            'place_details' => ['table' => 'place_detail_translations', 'parent_key' => 'place_detail_id'],
            'place_vehicle_types' => ['table' => 'place_vehicle_type_notes', 'parent_key' => 'place_vehicle_type_id'],
            'place_features' => ['table' => 'place_feature_notes', 'parent_key' => 'place_feature_id'],
            default => null,
        };

        if (! $child) {
            return;
        }

        $rows = DB::table($child['table'])
            ->where($child['parent_key'], $oldId)
            ->where('is_active', true)
            ->get();

        foreach ($rows as $row) {
            $values = (array) $row;
            unset($values['id']);
            $values[$child['parent_key']] = $newId;
            $values['created_at'] = $now;
            $values['updated_at'] = $now;
            DB::table($child['table'])->insert($values);
        }
    }

    private function auditDomainChange(User $reviewer, string $entityType, int $entityId, string $action, mixed $oldValues, mixed $newValues, Collection $requests, $now): void
    {
        DB::table('audit_logs')->insert([
            'user_id' => $reviewer->id,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'source' => 'admin',
            'old_values' => $oldValues === null ? null : $this->json($oldValues),
            'new_values' => $newValues === null ? null : $this->json($newValues),
            'internal_comment' => 'Applied approved change request(s): '.$requests->pluck('id')->implode(', '),
            'created_at' => $now,
        ]);
    }

    private function decodeJsonValue(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        return json_decode((string) $value, true, 512, JSON_THROW_ON_ERROR);
    }

    private function valuesEquivalent(mixed $current, mixed $original): bool
    {
        if ($current === $original) {
            return true;
        }

        if (is_numeric($current) && is_numeric($original)) {
            return (float) $current === (float) $original;
        }

        return (string) $current === (string) $original;
    }

    private function json(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
