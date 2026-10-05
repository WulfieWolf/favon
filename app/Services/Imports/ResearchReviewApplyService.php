<?php

namespace App\Services\Imports;

use App\Models\User;
use App\Services\PlaceHistoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ResearchReviewApplyService
{
    public function approveAll(User $actor): array
    {
        $reviewIds = DB::table('external_import_review_items')
            ->where('status', 'pending')
            ->where('type', 'research_update')
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $applied = 0;
        $skipped = [];

        foreach ($reviewIds as $reviewId) {
            try {
                $this->approve($reviewId, $actor);
                $applied++;
            } catch (RuntimeException $e) {
                $skipped[] = [
                    'review_id' => $reviewId,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return [
            'attempted' => count($reviewIds),
            'applied' => $applied,
            'skipped' => $skipped,
        ];
    }

    public function approve(int $reviewId, User $actor): int
    {
        return DB::transaction(function () use ($reviewId, $actor): int {
            $review = DB::table('external_import_review_items')
                ->where('id', $reviewId)
                ->lockForUpdate()
                ->first();

            if (! $review || $review->status !== 'pending' || ! in_array($review->type, ['research_update', 'research_conflict'], true)) {
                throw new RuntimeException(__('admin_imports.errors.research_review_closed'));
            }

            $record = DB::table('external_records')
                ->where('id', $review->external_record_id)
                ->lockForUpdate()
                ->first();

            if (! $record || $record->status !== 'active' || ! $record->place_id) {
                throw new RuntimeException(__('admin_imports.errors.research_record_inactive'));
            }

            $details = json_decode((string) $review->details, true) ?: [];
            $normalized = json_decode((string) $record->normalized_data, true) ?: [];
            $proposals = is_array($normalized['proposals'] ?? null) ? $normalized['proposals'] : [];
            $changes = is_array($details['changes'] ?? null) ? $details['changes'] : [];

            if ($proposals === [] || $changes === []) {
                throw new RuntimeException(__('admin_imports.errors.research_no_changes'));
            }

            $placeId = (int) $record->place_id;
            $this->assertNotStale($placeId, $changes);

            foreach ($proposals as $field => $value) {
                if (! array_key_exists($field, $changes)) {
                    continue;
                }

                if (str_starts_with($field, 'suitable_') && $value === 'no') {
                    $slug = Str::after($field, 'suitable_');
                    $current = $this->currentVehicleRow($placeId, $slug);
                    if (! $current) {
                        throw new RuntimeException(__('admin_imports.errors.research_unsuitable_loss', ['slug' => $slug]));
                    }
                }
            }

            $now = now();

            $this->applyPlaceFields($placeId, $proposals, $changes, $now);
            $this->applyAddressFields($placeId, $proposals, $changes, $now);
            $this->applyDetailFields($placeId, $proposals, $changes, $actor, $now);
            $this->applyContactFields($placeId, $proposals, $changes, $actor, $now);
            $this->applyVehicleFields($placeId, $proposals, $changes, $now);

            DB::table('external_import_review_items')->where('id', $reviewId)->update([
                'status' => 'resolved',
                'resolved_by' => $actor->id,
                'resolved_at' => $now,
                'resolution_note' => 'Recherchewerte vom Administrator übernommen.',
                'updated_at' => $now,
            ]);

            DB::table('external_records')->where('id', $record->id)->update([
                'classification' => 'research_applied',
                'classified_at' => $now,
                'updated_at' => $now,
            ]);

            $historyChanges = collect($changes)
                ->map(fn ($change, $field) => [
                    'field' => (string) $field,
                    'old' => $change['current'] ?? null,
                    'new' => $change['proposed'] ?? null,
                ])
                ->values()
                ->all();

            app(PlaceHistoryService::class)->addExternalSource(
                $placeId,
                (int) $review->external_source_id,
                'research_data_applied',
                __('place_profile.history.actions.research_data_applied'),
                [
                    'author' => $details['author'] ?? null,
                    'source_label' => $details['source_label'] ?? null,
                    'source_url' => $details['source_url'] ?? null,
                    'researched_at' => $details['researched_at'] ?? null,
                    'approved_at' => $now->toIso8601String(),
                    'reviewed_by' => (int) $actor->id,
                    'external_record_id' => (int) $record->id,
                    'research_review_id' => (int) $reviewId,
                    'notes' => $details['notes'] ?? null,
                    'research_changes' => $historyChanges,
                ],
            );

            DB::table('audit_logs')->insert([
                'user_id' => $actor->id,
                'entity_type' => 'place',
                'entity_id' => $placeId,
                'action' => 'research_review_applied',
                'source' => 'admin',
                'old_values' => json_encode(
                    collect($changes)->mapWithKeys(fn ($change, $field) => [$field => $change['current'] ?? null])->all(),
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ),
                'new_values' => json_encode(
                    collect($changes)->mapWithKeys(fn ($change, $field) => [$field => $change['proposed'] ?? null])->all(),
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ),
                'internal_comment' => 'Übernahme aus Recherche-Import Review #'.$reviewId,
                'created_at' => $now,
            ]);

            return $placeId;
        });
    }

    private function assertNotStale(int $placeId, array $changes): void
    {
        $current = $this->currentValues($placeId, array_keys($changes));

        foreach ($changes as $field => $change) {
            $expected = $change['current'] ?? null;
            $actual = $current[$field] ?? null;

            if (! $this->equivalent($expected, $actual)) {
                throw new RuntimeException(__('admin_imports.errors.research_stale_value', ['field' => $field]));
            }
        }
    }

    private function applyPlaceFields(int $placeId, array $proposals, array $changes, $now): void
    {
        $map = [
            'name' => 'name',
            'latitude' => 'latitude',
            'longitude' => 'longitude',
            'legal_status' => 'legal_status',
            'opening_status' => 'opening_status',
        ];

        $updates = [];
        foreach ($map as $researchField => $column) {
            if (array_key_exists($researchField, $changes)) {
                $updates[$column] = $proposals[$researchField];
            }
        }

        if (array_key_exists('place_type', $changes)) {
            $typeId = DB::table('place_types')
                ->where('slug', $proposals['place_type'])
                ->where('is_active', true)
                ->value('id');

            if (! $typeId) {
                throw new RuntimeException(__('admin_imports.errors.research_place_type_inactive'));
            }

            $updates['place_type_id'] = $typeId;
        }

        if ($updates !== []) {
            $updates['updated_at'] = $now;
            DB::table('places')->where('id', $placeId)->update($updates);
        }
    }

    private function applyAddressFields(int $placeId, array $proposals, array $changes, $now): void
    {
        $fields = ['country_code', 'postal_code', 'city', 'street', 'house_number', 'address_addition'];
        $changed = array_values(array_filter($fields, fn ($field) => array_key_exists($field, $changes)));

        if ($changed === []) {
            return;
        }

        $current = DB::table('place_addresses')
            ->where('place_id', $placeId)
            ->where('is_active', true)
            ->whereNull('version_valid_until')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();

        if ($current) {
            $values = (array) $current;
            unset($values['id']);

            DB::table('place_addresses')->where('id', $current->id)->update([
                'is_active' => false,
                'version_valid_until' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $values = [
                'place_id' => $placeId,
                'country_code' => null,
                'region_id' => null,
                'postal_code' => null,
                'city' => null,
                'street' => null,
                'house_number' => null,
                'address_addition' => null,
                'internal_comment' => null,
            ];
        }

        foreach ($changed as $field) {
            $values[$field] = $proposals[$field];
        }

        $values['place_id'] = $placeId;
        $values['is_active'] = true;
        $values['version_valid_from'] = $now;
        $values['version_valid_until'] = null;
        $values['created_at'] = $now;
        $values['updated_at'] = $now;

        DB::table('place_addresses')->insert($values);
    }

    private function applyDetailFields(int $placeId, array $proposals, array $changes, User $actor, $now): void
    {
        $map = [
            'operator' => 'operator_name',
            'parking_spaces' => 'pitch_count',
        ];

        $changed = array_filter($map, fn ($column, $field) => array_key_exists($field, $changes), ARRAY_FILTER_USE_BOTH);
        if ($changed === []) {
            return;
        }

        $current = DB::table('place_details')
            ->where('place_id', $placeId)
            ->where('is_active', true)
            ->whereNull('version_valid_until')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();

        if ($current) {
            $values = (array) $current;
            unset($values['id']);

            DB::table('place_details')->where('id', $current->id)->update([
                'is_active' => false,
                'version_valid_until' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $values = [
                'place_id' => $placeId,
                'operator_name' => null,
                'pitch_count' => null,
                'pitch_count_source' => null,
                'minimum_stay_nights' => null,
                'pitch_area_min_m2' => null,
                'internal_comment' => null,
                'created_by' => $actor->id,
            ];
        }

        foreach ($changed as $field => $column) {
            $values[$column] = $proposals[$field];
        }

        if (array_key_exists('parking_spaces', $changes)) {
            $values['pitch_count_source'] = 'research';
        }

        $values['place_id'] = $placeId;
        $values['is_active'] = true;
        $values['version_valid_from'] = $now;
        $values['version_valid_until'] = null;
        $values['created_at'] = $now;
        $values['updated_at'] = $now;

        DB::table('place_details')->insert($values);
    }

    private function applyContactFields(int $placeId, array $proposals, array $changes, User $actor, $now): void
    {
        foreach (['website', 'phone', 'email'] as $field) {
            if (! array_key_exists($field, $changes)) {
                continue;
            }

            $types = match ($field) {
                'website' => ['website', 'url'],
                'phone' => ['telephone', 'phone'],
                'email' => ['email'],
            };
            $canonicalType = match ($field) {
                'phone' => 'telephone',
                default => $field,
            };

            $current = DB::table('place_contacts')
                ->where('place_id', $placeId)
                ->whereIn('contact_type', $types)
                ->where('is_active', true)
                ->whereNull('version_valid_until')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if ($current) {
                $values = (array) $current;
                unset($values['id']);

                DB::table('place_contacts')->where('id', $current->id)->update([
                    'is_active' => false,
                    'version_valid_until' => $now,
                    'updated_at' => $now,
                ]);

                $values['value'] = $proposals[$field];
                $values['contact_type'] = $canonicalType;
            } else {
                $maxSort = (int) DB::table('place_contacts')
                    ->where('place_id', $placeId)
                    ->where('is_active', true)
                    ->whereNull('version_valid_until')
                    ->max('sort_order');

                $values = [
                    'place_id' => $placeId,
                    'contact_type' => $canonicalType,
                    'value' => $proposals[$field],
                    'label' => null,
                    'sort_order' => $maxSort + 10,
                    'internal_comment' => null,
                    'created_by' => $actor->id,
                ];
            }

            $values['place_id'] = $placeId;
            $values['is_active'] = true;
            $values['version_valid_from'] = $now;
            $values['version_valid_until'] = null;
            $values['created_at'] = $now;
            $values['updated_at'] = $now;

            DB::table('place_contacts')->insert($values);
        }
    }

    private function applyVehicleFields(int $placeId, array $proposals, array $changes, $now): void
    {
        foreach ($changes as $field => $change) {
            if (! str_starts_with((string) $field, 'suitable_')) {
                continue;
            }

            $slug = Str::after((string) $field, 'suitable_');
            $vehicleTypeId = DB::table('vehicle_types')
                ->where('slug', $slug)
                ->where('is_active', true)
                ->value('id');

            if (! $vehicleTypeId) {
                throw new RuntimeException(__('admin_imports.errors.research_vehicle_inactive', ['slug' => $slug]));
            }

            $current = DB::table('place_vehicle_types')
                ->where('place_id', $placeId)
                ->where('vehicle_type_id', $vehicleTypeId)
                ->where('is_active', true)
                ->whereNull('version_valid_until')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            $proposed = $proposals[$field];

            if ($proposed === 'no') {
                if ($current) {
                    DB::table('place_vehicle_types')->where('id', $current->id)->update([
                        'is_active' => false,
                        'version_valid_until' => $now,
                        'updated_at' => $now,
                    ]);
                }
                continue;
            }

            $capacity = is_int($proposed) ? $proposed : null;

            if ($current) {
                $values = (array) $current;
                unset($values['id']);

                DB::table('place_vehicle_types')->where('id', $current->id)->update([
                    'is_active' => false,
                    'version_valid_until' => $now,
                    'updated_at' => $now,
                ]);

                $values['capacity'] = $capacity;
            } else {
                $values = [
                    'place_id' => $placeId,
                    'vehicle_type_id' => $vehicleTypeId,
                    'capacity' => $capacity,
                    'internal_comment' => null,
                ];
            }

            $values['place_id'] = $placeId;
            $values['vehicle_type_id'] = $vehicleTypeId;
            $values['is_active'] = true;
            $values['version_valid_from'] = $now;
            $values['version_valid_until'] = null;
            $values['created_at'] = $now;
            $values['updated_at'] = $now;

            DB::table('place_vehicle_types')->insert($values);
        }
    }

    private function currentValues(int $placeId, array $fields): array
    {
        $place = DB::table('places as p')
            ->join('place_types as pt', 'pt.id', '=', 'p.place_type_id')
            ->where('p.id', $placeId)
            ->first([
                'p.name',
                'pt.slug as place_type',
                'p.latitude',
                'p.longitude',
                'p.legal_status',
                'p.opening_status',
            ]);

        $address = DB::table('place_addresses')
            ->where('place_id', $placeId)
            ->where('is_active', true)
            ->whereNull('version_valid_until')
            ->orderByDesc('id')
            ->first();

        $details = DB::table('place_details')
            ->where('place_id', $placeId)
            ->where('is_active', true)
            ->whereNull('version_valid_until')
            ->orderByDesc('id')
            ->first();

        $contacts = DB::table('place_contacts')
            ->where('place_id', $placeId)
            ->where('is_active', true)
            ->whereNull('version_valid_until')
            ->whereIn('contact_type', ['website', 'url', 'phone', 'telephone', 'email'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $values = [
            'name' => $place?->name,
            'place_type' => $place?->place_type,
            'latitude' => $place?->latitude !== null ? (float) $place->latitude : null,
            'longitude' => $place?->longitude !== null ? (float) $place->longitude : null,
            'legal_status' => $place?->legal_status,
            'opening_status' => $place?->opening_status,
            'country_code' => $address?->country_code,
            'postal_code' => $address?->postal_code,
            'city' => $address?->city,
            'street' => $address?->street,
            'house_number' => $address?->house_number,
            'address_addition' => $address?->address_addition,
            'operator' => $details?->operator_name,
            'parking_spaces' => $details?->pitch_count !== null ? (int) $details->pitch_count : null,
            'website' => $contacts->first(fn ($row) => in_array($row->contact_type, ['website', 'url'], true))?->value,
            'phone' => $contacts->first(fn ($row) => in_array($row->contact_type, ['telephone', 'phone'], true))?->value,
            'email' => $contacts->firstWhere('contact_type', 'email')?->value,
        ];

        foreach ($fields as $field) {
            if (! str_starts_with((string) $field, 'suitable_')) {
                continue;
            }

            $slug = Str::after((string) $field, 'suitable_');
            $row = $this->currentVehicleRow($placeId, $slug);
            $values[$field] = $row
                ? ($row->capacity !== null ? (int) $row->capacity : 'yes')
                : null;
        }

        return $values;
    }

    private function currentVehicleRow(int $placeId, string $slug): ?object
    {
        return DB::table('place_vehicle_types as pvt')
            ->join('vehicle_types as vt', 'vt.id', '=', 'pvt.vehicle_type_id')
            ->where('pvt.place_id', $placeId)
            ->where('vt.slug', $slug)
            ->where('pvt.is_active', true)
            ->whereNull('pvt.version_valid_until')
            ->orderByDesc('pvt.id')
            ->first(['pvt.*']);
    }

    private function equivalent(mixed $a, mixed $b): bool
    {
        if (is_numeric($a) && is_numeric($b)) {
            return (float) $a === (float) $b;
        }

        return trim((string) $a) === trim((string) $b);
    }
}
