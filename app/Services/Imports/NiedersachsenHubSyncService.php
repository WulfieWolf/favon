<?php

namespace App\Services\Imports;

use App\Services\ExternalImportGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class NiedersachsenHubSyncService
{
    private const ENDPOINT = 'https://meta.et4.de/rest.ashx/search/';

    private const CATEGORIES = [
        'Wohnmobilstellplatz',
        'Campingplatz',
        'Zelten',
    ];

    public function __construct(
        private readonly NiedersachsenHubMapper $mapper,
        private readonly ExternalImportGuard $guard,
        private readonly ExternalRecordStagingService $staging,
        private readonly ExternalRecordClassificationService $classifier,
        private readonly ExternalOperatingStatusBackfillService $operatingStatusBackfill,
        private readonly ExternalReviewSignalService $reviewSignals,
        private readonly ExternalPlacePhotoService $photos,
    ) {
    }

    public function sync(bool $classify = true, int $pageSize = 100): array
    {
        $key = (string) config('services.niedersachsen_hub.key');
        $experience = (string) config('services.niedersachsen_hub.experience');

        if ($key === '' || $experience === '') {
            throw new RuntimeException('Niedersachsen-Hub-Konfiguration fehlt.');
        }

        $pageSize = max(1, min(250, $pageSize));
        $source = $this->sourceConfig();
        $sourceId = $this->ensureSourceId($source);

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

            foreach (self::CATEGORIES as $category) {
                $items = $this->fetchCategory($category, $key, $experience, $pageSize);
                $sourceCounts[$category] = count($items);

                foreach ($items as $item) {
                    $mapped = $this->mapper->map($item);
                    $externalId = $mapped['external_id'] ?? null;

                    if (is_string($externalId) && $externalId !== '') {
                        $mappedById[$externalId] = $mapped;
                    }
                }
            }

            $mapped = array_values($mappedById);
            $validation = $this->guard->validate($mapped, [
                'min_records' => 1,
                'max_records' => 10000,
                'required_fields' => ['external_id'],
                'field_types' => ['external_id' => 'string'],
                'max_missing_required_ratio' => 0.0,
            ]);

            if (! $validation['valid']) {
                throw new RuntimeException(
                    'Niedersachsen-Snapshot-Validierung fehlgeschlagen: '
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
                foreach (['records', 'new', 'changed', 'unchanged'] as $keyName) {
                    $stage[$keyName] += (int) ($chunkStats[$keyName] ?? 0);
                }
            }

            $stage['missing_marked'] = $this->staging->completeSnapshot(
                $sourceId,
                array_keys($mappedById),
                $runId,
            );

            $operatingStatusFilled = $this->operatingStatusBackfill->fillUnknownForSource(
                $sourceId,
                $mappedById,
            );

            $classification = $classify
                ? $this->classifier->classifySource($sourceId)
                : null;

            $possibleReopens = $this->reviewSignals->queuePossibleReopens($sourceId, $runId);

            $photoStats = $this->photos->inventoryLinkedSource($sourceId);

            $stats = [
                'source_counts' => $sourceCounts,
                'unique_records' => count($mapped),
                'staging' => $stage,
                'classification' => $classification,
                'operating_status_filled' => $operatingStatusFilled,
                'possible_reopens' => $possibleReopens,
                'photos' => $photoStats,
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
                'error_code' => 'niedersachsen_sync_failed',
                'error_message' => mb_substr($e->getMessage(), 0, 4000),
                'completed_at' => now(),
                'updated_at' => now(),
            ]);

            throw $e;
        }
    }

    private function fetchCategory(string $category, string $key, string $experience, int $pageSize): array
    {
        $items = [];
        $seen = [];
        $offset = 0;
        $overall = null;

        do {
            $response = Http::timeout(45)
                ->retry(2, 750)
                ->get(self::ENDPOINT, [
                    'experience' => $experience,
                    'licensekey' => $key,
                    'type' => 'Hotel',
                    'q' => 'category:"'.$category.'"',
                    'template' => 'ET2014A.json',
                    'limit' => $pageSize,
                    'offset' => $offset,
                    'facets' => 'false',
                ]);

            if (! $response->successful()) {
                throw new RuntimeException('Niedersachsen Hub HTTP '.$response->status().' für '.$category.'.');
            }

            $payload = $response->json();
            if (! is_array($payload) || ($payload['status'] ?? null) !== 'OK') {
                throw new RuntimeException('Ungültige Niedersachsen-Hub-Antwort für '.$category.'.');
            }

            $overall ??= (int) ($payload['overallcount'] ?? 0);
            $page = is_array($payload['items'] ?? null) ? $payload['items'] : [];
            $newOnPage = 0;

            foreach ($page as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $id = (string) ($item['global_id'] ?? $item['id'] ?? '');
                if ($id === '' || isset($seen[$id])) {
                    continue;
                }

                $seen[$id] = true;
                $items[] = $item;
                $newOnPage++;
            }

            if ($page === [] || count($items) >= $overall) {
                break;
            }

            if ($newOnPage === 0) {
                throw new RuntimeException(
                    'Niedersachsen-Hub-Paginierung lieferte keine neuen Datensätze für '.$category
                    .'. Snapshot wird nicht als vollständig behandelt.',
                );
            }

            $offset += $pageSize;
        } while ($offset < 10000);

        if ($overall !== null && count($items) < $overall) {
            throw new RuntimeException(
                'Niedersachsen-Snapshot für '.$category.' unvollständig: '
                .count($items).' von '.$overall.' Datensätzen.',
            );
        }

        return $items;
    }

    private function sourceConfig(): array
    {
        return [
            'slug' => 'niedersachsen-hub-destination-one',
            'name' => 'Niedersachsen Hub – destination.one',
            'provider' => 'Niedersachsen Hub / destination.one',
            'source_type' => 'api',
            'adapter' => NiedersachsenHubMapper::class,
            'base_url' => self::ENDPOINT,
            'country_code' => 'DE',
            'sync_interval_minutes' => 1440,
            'attribution_text' => 'Quelle: Niedersachsen Hub / destination.one; Lizenz je Datensatz.',
            'config' => [
                'experience' => config('services.niedersachsen_hub.experience'),
                'content_type' => 'Hotel',
                'categories' => self::CATEGORIES,
            ],
        ];
    }

    private function ensureSourceId(array $source): int
    {
        $existing = DB::table('external_sources')->where('slug', $source['slug'])->value('id');
        if ($existing) {
            return (int) $existing;
        }

        // Let the established staging service create the source consistently.
        $stats = $this->staging->stage([], $source, false);

        return (int) $stats['source_id'];
    }
}
