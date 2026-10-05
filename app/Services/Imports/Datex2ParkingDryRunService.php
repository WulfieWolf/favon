<?php

namespace App\Services\Imports;

use App\Services\ExternalImportGuard;
use RuntimeException;
use SplFileInfo;

class Datex2ParkingDryRunService
{
    public function __construct(
        private readonly Datex2ParkingParser $parser,
        private readonly ExternalImportGuard $guard,
    ) {
    }

    public function analyzePath(string $path): array
    {
        $files = $this->resolveXmlFiles($path);

        if ($files === []) {
            throw new RuntimeException(__('admin_imports.errors.datex_no_xml'));
        }

        $results = [];
        $totals = $this->emptyStats();
        $distributions = $this->emptyDistributions();

        foreach ($files as $file) {
            $xml = file_get_contents($file);
            if ($xml === false) {
                throw new RuntimeException(__('admin_imports.errors.datex_read', ['file' => $file]));
            }

            $records = $this->parser->parse($xml);
            $guard = $this->guard->validate($records, [
                'required_fields' => ['external_id', 'name', 'latitude', 'longitude'],
                'field_types' => [
                    'external_id' => 'string',
                    'name' => 'string',
                    'latitude' => 'number',
                    'longitude' => 'number',
                ],
                'max_missing_required_ratio' => 0.20,
            ]);

            $stats = $this->stats($records);
            $fileDistributions = $this->distributions($records);

            $results[] = [
                'file' => $file,
                'records' => count($records),
                'guard' => $guard,
                'stats' => $stats,
                'distributions' => $fileDistributions,
            ];

            foreach ($totals as $key => $value) {
                $totals[$key] += $stats[$key];
            }

            $distributions = $this->mergeDistributions($distributions, $fileDistributions);
        }

        return [
            'files' => $results,
            'totals' => $totals,
            'distributions' => $this->sortDistributions($distributions),
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

    private function stats(array $records): array
    {
        $stats = $this->emptyStats();

        foreach ($records as $record) {
            $stats['records']++;

            if (($record['name'] ?? null) === null) {
                $stats['missing_name']++;
            }

            if (($record['latitude'] ?? null) === null || ($record['longitude'] ?? null) === null) {
                $stats['missing_coordinates']++;
            }

            if (($record['parking_spaces_total'] ?? null) === null) {
                $stats['unknown_total_capacity']++;
            }

            $byVehicle = $record['parking_spaces_by_vehicle'] ?? [];
            if (array_key_exists('car', $byVehicle) && $byVehicle['car'] !== null) {
                $stats['with_car_spaces']++;
            }
            if (array_key_exists('lorry', $byVehicle) && $byVehicle['lorry'] !== null) {
                $stats['with_lorry_spaces']++;
            }

            $stats['equipment_entries'] += count($record['equipment'] ?? []);
            $stats['access_points'] += count($record['access_points'] ?? []);
        }

        return $stats;
    }

    private function emptyStats(): array
    {
        return [
            'records' => 0,
            'missing_name' => 0,
            'missing_coordinates' => 0,
            'unknown_total_capacity' => 0,
            'with_car_spaces' => 0,
            'with_lorry_spaces' => 0,
            'equipment_entries' => 0,
            'access_points' => 0,
        ];
    }

    private function distributions(array $records): array
    {
        $result = $this->emptyDistributions();

        foreach ($records as $record) {
            foreach (array_keys($record['parking_spaces_by_vehicle'] ?? []) as $vehicleType) {
                $this->increment($result['vehicle_types'], $vehicleType);
            }

            foreach ($record['equipment'] ?? [] as $equipment) {
                $this->increment($result['equipment_types'], $equipment['type'] ?? null);
                $this->increment($result['equipment_availability'], $equipment['availability'] ?? null);
            }

            foreach ($record['usage_scenarios'] ?? [] as $scenario) {
                $this->increment($result['usage_scenarios'], $scenario);
            }

            $this->increment($result['site_locations'], $record['site_location'] ?? null);

            foreach ($record['access_points'] ?? [] as $access) {
                $this->increment($result['access_categories'], $access['category'] ?? null);
                $this->increment($result['road_types'], $access['road_type'] ?? null);
            }

            $this->increment($result['security_levels'], $record['security']['level'] ?? null);
            $this->increment($result['service_levels'], $record['security']['service_level'] ?? null);

            $certified = $record['security']['certified_secure'] ?? null;
            $this->increment(
                $result['certified_secure'],
                $certified === null ? null : ($certified ? 'true' : 'false'),
            );
        }

        return $this->sortDistributions($result);
    }

    private function emptyDistributions(): array
    {
        return [
            'vehicle_types' => [],
            'equipment_types' => [],
            'equipment_availability' => [],
            'usage_scenarios' => [],
            'site_locations' => [],
            'access_categories' => [],
            'road_types' => [],
            'security_levels' => [],
            'service_levels' => [],
            'certified_secure' => [],
        ];
    }

    private function mergeDistributions(array $left, array $right): array
    {
        foreach ($right as $group => $values) {
            foreach ($values as $value => $count) {
                $left[$group][$value] = ($left[$group][$value] ?? 0) + $count;
            }
        }

        return $left;
    }

    private function sortDistributions(array $distributions): array
    {
        foreach ($distributions as &$values) {
            arsort($values, SORT_NUMERIC);
        }
        unset($values);

        return $distributions;
    }

    private function increment(array &$bucket, mixed $value): void
    {
        $key = $value === null || $value === '' ? '(unknown)' : (string) $value;
        $bucket[$key] = ($bucket[$key] ?? 0) + 1;
    }
}
