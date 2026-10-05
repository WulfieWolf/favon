<?php

namespace App\Services\Imports;

use App\Services\ExternalImportGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class NrwTfisSyncService
{
    private const BASE_URL = 'https://ogc-api.nrw.de/tfis/v1';

    private const COLLECTIONS = [
        'unterkunft_rast' => ['Campingplatz'],
        'verkehr' => ['Parkplatz', 'Wanderparkplatz'],
    ];

    public function __construct(
        private readonly NrwTfisMapper $mapper,
        private readonly ExternalImportGuard $guard,
        private readonly ExternalRecordStagingService $staging,
        private readonly ExternalRecordClassificationService $classifier,
        private readonly ExternalOperatingStatusBackfillService $operatingStatusBackfill,
        private readonly ExternalReviewSignalService $reviewSignals,
    ) {
    }

    public function sync(bool $classify = true): array
    {
        $source = $this->sourceConfig();
        $sourceId = $this->ensureSourceId($source);
        $previousRecordCount = (int) DB::table('external_records')
            ->where('external_source_id', $sourceId)
            ->where('status', 'active')
            ->count();

        $runId = (int) DB::table('external_import_runs')->insertGetId([
            'external_source_id' => $sourceId,
            'mode' => 'sync',
            'status' => 'running',
            'started_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            $mappedById = [];
            $sourceCounts = [];

            foreach (self::COLLECTIONS as $collection => $allowedFunctions) {
                $features = $this->fetchCollection($collection);
                $counts = [];

                foreach ($features as $feature) {
                    if (! is_array($feature)) {
                        continue;
                    }

                    $properties = is_array($feature['properties'] ?? null) ? $feature['properties'] : [];
                    $function = trim((string) ($properties['fkt'] ?? ''));

                    if (! in_array($function, $allowedFunctions, true)) {
                        continue;
                    }

                    $counts[$function] = ($counts[$function] ?? 0) + 1;
                    $mapped = $this->mapper->map($feature);
                    $externalId = $mapped['external_id'] ?? null;

                    if (is_string($externalId) && $externalId !== '') {
                        $mappedById[$externalId] = $mapped;
                    }
                }

                $sourceCounts[$collection] = $counts;
            }

            $mapped = array_values($mappedById);
            $validation = $this->guard->validate($mapped, [
                'min_records' => 1000,
                'max_records' => 10000,
                'required_fields' => ['external_id', 'place.latitude', 'place.longitude'],
                'field_types' => [
                    'external_id' => 'string',
                    'place.latitude' => 'number',
                    'place.longitude' => 'number',
                ],
                'max_missing_required_ratio' => 0.0,
                'max_drop_ratio' => 0.25,
                'max_growth_factor' => 2.0,
            ], $previousRecordCount > 0 ? $previousRecordCount : null);

            if (! $validation['valid']) {
                throw new RuntimeException(
                    'NRW-TFIS-Snapshot-Validierung fehlgeschlagen: '
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
                array_keys($mappedById),
                $runId,
            );

            $eligibleCandidates = count(array_filter(
                $mapped,
                fn (array $record): bool => trim((string) ($record['place']['name'] ?? '')) !== '',
            ));

            $operatingStatusFilled = $this->operatingStatusBackfill->fillUnknownForSource(
                $sourceId,
                $mappedById,
            );

            $classification = $classify
                ? $this->classifier->classifySource($sourceId, true)
                : null;

            $possibleReopens = $this->reviewSignals->queuePossibleReopens($sourceId, $runId);

            $stats = [
                'source_counts' => $sourceCounts,
                'unique_records' => count($mapped),
                'eligible_candidates' => $eligibleCandidates,
                'source_only' => count($mapped) - $eligibleCandidates,
                'staging' => $stage,
                'classification' => $classification,
                'operating_status_filled' => $operatingStatusFilled,
                'possible_reopens' => $possibleReopens,
            ];

            DB::table('external_import_runs')->where('id', $runId)->update([
                'status' => 'completed',
                'fetched_at' => now(),
                'validated_at' => now(),
                'completed_at' => now(),
                'source_record_count' => count($mapped),
                'mapped_record_count' => count($mapped),
                'created_count' => $stage['new'],
                'updated_count' => $stage['changed'],
                'skipped_count' => $stage['unchanged'],
                'review_count' => (int) ($classification['review_items'] ?? 0),
                'validation_report' => json_encode($validation, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'stats' => json_encode($stats, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => now(),
            ]);

            return $stats + ['run_id' => $runId];
        } catch (\Throwable $e) {
            DB::table('external_import_runs')->where('id', $runId)->update([
                'status' => 'failed',
                'error_code' => 'nrw_tfis_sync_failed',
                'error_message' => mb_substr($e->getMessage(), 0, 4000),
                'completed_at' => now(),
                'updated_at' => now(),
            ]);

            throw $e;
        }
    }

    private function fetchCollection(string $collection): array
    {
        $response = Http::timeout(60)
            ->retry(2, 750)
            ->get(self::BASE_URL.'/collections/'.$collection.'/items', [
                'f' => 'json',
                'limit' => 10000,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('NRW TFIS HTTP '.$response->status().' für '.$collection.'.');
        }

        $payload = $response->json();

        if (! is_array($payload) || ! is_array($payload['features'] ?? null)) {
            throw new RuntimeException('Ungültige NRW-TFIS-Antwort für '.$collection.'.');
        }

        $features = $payload['features'];
        $numberMatched = isset($payload['numberMatched']) && is_numeric($payload['numberMatched'])
            ? (int) $payload['numberMatched']
            : null;

        if ($numberMatched !== null && count($features) < $numberMatched) {
            throw new RuntimeException(
                'NRW-TFIS-Snapshot für '.$collection.' unvollständig: '
                .count($features).' von '.$numberMatched.' Datensätzen.',
            );
        }

        return $features;
    }

    private function sourceConfig(): array
    {
        return [
            'slug' => 'nrw-tfis',
            'name' => 'NRW TFIS',
            'provider' => 'Geobasis NRW / TFIS NRW',
            'source_type' => 'api',
            'adapter' => NrwTfisMapper::class,
            'base_url' => self::BASE_URL,
            'country_code' => 'DE',
            'license_code' => 'dl-de-zero-2.0',
            'license_name' => 'Datenlizenz Deutschland - Zero - Version 2.0',
            'license_url' => 'https://www.govdata.de/dl-de/zero-2-0',
            'attribution_text' => 'Quelle: TFIS NRW / Geobasis NRW.',
            'sync_interval_minutes' => 1440,
            'config' => [
                'collections' => self::COLLECTIONS,
                'public_candidate_name_fields' => ['nam', 'info_ext'],
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
