<?php

namespace App\Services\Imports;

use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ExternalRecordStagingService
{
    public function stageDatexParking(array $mappedRecords, bool $completeSnapshot = false): array
    {
        return $this->stage($mappedRecords, [
            'slug' => 'mobilithek-itp-bab',
            'name' => 'Mobilithek – Lkw-Parken BAB Deutschland',
            'provider' => 'Mobilithek / Autobahn-Lkw-Parken',
            'source_type' => 'datex2',
            'adapter' => Datex2ParkingParser::class,
            'country_code' => 'DE',
            'sync_interval_minutes' => 43200,
        ], $completeSnapshot);
    }

    public function stage(array $mappedRecords, array $source, bool $completeSnapshot = false): array
    {
        $sourceId = $this->ensureSource($source);
        $now = now();

        $stats = [
            'source_id' => $sourceId,
            'records' => 0,
            'new' => 0,
            'changed' => 0,
            'unchanged' => 0,
            'missing_marked' => 0,
        ];

        $seen = [];

        DB::transaction(function () use ($mappedRecords, $completeSnapshot, $sourceId, $now, &$stats, &$seen): void {
            foreach ($mappedRecords as $mapped) {
                $externalId = $mapped['external_id'] ?? null;

                if (! is_string($externalId) || $externalId === '') {
                    continue;
                }

                $seen[] = $externalId;

                $existing = DB::table('external_records')
                    ->where('external_source_id', $sourceId)
                    ->where('external_id', $externalId)
                    ->first();

                if ($existing) {
                    $mapped = $this->applyManualOverrides($mapped, $existing->manual_overrides ?? null);
                }

                $normalizedJson = $this->canonicalJson($mapped);
                $hash = hash('sha256', $normalizedJson);

                if (! $existing) {
                    $recordId = (int) DB::table('external_records')->insertGetId([
                        'external_source_id' => $sourceId,
                        'external_id' => $externalId,
                        'status' => 'active',
                        'normalized_hash' => $hash,
                        'normalized_data' => $normalizedJson,
                        'source_updated_at' => $this->sourceUpdatedAt($mapped),
                        'first_seen_at' => $now,
                        'last_seen_at' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    $stats['new']++;
                } else {
                    $recordId = (int) $existing->id;
                    $changed = $existing->normalized_hash !== $hash;

                    DB::table('external_records')->where('id', $recordId)->update([
                        'status' => 'active',
                        'normalized_hash' => $hash,
                        'normalized_data' => $normalizedJson,
                        'source_updated_at' => $this->sourceUpdatedAt($mapped),
                        'last_seen_at' => $now,
                        'missing_since' => null,
                        'updated_at' => $now,
                    ]);

                    if ($existing->status === 'missing') {
                        DB::table('external_import_review_items')
                            ->where('external_record_id', $recordId)
                            ->where('type', 'source_missing')
                            ->where('status', 'pending')
                            ->update([
                                'status' => 'superseded',
                                'resolved_at' => $now,
                                'resolution_note' => 'Der Datensatz ist in der Quelle wieder vorhanden.',
                                'updated_at' => $now,
                            ]);
                    }

                    $stats[$changed ? 'changed' : 'unchanged']++;
                }

                $this->syncFields($recordId, $mapped, $now);
                $stats['records']++;
            }

            if ($completeSnapshot) {
                $query = DB::table('external_records')
                    ->where('external_source_id', $sourceId)
                    ->where('status', 'active');

                if ($seen !== []) {
                    $query->whereNotIn('external_id', array_values(array_unique($seen)));
                }

                $stats['missing_marked'] = $query->update([
                    'status' => 'missing',
                    'missing_since' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('external_sources')->where('id', $sourceId)->update([
                'last_checked_at' => $now,
                'last_success_at' => $now,
                'updated_at' => $now,
            ]);
        });

        return $stats;
    }

    public function completeSnapshot(int $sourceId, array $seenExternalIds, ?int $reviewRunId = null): int
    {
        $now = now();
        $query = DB::table('external_records')
            ->where('external_source_id', $sourceId)
            ->where('status', 'active');

        if ($seenExternalIds !== []) {
            $normalizedExternalIds = array_values(array_unique(array_map(
                static fn ($externalId): string => (string) $externalId,
                $seenExternalIds,
            )));

            $query->whereNotIn('external_id', $normalizedExternalIds);
        }

        $missingRecords = (clone $query)->get([
            'id',
            'external_id',
            'place_id',
            'normalized_data',
        ]);

        $missing = $query->update([
            'status' => 'missing',
            'missing_since' => $now,
            'updated_at' => $now,
        ]);

        if ($reviewRunId !== null) {
            foreach ($missingRecords as $record) {
                if ($record->place_id === null) {
                    continue;
                }

                $mapped = json_decode((string) $record->normalized_data, true);
                $name = is_array($mapped)
                    ? trim((string) ($mapped['place']['name'] ?? ''))
                    : '';

                DB::table('external_import_review_items')
                    ->where('external_record_id', $record->id)
                    ->where('type', 'source_missing')
                    ->where('status', 'pending')
                    ->update([
                        'status' => 'superseded',
                        'resolved_at' => $now,
                        'resolution_note' => 'Durch einen neueren Snapshot ersetzt.',
                        'updated_at' => $now,
                    ]);

                DB::table('external_import_review_items')->insert([
                    'external_import_run_id' => $reviewRunId,
                    'external_source_id' => $sourceId,
                    'external_record_id' => $record->id,
                    'place_id' => $record->place_id,
                    'type' => 'source_missing',
                    'severity' => 'warning',
                    'status' => 'pending',
                    'details' => json_encode([
                        'external_id' => $record->external_id,
                        'external_name' => $name !== '' ? $name : null,
                        'missing_since' => $now->toIso8601String(),
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        DB::table('external_sources')->where('id', $sourceId)->update([
            'last_checked_at' => $now,
            'last_success_at' => $now,
            'updated_at' => $now,
        ]);

        return $missing;
    }

    private function ensureSource(array $source): int
    {
        $slug = (string) ($source['slug'] ?? '');

        if ($slug === '') {
            throw new RuntimeException('External source slug is required.');
        }

        $values = [
            'name' => (string) ($source['name'] ?? $slug),
            'source_type' => (string) ($source['source_type'] ?? 'manual'),
            'is_active' => true,
            'updated_at' => now(),
        ];

        foreach ([
            'provider',
            'adapter',
            'base_url',
            'country_code',
            'license_code',
            'license_name',
            'license_url',
            'attribution_text',
            'sync_interval_minutes',
        ] as $key) {
            if (array_key_exists($key, $source)) {
                $values[$key] = $source[$key];
            }
        }

        if (array_key_exists('config', $source)) {
            $values['config'] = json_encode(
                $source['config'],
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            );
        }

        $existing = DB::table('external_sources')->where('slug', $slug)->first();

        if ($existing) {
            DB::table('external_sources')->where('id', $existing->id)->update($values);

            return (int) $existing->id;
        }

        $id = DB::table('external_sources')->insertGetId($values + [
            'slug' => $slug,
            'created_at' => now(),
        ]);

        if (! $id) {
            throw new RuntimeException('External source could not be created.');
        }

        return (int) $id;
    }

    private function syncFields(int $recordId, array $mapped, $now): void
    {
        $fields = [
            'name' => Arr::get($mapped, 'place.name'),
            'alias' => Arr::get($mapped, 'place.alias'),
            'latitude' => Arr::get($mapped, 'place.latitude'),
            'longitude' => Arr::get($mapped, 'place.longitude'),
            'coordinate_source' => Arr::get($mapped, 'place.coordinate_source'),
            'address' => Arr::get($mapped, 'place.address'),
            'operator' => Arr::get($mapped, 'place.operator'),
            'free_of_charge' => Arr::get($mapped, 'place.free_of_charge'),
            'parking_spaces_total' => Arr::get($mapped, 'place.parking_spaces_total'),
            'minimum_stay_nights' => Arr::get($mapped, 'place.minimum_stay_nights'),
            'pitch_area_min_m2' => Arr::get($mapped, 'place.pitch_area_min_m2'),
            'min_price' => Arr::get($mapped, 'place.min_price'),
            'currency' => Arr::get($mapped, 'place.currency'),
            'features' => $mapped['features'] ?? [],
            'media' => $mapped['media'] ?? [],
            'license' => $mapped['license'] ?? [],
            'source_categories' => $mapped['source_categories'] ?? [],
            'source_features' => $mapped['source_features'] ?? [],
            'source_payments' => $mapped['source_payments'] ?? [],
            'source_numbers' => $mapped['source_numbers'] ?? [],
            'vehicle_capacities' => $mapped['vehicle_capacities'] ?? [],
            'access_points' => $mapped['access_points'] ?? [],
        ];

        foreach ($fields as $key => $value) {
            $json = $this->canonicalJson($value);

            DB::table('external_record_fields')->updateOrInsert(
                [
                    'external_record_id' => $recordId,
                    'field_key' => $key,
                ],
                [
                    'value' => $json,
                    'value_hash' => hash('sha256', $json),
                    'source_updated_at' => $this->sourceUpdatedAt($mapped),
                    'last_seen_at' => $now,
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }
    }

    private function applyManualOverrides(array $mapped, mixed $rawOverrides): array
    {
        if ($rawOverrides === null || $rawOverrides === '') {
            return $mapped;
        }

        $overrides = is_string($rawOverrides)
            ? json_decode($rawOverrides, true)
            : $rawOverrides;

        if (! is_array($overrides)) {
            return $mapped;
        }

        $sourceLat = Arr::get($mapped, 'place.latitude');
        $sourceLon = Arr::get($mapped, 'place.longitude');
        $overrideLat = $overrides['place.latitude'] ?? null;
        $overrideLon = $overrides['place.longitude'] ?? null;

        // Manual coordinates only fill a gap. As soon as the upstream source
        // supplies valid coordinates itself, the authoritative source value wins.
        if ((! is_numeric($sourceLat) || ! is_numeric($sourceLon))
            && is_numeric($overrideLat)
            && is_numeric($overrideLon)) {
            Arr::set($mapped, 'place.latitude', (float) $overrideLat);
            Arr::set($mapped, 'place.longitude', (float) $overrideLon);
            Arr::set($mapped, 'place.coordinate_source', 'manual-review');
        }

        return $mapped;
    }

    private function sourceUpdatedAt(array $mapped): ?string
    {
        $value = $mapped['source_updated_at'] ?? null;

        if ($value === null || $value === '') {
            return null;
        }

        return CarbonImmutable::parse((string) $value)
            ->utc()
            ->format('Y-m-d H:i:s');
    }

    private function canonicalJson(mixed $value): string
    {
        $value = $this->sortRecursive($value);

        return json_encode(
            $value,
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
}
