<?php

namespace App\Services\Imports;

use App\Services\ExternalImportGuard;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class RvrCampingSyncService
{
    private const BASE_URL = 'https://geodaten.metropoleruhr.de/poi/poi';

    private const CATEGORIES = [
        'Campingplätze',
        'Dauercampingplätze',
        'Wohnmobilstellplätze',
        'Jugendzeltplätze',
    ];

    public function __construct(
        private readonly RvrCampingMapper $mapper,
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
            $rawById = [];
            $sourceCounts = [];

            foreach (self::CATEGORIES as $category) {
                $items = $this->fetchCategory($category);
                $sourceCounts[$category] = count($items);

                foreach ($items as $item) {
                    $poiId = trim((string) ($item['poi_id'] ?? ''));
                    $gid = trim((string) ($item['gid'] ?? ''));
                    $externalId = $poiId !== '' ? $poiId : ($gid !== '' ? 'gid:'.$gid : '');

                    if ($externalId === '') {
                        continue;
                    }

                    $item['external_id'] = $externalId;

                    if (! isset($rawById[$externalId])) {
                        $rawById[$externalId] = $item;
                        $rawById[$externalId]['categories'] = [];
                        $rawById[$externalId]['category_assignments'] = [];
                    } else {
                        $rawById[$externalId] = $this->fillMissing($rawById[$externalId], $item);
                    }

                    $itemCategory = trim((string) ($item['kategorie'] ?? ''));
                    if ($itemCategory !== '') {
                        $rawById[$externalId]['categories'][] = $itemCategory;
                    }

                    $assignmentKey = implode('|', [
                        (string) ($item['gid'] ?? ''),
                        (string) ($item['kategorie_id'] ?? ''),
                        $itemCategory,
                    ]);
                    $rawById[$externalId]['category_assignments'][$assignmentKey] = [
                        'gid' => $this->text($item['gid'] ?? null),
                        'kategorie_id' => $this->text($item['kategorie_id'] ?? null),
                        'kategorie' => $this->text($item['kategorie'] ?? null),
                        'kategorie_idpfad' => $this->text($item['kategorie_idpfad'] ?? null),
                        'kategorie_pfad' => $this->text($item['kategorie_pfad'] ?? null),
                        'kategorie_status' => $this->text($item['kategorie_status'] ?? null),
                        'ist_erstkategorie' => $this->boolValue($item['ist_erstkategorie'] ?? null),
                    ];
                }
            }

            $mappedById = [];

            foreach ($rawById as $externalId => $item) {
                $item['categories'] = array_values(array_unique($item['categories']));
                $item['category_assignments'] = array_values($item['category_assignments']);
                $mappedById[$externalId] = $this->mapper->map($item);
            }

            $mapped = array_values($mappedById);
            $validation = $this->guard->validate($mapped, [
                'min_records' => 100,
                'max_records' => 500,
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
                'max_drop_ratio' => 0.4,
                'max_growth_factor' => 2.0,
            ], $previousRecordCount > 0 ? $previousRecordCount : null);

            if (! $validation['valid']) {
                throw new RuntimeException(
                    'RVR-POI-Camping-Snapshot-Validierung fehlgeschlagen: '
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
                'eligible_candidates' => count($mapped),
                'source_only' => 0,
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
                'error_code' => 'rvr_camping_sync_failed',
                'error_message' => mb_substr($e->getMessage(), 0, 4000),
                'completed_at' => now(),
                'updated_at' => now(),
            ]);

            throw $e;
        }
    }

    private function fetchCategory(string $category): array
    {
        $response = Http::timeout(60)
            ->retry(2, 750)
            ->get(self::BASE_URL, [
                'service' => 'WFS',
                'version' => '1.1.0',
                'request' => 'GetFeature',
                'typeName' => 'poi_einfach',
                'srsName' => 'EPSG:4326',
                'maxFeatures' => 1000,
                'filter' => $this->categoryFilter($category),
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('RVR POI WFS HTTP '.$response->status().' für '.$category.'.');
        }

        return $this->parseGml($response->body(), $category);
    }

    private function parseGml(string $xml, string $expectedCategory): array
    {
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);

        try {
            $loaded = $document->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS);
            if (! $loaded) {
                throw new RuntimeException('Ungültige RVR-POI-WFS-Antwort für '.$expectedCategory.'.');
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        $xpath = new DOMXPath($document);
        $nodes = $xpath->query("//*[local-name()='featureMember']/*[local-name()='poi_einfach']");

        if ($nodes === false) {
            throw new RuntimeException('RVR-POI-WFS-Features konnten nicht gelesen werden.');
        }

        $items = [];

        foreach ($nodes as $feature) {
            if (! $feature instanceof DOMElement) {
                continue;
            }

            $item = [
                'gid' => $this->childText($xpath, $feature, 'gid'),
                'poi_id' => $this->childText($xpath, $feature, 'poi_id'),
                'name' => $this->childText($xpath, $feature, 'name'),
                'spw_name' => $this->childText($xpath, $feature, 'spw_name'),
                'beschreibung' => $this->childText($xpath, $feature, 'beschreibung'),
                'link' => $this->childText($xpath, $feature, 'link'),
                'ort' => $this->childText($xpath, $feature, 'ort'),
                'plz' => $this->childText($xpath, $feature, 'plz'),
                'strasse' => $this->childText($xpath, $feature, 'strasse'),
                'hausnummer' => $this->childText($xpath, $feature, 'hausnummer'),
                'adresse_zusatz' => $this->childText($xpath, $feature, 'adresse_zusatz'),
                'kreis_id' => $this->childText($xpath, $feature, 'kreis_id'),
                'kreis' => $this->childText($xpath, $feature, 'kreis'),
                'ist_rvr' => $this->childText($xpath, $feature, 'ist_rvr'),
                'hauptkategorie' => $this->childText($xpath, $feature, 'hauptkategorie'),
                'kategorie_id' => $this->childText($xpath, $feature, 'kategorie_id'),
                'kategorie' => $this->childText($xpath, $feature, 'kategorie'),
                'kategorie_idpfad' => $this->childText($xpath, $feature, 'kategorie_idpfad'),
                'kategorie_pfad' => $this->childText($xpath, $feature, 'kategorie_pfad'),
                'kategorie_status' => $this->childText($xpath, $feature, 'kategorie_status'),
                'ist_erstkategorie' => $this->childText($xpath, $feature, 'ist_erstkategorie'),
                'institution' => $this->childText($xpath, $feature, 'institution'),
                'bedeutung_id' => $this->childText($xpath, $feature, 'bedeutung_id'),
                'bedeutung' => $this->childText($xpath, $feature, 'bedeutung'),
                'fachinfodatum' => $this->childText($xpath, $feature, 'fachinfodatum'),
                'ablaufdatum' => $this->childText($xpath, $feature, 'ablaufdatum'),
                'rechts_utm' => $this->childText($xpath, $feature, 'rechts_utm'),
                'hoch_utm' => $this->childText($xpath, $feature, 'hoch_utm'),
                'angelegt_am' => $this->childText($xpath, $feature, 'angelegt_am'),
                'geaendert_am' => $this->childText($xpath, $feature, 'geaendert_am'),
            ];

            if (($item['kategorie'] ?? null) !== $expectedCategory) {
                continue;
            }

            $position = $xpath->query(".//*[local-name()='pos']", $feature)?->item(0);
            if ($position) {
                $coordinates = preg_split('/\s+/', trim($position->textContent)) ?: [];
                $item['latitude'] = isset($coordinates[0]) && is_numeric($coordinates[0])
                    ? (float) $coordinates[0]
                    : null;
                $item['longitude'] = isset($coordinates[1]) && is_numeric($coordinates[1])
                    ? (float) $coordinates[1]
                    : null;
            } else {
                $item['latitude'] = null;
                $item['longitude'] = null;
            }

            $items[] = $item;
        }

        return $items;
    }

    private function categoryFilter(string $category): string
    {
        $escaped = htmlspecialchars($category, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return '<ogc:Filter xmlns:ogc="http://www.opengis.net/ogc">'
            .'<ogc:PropertyIsEqualTo>'
            .'<ogc:PropertyName>kategorie</ogc:PropertyName>'
            .'<ogc:Literal>'.$escaped.'</ogc:Literal>'
            .'</ogc:PropertyIsEqualTo>'
            .'</ogc:Filter>';
    }

    private function childText(DOMXPath $xpath, DOMElement $feature, string $name): ?string
    {
        $node = $xpath->query("./*[local-name()='".$name."']", $feature)?->item(0);

        return $node ? $this->text($node->textContent) : null;
    }

    private function fillMissing(array $base, array $candidate): array
    {
        foreach ($candidate as $key => $value) {
            if (! array_key_exists($key, $base) || $base[$key] === null || $base[$key] === '') {
                $base[$key] = $value;
            }
        }

        return $base;
    }

    private function sourceConfig(): array
    {
        return [
            'slug' => 'rvr-poi-camping',
            'name' => 'RVR POI - Camping',
            'provider' => 'Regionalverband Ruhr',
            'source_type' => 'api',
            'adapter' => RvrCampingMapper::class,
            'base_url' => self::BASE_URL,
            'country_code' => 'DE',
            'attribution_text' => 'Quelle: Regionalverband Ruhr (RVR), POI - Camping.',
            'sync_interval_minutes' => 1440,
            'config' => [
                'feature_type' => 'poi_einfach',
                'categories' => self::CATEGORIES,
                'srs_name' => 'EPSG:4326',
                'dataset_url' => 'https://www.govdata.de/suche/daten/poi-camping17bde',
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

    private function text(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function boolValue(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $value = mb_strtolower(trim((string) $value));

        return match ($value) {
            '1', 'true', 'yes', 'ja' => true,
            '0', 'false', 'no', 'nein' => false,
            default => null,
        };
    }
}
