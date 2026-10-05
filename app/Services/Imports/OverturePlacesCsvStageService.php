<?php

namespace App\Services\Imports;

use App\Services\ExternalImportGuard;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OverturePlacesCsvStageService
{
    public const SOURCE_SLUG = 'overture-places-camping';

    public function __construct(
        private readonly OverturePlacesMapper $mapper,
        private readonly ExternalImportGuard $guard,
        private readonly ExternalRecordStagingService $staging,
    ) {
    }

    public function stage(string $path): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('Overture-CSV wurde nicht gefunden oder ist nicht lesbar: '.$path);
        }

        $source = $this->sourceConfig();
        $sourceId = $this->ensureSourceId($source);
        $previousRecordCount = (int) DB::table('external_records')
            ->where('external_source_id', $sourceId)
            ->where('status', 'active')
            ->count();

        $runId = (int) DB::table('external_import_runs')->insertGetId([
            'external_source_id' => $sourceId,
            'mode' => 'stage',
            'status' => 'running',
            'started_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            [$headers, $rows] = $this->readCsv($path);

            $requiredHeaders = [
                'id',
                'name',
                'category',
                'confidence',
                'operating_status',
                'latitude',
                'longitude',
                'country',
            ];

            $missingHeaders = array_values(array_diff($requiredHeaders, $headers));
            if ($missingHeaders !== []) {
                throw new RuntimeException(
                    'Overture-CSV fehlen Pflichtspalten: '.implode(', ', $missingHeaders),
                );
            }

            $mapped = array_values(array_filter(array_map(
                fn (array $row) => $this->mapper->map($row),
                $rows,
            ), fn (array $record) => in_array(
                $record['place']['suggested_place_type'] ?? null,
                ['campground', 'motorhome-pitch'],
                true,
            )));

            $validation = $this->guard->validate($mapped, [
                'min_records' => 1000,
                'max_records' => 10000,
                'required_fields' => [
                    'external_id',
                    'place.name',
                    'place.latitude',
                    'place.longitude',
                    'place.suggested_place_type',
                ],
                'field_types' => [
                    'external_id' => 'string',
                    'place.name' => 'string',
                    'place.latitude' => 'number',
                    'place.longitude' => 'number',
                    'place.suggested_place_type' => 'string',
                ],
                'max_missing_required_ratio' => 0.0,
                'max_drop_ratio' => 0.5,
                'max_growth_factor' => 2.0,
            ], $previousRecordCount > 0 ? $previousRecordCount : null);

            if (! $validation['valid']) {
                throw new RuntimeException(
                    'Overture-Places-Validierung fehlgeschlagen: '
                    .json_encode($validation['errors'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                );
            }

            $stage = [
                'source_id' => $sourceId,
                'records' => 0,
                'new' => 0,
                'changed' => 0,
                'unchanged' => 0,
                'missing_marked' => 0,
            ];

            foreach (array_chunk($mapped, 100) as $chunk) {
                $chunkStats = $this->staging->stage($chunk, $source, false);

                foreach (['records', 'new', 'changed', 'unchanged'] as $key) {
                    $stage[$key] += (int) ($chunkStats[$key] ?? 0);
                }
            }

            $stage['missing_marked'] = $this->staging->completeSnapshot(
                $sourceId,
                array_column($mapped, 'external_id'),
                $runId,
            );

            $stats = [
                'csv_rows' => count($rows),
                'mapped_records' => count($mapped),
                'staging' => $stage,
            ];

            DB::table('external_import_runs')->where('id', $runId)->update([
                'status' => 'completed',
                'fetched_at' => now(),
                'validated_at' => now(),
                'completed_at' => now(),
                'source_record_count' => count($rows),
                'mapped_record_count' => count($mapped),
                'created_count' => $stage['new'],
                'updated_count' => $stage['changed'],
                'skipped_count' => $stage['unchanged'],
                'review_count' => 0,
                'validation_report' => json_encode($validation, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'stats' => json_encode($stats, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => now(),
            ]);

            return $stats + ['run_id' => $runId, 'source_id' => $sourceId];
        } catch (\Throwable $e) {
            DB::table('external_import_runs')->where('id', $runId)->update([
                'status' => 'failed',
                'error_code' => 'overture_places_stage_failed',
                'error_message' => mb_substr($e->getMessage(), 0, 4000),
                'completed_at' => now(),
                'updated_at' => now(),
            ]);

            throw $e;
        }
    }

    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Overture-CSV konnte nicht geöffnet werden.');
        }

        try {
            $headers = fgetcsv($handle, 0, ',', '"', '');
            if (! is_array($headers) || $headers === []) {
                throw new RuntimeException('Overture-CSV enthält keinen lesbaren Header.');
            }

            $headers = array_map(
                static fn ($value) => trim((string) $value, "\xEF\xBB\xBF \t\n\r\0\x0B"),
                $headers,
            );

            $rows = [];

            while (($values = fgetcsv($handle, 0, ',', '"', '')) !== false) {
                if ($values === [null] || $values === []) {
                    continue;
                }

                if (count($values) !== count($headers)) {
                    throw new RuntimeException(
                        'Overture-CSV enthält eine Zeile mit '.count($values)
                        .' statt '.count($headers).' Spalten.',
                    );
                }

                $row = array_combine($headers, $values);
                if ($row !== false) {
                    $rows[] = $row;
                }
            }

            return [$headers, $rows];
        } finally {
            fclose($handle);
        }
    }

    private function sourceConfig(): array
    {
        return [
            'slug' => self::SOURCE_SLUG,
            'name' => 'Overture Maps Places - Camping',
            'provider' => 'Overture Maps Foundation',
            'source_type' => 'open-data',
            'adapter' => OverturePlacesMapper::class,
            'base_url' => 'https://overturemaps.org/',
            'country_code' => 'DE',
            'license_code' => 'mixed-permissive',
            'license_name' => 'Source-specific permissive licenses (preserved per record)',
            'attribution_text' => 'Quelle: Overture Maps Places; Upstream-Quellen und Lizenzen werden je Datensatz gespeichert.',
            'sync_interval_minutes' => 43200,
            'config' => [
                'release' => '2026-09-23.1',
                'categories' => ['campground', 'rv_park', 'holiday_park'],
                'import_mode' => 'csv-research-stage',
                'license_note' => 'Record-level Overture sources[] metadata is preserved in normalized_data.',
            ],
        ];
    }

    private function ensureSourceId(array $source): int
    {
        $existing = DB::table('external_sources')->where('slug', $source['slug'])->value('id');

        if ($existing) {
            return (int) $existing;
        }

        $stats = $this->staging->stage([], $source, false);

        return (int) $stats['source_id'];
    }
}
