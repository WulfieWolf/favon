<?php

namespace App\Services\Imports;

use App\Services\ExternalImportGuard;
use RuntimeException;

class Datex2ParkingStageService
{
    public function __construct(
        private readonly Datex2ParkingParser $parser,
        private readonly Datex2ParkingMapper $mapper,
        private readonly ExternalImportGuard $guard,
        private readonly ExternalRecordStagingService $staging,
        private readonly Datex2ParkingCandidateService $candidates,
    ) {
    }

    public function stagePath(string $path, bool $completeSnapshot = false, bool $analyzeCandidates = true): array
    {
        $files = $this->resolveXmlFiles($path);

        if ($files === []) {
            throw new RuntimeException(__('admin_imports.errors.datex_no_xml'));
        }

        $validationRecords = [];
        $seenIds = [];
        $duplicateExternalIds = [];

        // Pass 1: fail-closed validation with only the minimal fields kept
        // in memory. The complete XML structure is never retained.
        foreach ($files as $file) {
            foreach ($this->parser->parseFile($file) as $record) {
                $externalId = $record['external_id'] ?? null;
                $name = $record['name'] ?? null;

                $validationRecords[] = [
                    'external_id' => $externalId,
                    'name' => $name,
                ];

                if (! is_string($externalId) || $externalId === '') {
                    continue;
                }

                if (isset($seenIds[$externalId])) {
                    $duplicateExternalIds[$externalId] = ($duplicateExternalIds[$externalId] ?? 1) + 1;
                    continue;
                }

                $seenIds[$externalId] = true;
            }
        }

        if ($duplicateExternalIds !== []) {
            throw new RuntimeException(
                'Doppelte externe IDs im Snapshot gefunden: '.implode(', ', array_slice(array_keys($duplicateExternalIds), 0, 20)),
            );
        }

        $validation = $this->guard->validate($validationRecords, [
            'min_records' => 1,
            'max_records' => 100000,
            'required_fields' => ['external_id', 'name'],
            'field_types' => [
                'external_id' => 'string',
                'name' => 'string',
            ],
            'max_missing_required_ratio' => 0.01,
        ]);

        unset($validationRecords);

        if (! $validation['valid']) {
            throw new RuntimeException(
                'Snapshot-Validierung fehlgeschlagen: '.json_encode($validation['errors'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            );
        }

        $unmappedEquipment = [];
        $fallbackCoordinates = 0;
        $withoutCoordinates = 0;
        $candidateRecords = 0;
        $candidatePairs = 0;
        $candidateExamples = [];
        $mappedCount = 0;
        $sourceId = null;
        $stageStats = [
            'source_id' => null,
            'records' => 0,
            'new' => 0,
            'changed' => 0,
            'unchanged' => 0,
            'missing_marked' => 0,
        ];
        $chunk = [];

        $flush = function () use (&$chunk, &$sourceId, &$stageStats): void {
            if ($chunk === []) {
                return;
            }

            $stats = $this->staging->stageDatexParking($chunk, false);
            $sourceId ??= (int) $stats['source_id'];
            $stageStats['source_id'] = $sourceId;
            $stageStats['records'] += (int) ($stats['records'] ?? 0);
            $stageStats['new'] += (int) ($stats['new'] ?? 0);
            $stageStats['changed'] += (int) ($stats['changed'] ?? 0);
            $stageStats['unchanged'] += (int) ($stats['unchanged'] ?? 0);
            $chunk = [];
        };

        // Pass 2: map and persist in bounded chunks. At no point do we retain
        // all normalized records in PHP memory.
        foreach ($files as $file) {
            foreach ($this->parser->parseFile($file) as $record) {
                $externalId = $record['external_id'] ?? null;
                if (! is_string($externalId) || $externalId === '') {
                    continue;
                }

                $item = $this->mapper->map($record);

                $coordinateSource = $item['place']['coordinate_source'] ?? null;
                if ($coordinateSource !== null && $coordinateSource !== 'parking_location') {
                    $fallbackCoordinates++;
                }
                if (($item['place']['latitude'] ?? null) === null || ($item['place']['longitude'] ?? null) === null) {
                    $withoutCoordinates++;
                }

                foreach ($item['unmapped_equipment'] ?? [] as $type => $count) {
                    $unmappedEquipment[$type] = ($unmappedEquipment[$type] ?? 0) + $count;
                }

                if ($analyzeCandidates) {
                    $matches = $this->candidates->candidates($item);
                    if ($matches !== []) {
                        $candidateRecords++;
                        $candidatePairs += count($matches);

                        if (count($candidateExamples) < 20) {
                            $candidateExamples[] = [
                                'external_id' => $externalId,
                                'external_name' => $item['place']['name'] ?? null,
                                'candidates' => $matches,
                            ];
                        }
                    }
                }

                $chunk[] = $item;
                $mappedCount++;

                if (count($chunk) >= 100) {
                    $flush();
                }
            }
        }

        $flush();

        if ($sourceId === null) {
            throw new RuntimeException(__('admin_imports.errors.datex_no_valid_records'));
        }

        if ($completeSnapshot) {
            $stageStats['missing_marked'] = $this->staging->completeSnapshot(
                $sourceId,
                array_keys($seenIds),
            );
        }

        arsort($unmappedEquipment);

        return [
            'files' => $files,
            'mapped_records' => $mappedCount,
            'fallback_coordinates' => $fallbackCoordinates,
            'without_coordinates' => $withoutCoordinates,
            'unmapped_equipment' => $unmappedEquipment,
            'candidate_records' => $candidateRecords,
            'candidate_pairs' => $candidatePairs,
            'candidate_examples' => $candidateExamples,
            'staging' => $stageStats,
            'complete_snapshot' => $completeSnapshot,
            'validation' => $validation,
        ];
    }

    private function resolveXmlFiles(string $path): array
    {
        $path = rtrim($path, "\\/");

        if (is_file($path)) {
            if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'xml') {
                throw new RuntimeException(__('admin_imports.errors.datex_not_xml'));
            }

            return [$path];
        }

        if (! is_dir($path)) {
            throw new RuntimeException(__('admin_imports.errors.datex_path_missing', ['path' => $path]));
        }

        $files = glob($path.DIRECTORY_SEPARATOR.'*.{xml,XML}', GLOB_BRACE) ?: [];
        sort($files, SORT_NATURAL | SORT_FLAG_CASE);

        return array_values(array_filter($files, 'is_file'));
    }
}
