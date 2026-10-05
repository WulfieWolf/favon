<?php

namespace App\Services\Imports;

use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ResearchCsvImportService
{
    private const BASE_FIELDS = [
        'name',
        'place_type',
        'latitude',
        'longitude',
        'legal_status',
        'opening_status',
        'country_code',
        'postal_code',
        'city',
        'street',
        'house_number',
        'address_addition',
        'operator',
        'parking_spaces',
        'website',
        'phone',
        'email',
    ];

    public function import(UploadedFile $file): array
    {
        [$headers, $rows] = $this->parse($file->getRealPath());
        $vehicleSlugs = $this->validateHeaders($headers);
        $sourceId = $this->ensureSource();
        $runId = $this->startRun($sourceId, count($rows), hash_file('sha256', $file->getRealPath()) ?: null);

        $stats = [
            'run_id' => $runId,
            'rows' => count($rows),
            'staged' => 0,
            'no_change' => 0,
            'review' => 0,
            'conflicts' => 0,
            'skipped' => 0,
        ];

        try {
            DB::transaction(function () use ($rows, $vehicleSlugs, $sourceId, $runId, &$stats): void {
                $prepared = [];

                foreach ($rows as $line => $row) {
                    $preparedRow = $this->prepareRow($row, $vehicleSlugs, $line + 2);
                    if ($preparedRow === null) {
                        $stats['skipped']++;
                        continue;
                    }

                    $prepared[] = $preparedRow;
                }

                $conflicts = $this->crossRowConflicts($prepared);

                foreach ($prepared as $preparedRow) {
                    $placeId = $preparedRow['place_id'];
                    $current = $this->currentValues($placeId, $vehicleSlugs);
                    $comparisons = [];

                    foreach ($preparedRow['proposals'] as $field => $proposed) {
                        $currentValue = $current[$field] ?? null;
                        if ($this->equivalent($currentValue, $proposed)) {
                            continue;
                        }

                        $comparisons[$field] = [
                            'current' => $currentValue,
                            'proposed' => $proposed,
                            'status' => ($currentValue === null || $currentValue === '') ? 'new' : 'different',
                            'conflict' => isset($conflicts[$placeId][$field]),
                        ];
                    }

                    $recordId = $this->stageRecord($sourceId, $preparedRow, $comparisons);
                    $this->replaceFields($recordId, $preparedRow['proposals']);
                    $this->supersedePendingReviews($recordId);

                    if ($comparisons === []) {
                        DB::table('external_records')->where('id', $recordId)->update([
                            'classification' => 'research_no_change',
                            'classified_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $stats['no_change']++;
                        $stats['staged']++;
                        continue;
                    }

                    $hasConflict = collect($comparisons)->contains(fn ($comparison) => $comparison['conflict'] === true);
                    $type = $hasConflict ? 'research_conflict' : 'research_update';

                    DB::table('external_records')->where('id', $recordId)->update([
                        'classification' => $type,
                        'classified_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('external_import_review_items')->insert([
                        'external_import_run_id' => $runId,
                        'external_source_id' => $sourceId,
                        'external_record_id' => $recordId,
                        'place_id' => $placeId,
                        'type' => $type,
                        'severity' => $hasConflict ? 'warning' : 'info',
                        'status' => 'pending',
                        'details' => json_encode([
                            'place_id' => $placeId,
                            'place_name' => $current['name'] ?? null,
                            'name' => $current['name'] ?? null,
                            'author' => $preparedRow['metadata']['author'],
                            'source_label' => $preparedRow['metadata']['source_label'],
                            'source_url' => $preparedRow['metadata']['source_url'],
                            'researched_at' => $preparedRow['metadata']['researched_at'],
                            'notes' => $preparedRow['metadata']['notes'],
                            'changes' => $comparisons,
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $stats['review']++;
                    if ($hasConflict) {
                        $stats['conflicts']++;
                    }
                    $stats['staged']++;
                }

                DB::table('external_import_runs')->where('id', $runId)->update([
                    'status' => 'completed',
                    'mapped_record_count' => $stats['staged'],
                    'updated_count' => $stats['review'],
                    'skipped_count' => $stats['no_change'] + $stats['skipped'],
                    'conflict_count' => $stats['conflicts'],
                    'review_count' => $stats['review'],
                    'stats' => json_encode($stats, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'validated_at' => now(),
                    'completed_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('external_sources')->where('id', $sourceId)->update([
                    'last_checked_at' => now(),
                    'last_success_at' => now(),
                    'updated_at' => now(),
                ]);
            });
        } catch (\Throwable $e) {
            DB::table('external_import_runs')->where('id', $runId)->update([
                'status' => 'failed',
                'error_code' => 'research_import_failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
            throw $e;
        }

        return $stats;
    }

    private function parse(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException(__('admin_imports.errors.research_open'));
        }

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            throw new RuntimeException(__('admin_imports.errors.research_no_header'));
        }

        $delimiter = $this->detectDelimiter($firstLine);
        rewind($handle);

        $headers = fgetcsv($handle, 0, $delimiter, '"', '');
        if (! is_array($headers)) {
            fclose($handle);
            throw new RuntimeException(__('admin_imports.errors.research_bad_header'));
        }

        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]);
        $headers = array_map(fn ($value) => trim((string) $value), $headers);

        $rows = [];
        while (($values = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
            if (count($values) === 1 && trim((string) $values[0]) === '') {
                continue;
            }
            if (count($values) !== count($headers)) {
                fclose($handle);
                throw new RuntimeException(__('admin_imports.errors.research_column_count'));
            }
            $rows[] = array_combine($headers, $values);
        }

        fclose($handle);

        return [$headers, $rows];
    }

    private function detectDelimiter(string $headerLine): string
    {
        $candidates = [
            ';' => substr_count($headerLine, ';'),
            "\t" => substr_count($headerLine, "\t"),
            ',' => substr_count($headerLine, ','),
        ];

        arsort($candidates);
        $delimiter = array_key_first($candidates);

        if ($delimiter === null || ($candidates[$delimiter] ?? 0) === 0) {
            throw new RuntimeException(__('admin_imports.errors.research_delimiter'));
        }

        return $delimiter;
    }

    private function validateHeaders(array $headers): array
    {
        foreach (['place_id', 'author', 'source_label', 'source_url', 'researched_at', 'notes'] as $required) {
            if (! in_array($required, $headers, true)) {
                throw new RuntimeException(__('admin_imports.errors.research_required_column', ['column' => $required]));
            }
        }

        $vehicleSlugs = DB::table('vehicle_types')
            ->where('is_active', true)
            ->pluck('slug')
            ->map(fn ($slug) => (string) $slug)
            ->all();

        foreach ($headers as $header) {
            if (! str_starts_with($header, 'suitable_') || str_starts_with($header, 'suitable_current_')) {
                continue;
            }

            $slug = Str::after($header, 'suitable_');
            if (! in_array($slug, $vehicleSlugs, true)) {
                throw new RuntimeException(__('admin_imports.errors.research_vehicle_type', ['slug' => $slug]));
            }
        }

        return $vehicleSlugs;
    }

    private function prepareRow(array $row, array $vehicleSlugs, int $line): ?array
    {
        $placeId = filter_var(trim((string) ($row['place_id'] ?? '')), FILTER_VALIDATE_INT);
        if (! $placeId || ! DB::table('places')->where('id', $placeId)->exists()) {
            throw new RuntimeException(__('admin_imports.errors.research_place_id', ['line' => $line]));
        }

        $proposals = [];
        foreach (self::BASE_FIELDS as $field) {
            $raw = trim((string) ($row[$field] ?? ''));
            if ($raw === '') {
                continue;
            }
            $proposals[$field] = $this->normalizeProposal($field, $raw, $line);
        }

        foreach ($vehicleSlugs as $slug) {
            $column = 'suitable_'.$slug;
            $raw = trim((string) ($row[$column] ?? ''));
            if ($raw === '') {
                continue;
            }

            $lower = mb_strtolower($raw);
            if ($lower === 'yes') {
                $value = 'yes';
            } elseif ($lower === 'no') {
                $value = 'no';
            } elseif (ctype_digit($raw) && (int) $raw > 0) {
                $value = (int) $raw;
            } else {
                throw new RuntimeException(__('admin_imports.errors.research_suitable_value', ['column' => $column, 'line' => $line]));
            }

            $proposals[$column] = $value;
        }

        if ($proposals === []) {
            return null;
        }

        $author = trim((string) ($row['author'] ?? ''));
        $sourceLabel = trim((string) ($row['source_label'] ?? ''));
        $sourceUrl = trim((string) ($row['source_url'] ?? ''));
        $researchedAt = trim((string) ($row['researched_at'] ?? ''));
        $notes = trim((string) ($row['notes'] ?? ''));

        if ($author === '') {
            throw new RuntimeException(__('admin_imports.errors.research_author', ['line' => $line]));
        }
        if ($sourceLabel === '') {
            throw new RuntimeException(__('admin_imports.errors.research_source_label', ['line' => $line]));
        }
        if ($sourceUrl !== '' && (! filter_var($sourceUrl, FILTER_VALIDATE_URL) || ! in_array(mb_strtolower((string) parse_url($sourceUrl, PHP_URL_SCHEME)), ['http', 'https'], true))) {
            throw new RuntimeException(__('admin_imports.errors.research_source_url', ['line' => $line]));
        }

        if ($researchedAt !== '') {
            try {
                $researchedAt = CarbonImmutable::parse($researchedAt)->format('Y-m-d H:i:s');
            } catch (\Throwable) {
                throw new RuntimeException(__('admin_imports.errors.research_date', ['line' => $line]));
            }
        }

        $metadata = [
            'author' => $author,
            'source_label' => $sourceLabel,
            'source_url' => $sourceUrl !== '' ? $sourceUrl : null,
            'researched_at' => $researchedAt !== '' ? $researchedAt : null,
            'notes' => $notes !== '' ? $notes : null,
        ];

        return [
            'place_id' => (int) $placeId,
            'metadata' => $metadata,
            'proposals' => $proposals,
            'external_id' => $this->externalId((int) $placeId, $metadata),
        ];
    }

    private function normalizeProposal(string $field, string $raw, int $line): mixed
    {
        return match ($field) {
            'latitude' => $this->number($raw, -90, 90, $field, $line),
            'longitude' => $this->number($raw, -180, 180, $field, $line),
            'parking_spaces' => $this->positiveInteger($raw, $field, $line),
            'country_code' => $this->countryCode($raw, $line),
            'website' => $this->url($raw, $field, $line),
            'email' => filter_var($raw, FILTER_VALIDATE_EMAIL) ? $raw : throw new RuntimeException(__('admin_imports.errors.research_email', ['line' => $line])),
            'place_type' => DB::table('place_types')->where('slug', $raw)->where('is_active', true)->exists()
                ? $raw
                : throw new RuntimeException(__('admin_imports.errors.research_place_type', ['line' => $line, 'value' => $raw])),
            'legal_status' => in_array($raw, ['overnight_allowed', 'camping_allowed', 'parking_only', 'prohibited', 'owner_unwanted', 'unclear'], true)
                ? $raw
                : throw new RuntimeException(__('admin_imports.errors.research_legal_status', ['line' => $line])),
            'opening_status' => in_array($raw, ['open', 'temporarily_closed', 'seasonally_closed', 'permanently_closed', 'unclear'], true)
                ? $raw
                : throw new RuntimeException(__('admin_imports.errors.research_opening_status', ['line' => $line])),
            default => $raw,
        };
    }

    private function currentValues(int $placeId, array $vehicleSlugs): array
    {
        $place = DB::table('places as p')
            ->join('place_types as pt', 'pt.id', '=', 'p.place_type_id')
            ->where('p.id', $placeId)
            ->first([
                'p.name', 'pt.slug as place_type', 'p.latitude', 'p.longitude',
                'p.legal_status', 'p.opening_status',
            ]);

        $address = DB::table('place_addresses')
            ->where('place_id', $placeId)->where('is_active', true)->whereNull('version_valid_until')
            ->orderByDesc('id')->first();

        $details = DB::table('place_details')
            ->where('place_id', $placeId)->where('is_active', true)->whereNull('version_valid_until')
            ->orderByDesc('id')->first();

        $contacts = DB::table('place_contacts')
            ->where('place_id', $placeId)->where('is_active', true)->whereNull('version_valid_until')
            ->whereIn('contact_type', ['website', 'url', 'phone', 'telephone', 'email'])
            ->orderBy('sort_order')->orderBy('id')->get();

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
            'phone' => $contacts->first(fn ($row) => in_array($row->contact_type, ['phone', 'telephone'], true))?->value,
            'email' => $contacts->firstWhere('contact_type', 'email')?->value,
        ];

        $vehicleIds = DB::table('vehicle_types')->whereIn('slug', $vehicleSlugs)->pluck('id', 'slug');
        $vehicleRows = DB::table('place_vehicle_types')
            ->where('place_id', $placeId)->where('is_active', true)->whereNull('version_valid_until')
            ->get(['vehicle_type_id', 'capacity'])->keyBy('vehicle_type_id');

        foreach ($vehicleSlugs as $slug) {
            $vehicleId = $vehicleIds->get($slug);
            $vehicle = $vehicleId ? $vehicleRows->get($vehicleId) : null;
            $values['suitable_'.$slug] = $vehicle
                ? ($vehicle->capacity !== null ? (int) $vehicle->capacity : 'yes')
                : null;
        }

        return $values;
    }

    private function stageRecord(int $sourceId, array $preparedRow, array $comparisons): int
    {
        $normalized = [
            'research' => $preparedRow['metadata'],
            'place_id' => $preparedRow['place_id'],
            'proposals' => $preparedRow['proposals'],
            'comparisons' => $comparisons,
        ];
        $json = $this->canonicalJson($normalized);
        $hash = hash('sha256', $json);
        $now = now();

        $existing = DB::table('external_records')
            ->where('external_source_id', $sourceId)
            ->where('external_id', $preparedRow['external_id'])
            ->first();

        if ($existing) {
            DB::table('external_records')->where('id', $existing->id)->update([
                'place_id' => $preparedRow['place_id'],
                'status' => 'active',
                'normalized_data' => $json,
                'normalized_hash' => $hash,
                'last_seen_at' => $now,
                'missing_since' => null,
                'updated_at' => $now,
            ]);
            return (int) $existing->id;
        }

        return (int) DB::table('external_records')->insertGetId([
            'external_source_id' => $sourceId,
            'external_id' => $preparedRow['external_id'],
            'place_id' => $preparedRow['place_id'],
            'status' => 'active',
            'normalized_data' => $json,
            'normalized_hash' => $hash,
            'first_seen_at' => $now,
            'last_seen_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function replaceFields(int $recordId, array $proposals): void
    {
        DB::table('external_record_fields')->where('external_record_id', $recordId)->delete();

        foreach ($proposals as $field => $value) {
            $json = $this->canonicalJson($value);
            DB::table('external_record_fields')->insert([
                'external_record_id' => $recordId,
                'field_key' => $field,
                'value' => $json,
                'value_hash' => hash('sha256', $json),
                'last_seen_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function crossRowConflicts(array $prepared): array
    {
        $values = [];
        foreach ($prepared as $row) {
            foreach ($row['proposals'] as $field => $value) {
                $values[$row['place_id']][$field][$this->canonicalJson($value)] = true;
            }
        }

        $conflicts = [];
        foreach ($values as $placeId => $fields) {
            foreach ($fields as $field => $uniqueValues) {
                if (count($uniqueValues) > 1) {
                    $conflicts[$placeId][$field] = true;
                }
            }
        }

        return $conflicts;
    }

    private function supersedePendingReviews(int $recordId): void
    {
        DB::table('external_import_review_items')
            ->where('external_record_id', $recordId)
            ->where('status', 'pending')
            ->update([
                'status' => 'superseded',
                'resolved_at' => now(),
                'resolution_note' => 'Durch neueren Recherche-Import ersetzt.',
                'updated_at' => now(),
            ]);
    }

    private function externalId(int $placeId, array $metadata): string
    {
        $identity = [
            'place_id' => $placeId,
            'author' => mb_strtolower(trim((string) $metadata['author'])),
            'source_label' => mb_strtolower(trim((string) $metadata['source_label'])),
            'source_url' => trim((string) ($metadata['source_url'] ?? '')),
            'researched_at' => (string) ($metadata['researched_at'] ?? ''),
        ];

        return 'research-'.$placeId.'-'.substr(hash('sha256', $this->canonicalJson($identity)), 0, 24);
    }

    private function ensureSource(): int
    {
        $existing = DB::table('external_sources')->where('slug', 'research-import')->first();
        $values = [
            'name' => 'Recherche-Import',
            'provider' => 'Camperwolf Admin-Recherche',
            'source_type' => 'manual_research',
            'adapter' => self::class,
            'is_active' => true,
            'updated_at' => now(),
        ];

        if ($existing) {
            DB::table('external_sources')->where('id', $existing->id)->update($values);
            return (int) $existing->id;
        }

        return (int) DB::table('external_sources')->insertGetId($values + [
            'slug' => 'research-import',
            'created_at' => now(),
        ]);
    }

    private function startRun(int $sourceId, int $rows, ?string $sha): int
    {
        return (int) DB::table('external_import_runs')->insertGetId([
            'external_source_id' => $sourceId,
            'mode' => 'manual_upload',
            'status' => 'running',
            'started_at' => now(),
            'fetched_at' => now(),
            'source_record_count' => $rows,
            'payload_sha256' => $sha,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function number(string $raw, float $min, float $max, string $field, int $line): float
    {
        $normalized = str_replace(',', '.', $raw);
        if (! is_numeric($normalized) || (float) $normalized < $min || (float) $normalized > $max) {
            throw new RuntimeException(__('admin_imports.errors.research_field_value', ['field' => $field, 'line' => $line]));
        }
        return (float) $normalized;
    }

    private function positiveInteger(string $raw, string $field, int $line): int
    {
        if (! ctype_digit($raw) || (int) $raw < 0) {
            throw new RuntimeException(__('admin_imports.errors.research_field_value', ['field' => $field, 'line' => $line]));
        }
        return (int) $raw;
    }

    private function countryCode(string $raw, int $line): string
    {
        $value = mb_strtoupper($raw);
        if (! preg_match('/^[A-Z]{2}$/', $value)) {
            throw new RuntimeException(__('admin_imports.errors.research_country', ['line' => $line]));
        }
        return $value;
    }

    private function url(string $raw, string $field, int $line): string
    {
        $scheme = mb_strtolower((string) parse_url($raw, PHP_URL_SCHEME));
        if (! filter_var($raw, FILTER_VALIDATE_URL) || ! in_array($scheme, ['http', 'https'], true)) {
            throw new RuntimeException(__('admin_imports.errors.research_url', ['field' => $field, 'line' => $line]));
        }
        return $raw;
    }

    private function equivalent(mixed $a, mixed $b): bool
    {
        if (is_numeric($a) && is_numeric($b)) {
            return (float) $a === (float) $b;
        }
        return trim((string) $a) === trim((string) $b);
    }

    private function canonicalJson(mixed $value): string
    {
        if (is_array($value)) {
            if (array_is_list($value)) {
                $value = array_map(fn ($item) => json_decode($this->canonicalJson($item), true), $value);
            } else {
                ksort($value);
                foreach ($value as $key => $item) {
                    if (is_array($item)) {
                        $value[$key] = json_decode($this->canonicalJson($item), true);
                    }
                }
            }
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION) ?: 'null';
    }
}
