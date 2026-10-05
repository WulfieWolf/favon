<?php

namespace App\Services\Imports;

use App\Services\ExternalImportGuard;
use DOMDocument;
use DOMNode;
use DOMXPath;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BayernAtkisSyncService
{
    private const BASE_URL = BayernAtkisCatalogAnalysisService::BASE_URL;
    private const PAGE_SIZE = 5000;

    private const FEATURE_TYPES = [
        'adv:AX_Platz',
        'adv:AX_SportFreizeitUndErholungsflaeche',
    ];

    public function __construct(
        private readonly BayernAtkisMapper $mapper,
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

            foreach (self::FEATURE_TYPES as $featureType) {
                $items = $this->fetchType($featureType);
                $counts = [];

                foreach ($items as $item) {
                    $function = (string) ($item['function'] ?? '');
                    if (! $this->mapper->supports($featureType, $function)) {
                        continue;
                    }

                    $counts[$function] = ($counts[$function] ?? 0) + 1;
                    $mapped = $this->mapper->map($item);
                    $externalId = $mapped['external_id'] ?? null;

                    if (is_string($externalId) && $externalId !== '') {
                        $mappedById[$externalId] = $mapped;
                    }
                }

                $sourceCounts[$featureType] = $counts;
            }

            $mapped = array_values($mappedById);
            $validation = $this->guard->validate($mapped, [
                'min_records' => 5000,
                'max_records' => 12000,
                'required_fields' => ['external_id', 'place.latitude', 'place.longitude', 'place.suggested_place_type'],
                'field_types' => [
                    'external_id' => 'string',
                    'place.latitude' => 'number',
                    'place.longitude' => 'number',
                    'place.suggested_place_type' => 'string',
                ],
                'max_missing_required_ratio' => 0.0,
                'max_drop_ratio' => 0.25,
                'max_growth_factor' => 2.0,
            ], $previousRecordCount > 0 ? $previousRecordCount : null);

            if (! $validation['valid']) {
                throw new RuntimeException(
                    'Bayern-ATKIS-Snapshot-Validierung fehlgeschlagen: '
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
                'duplicate_groups' => $classification['duplicate_groups'] ?? null,
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
                'error_code' => 'bayern_atkis_sync_failed',
                'error_message' => mb_substr($e->getMessage(), 0, 4000),
                'completed_at' => now(),
                'updated_at' => now(),
            ]);

            throw $e;
        }
    }

    public function diagnoseChanges(int $limit = 30): array
    {
        $sourceId = (int) DB::table('external_sources')
            ->where('slug', 'bayern-atkis-basis-dlm')
            ->value('id');

        if ($sourceId <= 0) {
            throw new RuntimeException('Bayern-ATKIS-Quelle ist lokal noch nicht vorhanden.');
        }

        $mappedById = [];

        foreach (self::FEATURE_TYPES as $featureType) {
            foreach ($this->fetchType($featureType) as $item) {
                $function = (string) ($item['function'] ?? '');
                if (! $this->mapper->supports($featureType, $function)) {
                    continue;
                }

                $mapped = $this->mapper->map($item);
                $externalId = (string) ($mapped['external_id'] ?? '');

                if ($externalId !== '') {
                    $mappedById[$externalId] = $mapped;
                }
            }
        }

        $changed = [];

        foreach ($mappedById as $externalId => $mapped) {
            $existing = DB::table('external_records')
                ->where('external_source_id', $sourceId)
                ->where('external_id', $externalId)
                ->first(['normalized_hash', 'normalized_data']);

            if (! $existing) {
                continue;
            }

            $currentJson = $this->canonicalJson($mapped);
            $currentHash = hash('sha256', $currentJson);

            if ((string) $existing->normalized_hash === $currentHash) {
                continue;
            }

            $previous = json_decode((string) $existing->normalized_data, true);
            $current = json_decode($currentJson, true);

            $changed[] = [
                'external_id' => $externalId,
                'name' => $mapped['place']['name'] ?? null,
                'feature_type' => $mapped['source_properties']['feature_type'] ?? null,
                'function' => $mapped['source_properties']['function'] ?? null,
                'diff_paths' => $this->diffPaths($previous, $current),
            ];

            if (count($changed) >= max(1, $limit)) {
                break;
            }
        }

        return [
            'checked' => count($mappedById),
            'changed_examples' => $changed,
        ];
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

    private function diffPaths(mixed $before, mixed $after, string $path = ''): array
    {
        if (! is_array($before) || ! is_array($after)) {
            return $before === $after ? [] : [$path !== '' ? $path : '(root)'];
        }

        $paths = [];
        $keys = array_unique(array_merge(array_keys($before), array_keys($after)));

        foreach ($keys as $key) {
            $childPath = $path === '' ? (string) $key : $path.'.'.$key;

            if (! array_key_exists($key, $before) || ! array_key_exists($key, $after)) {
                $paths[] = $childPath;
                continue;
            }

            $paths = array_merge($paths, $this->diffPaths($before[$key], $after[$key], $childPath));
        }

        return array_values(array_unique($paths));
    }

    public function samples(int $perFunction = 10): array
    {
        $perFunction = max(1, min(50, $perFunction));
        $samples = [];

        foreach (self::FEATURE_TYPES as $featureType) {
            $items = $this->fetchType($featureType);

            foreach ($items as $item) {
                $function = (string) ($item['function'] ?? '');
                if (! $this->mapper->supports($featureType, $function)) {
                    continue;
                }

                $name = trim((string) ($item['name'] ?? ''));
                if ($name === '') {
                    $secondNames = is_array($item['second_names'] ?? null) ? $item['second_names'] : [];
                    $name = trim((string) ($secondNames[0] ?? ''));
                }

                if ($name === '') {
                    continue;
                }

                $key = $featureType.'|'.$function;
                $samples[$key] ??= [];

                if (count($samples[$key]) >= $perFunction) {
                    continue;
                }

                $mapped = $this->mapper->map($item);
                $lat = $mapped['place']['latitude'] ?? null;
                $lon = $mapped['place']['longitude'] ?? null;

                $samples[$key][] = [
                    'feature_type' => $featureType,
                    'function' => $function,
                    'camperwolf_type' => $mapped['place']['suggested_place_type'] ?? null,
                    'external_id' => $mapped['external_id'] ?? null,
                    'name' => $mapped['place']['name'] ?? null,
                    'latitude' => $lat,
                    'longitude' => $lon,
                    'google_maps_url' => is_numeric($lat) && is_numeric($lon)
                        ? 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($lat.','.$lon)
                        : null,
                ];
            }
        }

        ksort($samples);

        return $samples;
    }

    private function fetchType(string $featureType): array
    {
        $items = [];
        $seen = [];
        $startIndex = 0;
        $numberMatched = null;

        do {
            $response = Http::timeout(90)
                ->retry(2, 1000)
                ->get(self::BASE_URL, [
                    'service' => 'WFS',
                    'request' => 'GetFeature',
                    'version' => '2.0.0',
                    'typeNames' => $featureType,
                    'count' => self::PAGE_SIZE,
                    'startIndex' => $startIndex,
                    'srsName' => 'EPSG:4326',
                ]);

            if (! $response->successful()) {
                throw new RuntimeException('Bayern ATKIS WFS HTTP '.$response->status().' für '.$featureType.'.');
            }

            $dom = $this->xml($response->body());
            $numberMatched ??= $this->numberMatched($dom);
            $page = $this->extractItems($dom, $featureType);
            $newOnPage = 0;

            foreach ($page as $item) {
                $id = (string) ($item['external_id'] ?? '');
                if ($id === '' || isset($seen[$id])) {
                    continue;
                }

                $seen[$id] = true;
                $items[] = $item;
                $newOnPage++;
            }

            if ($page === []) {
                break;
            }

            if ($newOnPage === 0) {
                throw new RuntimeException(
                    'Bayern-ATKIS-Paginierung lieferte keine neuen Datensätze für '.$featureType.'.',
                );
            }

            $startIndex += count($page);
        } while ($numberMatched === null || $startIndex < $numberMatched);

        if ($numberMatched !== null && count($items) < $numberMatched) {
            throw new RuntimeException(
                'Bayern-ATKIS-Snapshot für '.$featureType.' unvollständig: '
                .count($items).' von '.$numberMatched.' Datensätzen.',
            );
        }

        return $items;
    }

    private function extractItems(DOMDocument $dom, string $featureType): array
    {
        $xpath = new DOMXPath($dom);
        $localType = str_contains($featureType, ':') ? explode(':', $featureType, 2)[1] : $featureType;
        $nodes = $xpath->query('//*[local-name()="'.$localType.'"]') ?: [];
        $items = [];

        foreach ($nodes as $node) {
            $id = trim((string) $node->attributes?->getNamedItemNS('http://www.opengis.net/gml/3.2', 'id')?->nodeValue);
            $function = $this->childText($xpath, $node, 'funktion');
            [$latitude, $longitude] = $this->representativePoint($xpath, $node);

            $items[] = [
                'external_id' => $id,
                'feature_type' => $featureType,
                'function' => $function,
                'name' => $this->featureName($xpath, $node),
                'second_names' => $this->childTexts($xpath, $node, 'zweitname'),
                'source_updated_at' => $this->childText($xpath, $node, 'datumDerLetztenUeberpruefung'),
                'condition' => $this->childText($xpath, $node, 'zustand'),
                'latitude' => $latitude,
                'longitude' => $longitude,
            ];
        }

        return $items;
    }

    private function representativePoint(DOMXPath $xpath, DOMNode $node): array
    {
        $pairs = [];

        foreach ($xpath->query('.//*[local-name()="posList"]', $node) ?: [] as $posList) {
            $values = preg_split('/\s+/', trim((string) $posList->textContent)) ?: [];

            for ($i = 0; $i + 1 < count($values); $i += 2) {
                if (! is_numeric($values[$i]) || ! is_numeric($values[$i + 1])) {
                    continue;
                }

                $pair = $this->bavariaCoordinatePair((float) $values[$i], (float) $values[$i + 1]);
                if ($pair !== null) {
                    $pairs[] = $pair;
                }
            }
        }

        foreach ($xpath->query('.//*[local-name()="pos"]', $node) ?: [] as $pos) {
            $values = preg_split('/\s+/', trim((string) $pos->textContent)) ?: [];
            if (count($values) < 2 || ! is_numeric($values[0]) || ! is_numeric($values[1])) {
                continue;
            }

            $pair = $this->bavariaCoordinatePair((float) $values[0], (float) $values[1]);
            if ($pair !== null) {
                $pairs[] = $pair;
            }
        }

        if ($pairs === []) {
            return [null, null];
        }

        $unique = [];
        foreach ($pairs as [$lat, $lon]) {
            $key = sprintf('%.8F,%.8F', $lat, $lon);
            $unique[$key] = [(float) number_format($lat, 8, '.', ''), (float) number_format($lon, 8, '.', '')];
        }

        ksort($unique);
        $pairs = array_values($unique);

        return [
            round(array_sum(array_column($pairs, 0)) / count($pairs), 7),
            round(array_sum(array_column($pairs, 1)) / count($pairs), 7),
        ];
    }

    private function bavariaCoordinatePair(float $first, float $second): ?array
    {
        if ($first >= 47.0 && $first <= 51.0 && $second >= 8.0 && $second <= 14.5) {
            return [$first, $second];
        }

        if ($second >= 47.0 && $second <= 51.0 && $first >= 8.0 && $first <= 14.5) {
            return [$second, $first];
        }

        return null;
    }

    private function featureName(DOMXPath $xpath, DOMNode $node): ?string
    {
        $nameNode = $xpath->query('./*[local-name()="name"]', $node)?->item(0);
        if (! $nameNode) {
            return null;
        }

        $plain = $xpath->query('.//*[local-name()="unverschluesselt"]', $nameNode)?->item(0);
        $value = trim((string) ($plain?->textContent ?: $nameNode->textContent));

        return $value !== '' ? $value : null;
    }

    private function childText(DOMXPath $xpath, DOMNode $node, string $name): ?string
    {
        $child = $xpath->query('./*[local-name()="'.$name.'"]', $node)?->item(0);
        $value = trim((string) $child?->textContent);

        return $value !== '' ? $value : null;
    }

    private function childTexts(DOMXPath $xpath, DOMNode $node, string $name): array
    {
        $values = [];

        foreach ($xpath->query('./*[local-name()="'.$name.'"]', $node) ?: [] as $child) {
            $value = trim((string) $child->textContent);
            if ($value !== '') {
                $values[] = $value;
            }
        }

        return array_values(array_unique($values));
    }

    private function numberMatched(DOMDocument $dom): ?int
    {
        $value = $dom->documentElement?->getAttribute('numberMatched');

        return is_numeric($value) ? (int) $value : null;
    }

    private function xml(string $xml): DOMDocument
    {
        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);

        try {
            if (! $dom->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS)) {
                $error = collect(libxml_get_errors())->first();
                throw new RuntimeException(
                    'Bayern ATKIS WFS XML konnte nicht gelesen werden: '
                    .trim((string) ($error?->message ?? 'unbekannter XML-Fehler')),
                );
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        return $dom;
    }

    private function sourceConfig(): array
    {
        return [
            'slug' => 'bayern-atkis-basis-dlm',
            'name' => 'Bayern ATKIS Basis-DLM',
            'provider' => 'Bayerische Vermessungsverwaltung',
            'source_type' => 'api',
            'adapter' => BayernAtkisMapper::class,
            'base_url' => self::BASE_URL,
            'country_code' => 'DE',
            'license_code' => 'cc-by-4.0',
            'license_name' => 'Creative Commons Attribution 4.0 International',
            'license_url' => 'https://creativecommons.org/licenses/by/4.0/',
            'attribution_text' => 'Datenquelle: Bayerische Vermessungsverwaltung – www.geodaten.bayern.de; bearbeitet für Camperwolf: Flächengeometrie auf Repräsentativpunkt reduziert.',
            'sync_interval_minutes' => 1440,
            'config' => [
                'feature_types' => self::FEATURE_TYPES,
                'function_map' => $this->mapper->relevantFunctions(),
                'geometry_transformation' => 'Surface geometry reduced to representative point.',
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
