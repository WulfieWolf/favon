<?php

namespace App\Services\Imports;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class ManualCsvPlaceParser
{
    private const BASE_COLUMNS = [
        'external_id',
        'name',
        'latitude',
        'longitude',
        'street',
        'house_number',
        'postal_code',
        'city',
        'country_code',
        'place_type',
        'operator',
        'website',
        'phone',
        'parking_spaces',
    ];

    public function parseFile(string $path): array
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException(__('admin_imports.errors.manual_csv_read'));
        }

        try {
            $firstLine = fgets($handle);
            if ($firstLine === false) {
                throw new RuntimeException(__('admin_imports.errors.manual_csv_empty'));
            }

            $delimiter = $this->detectDelimiter($firstLine);
            rewind($handle);

            $header = fgetcsv($handle, 0, $delimiter);
            if (! is_array($header)) {
                throw new RuntimeException(__('admin_imports.errors.manual_csv_header'));
            }

            $header = array_map(fn ($value) => trim((string) $value), $header);
            if ($header !== array_values(array_unique($header))) {
                throw new RuntimeException(__('admin_imports.errors.manual_csv_duplicate_columns'));
            }

            $this->validateHeader($header);

            $records = [];
            $line = 1;
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $line++;

                if ($this->isEmptyRow($row)) {
                    continue;
                }

                if (count($row) !== count($header)) {
                    throw new RuntimeException(__('admin_imports.errors.manual_csv_column_count', ['line' => $line]));
                }

                $data = array_combine($header, array_map(fn ($value) => trim((string) $value), $row));
                if (! is_array($data)) {
                    throw new RuntimeException(__('admin_imports.errors.manual_csv_row', ['line' => $line]));
                }

                $records[] = $this->mapRow($data, $line);
            }

            if ($records === []) {
                throw new RuntimeException(__('admin_imports.errors.manual_csv_no_records'));
            }

            $ids = array_column($records, 'external_id');
            if (count($ids) !== count(array_unique($ids))) {
                throw new RuntimeException(__('admin_imports.errors.manual_csv_duplicate_ids'));
            }

            return $records;
        } finally {
            fclose($handle);
        }
    }

    public function template(): string
    {
        $header = array_merge(self::BASE_COLUMNS, [
            'feature:waste-bins',
            'feature:toilet',
            'feature:shower',
            'feature:fresh-water',
            'feature:dumping-station',
            'feature:rest-picnic-area',
            'feature:playground',
            'feature:defibrillator',
            'feature:first-aid-equipment',
        ]);

        $example = [
            'example-001',
            'Beispiel Stellplatz',
            '51.4556',
            '7.0116',
            'Musterstraße',
            '1',
            '45127',
            'Essen',
            'DE',
            'rest-area',
            'Beispiel Betreiber',
            'https://example.org',
            '+49 201 123456',
            '25',
            'yes',
            'yes',
            'unknown',
            'yes',
            'no',
            'yes',
            'no',
            'yes',
            'yes',
        ];

        return $this->csvLine($header).$this->csvLine($example);
    }

    private function validateHeader(array $header): void
    {
        foreach (['external_id', 'name'] as $required) {
            if (! in_array($required, $header, true)) {
                throw new RuntimeException(__('admin_imports.errors.manual_csv_required_column', ['column' => $required]));
            }
        }

        $knownFeatures = DB::table('features')
            ->where('is_active', true)
            ->pluck('slug')
            ->all();

        foreach ($header as $column) {
            if (in_array($column, self::BASE_COLUMNS, true)) {
                continue;
            }

            if (str_starts_with($column, 'feature:')) {
                $slug = substr($column, 8);
                if ($slug !== '' && in_array($slug, $knownFeatures, true)) {
                    continue;
                }
            }

            throw new RuntimeException(__('admin_imports.errors.manual_csv_unknown_column', ['column' => $column]));
        }
    }

    private function mapRow(array $data, int $line): array
    {
        $externalId = trim((string) ($data['external_id'] ?? ''));
        $name = trim((string) ($data['name'] ?? ''));

        if ($externalId === '') {
            throw new RuntimeException(__('admin_imports.errors.manual_csv_external_id', ['line' => $line]));
        }
        if ($name === '') {
            throw new RuntimeException(__('admin_imports.errors.manual_csv_name', ['line' => $line]));
        }

        $latitude = $this->coordinate($data['latitude'] ?? '', -90, 90, 'latitude', $line);
        $longitude = $this->coordinate($data['longitude'] ?? '', -180, 180, 'longitude', $line);

        if (($latitude === null) xor ($longitude === null)) {
            throw new RuntimeException(__('admin_imports.errors.manual_csv_coordinates_pair', ['line' => $line]));
        }

        $parkingSpaces = $this->positiveIntOrNull($data['parking_spaces'] ?? '', 'parking_spaces', $line);

        $features = [];
        foreach ($data as $column => $value) {
            if (! str_starts_with($column, 'feature:')) {
                continue;
            }

            $slug = substr($column, 8);
            $features[$slug] = [
                'status' => $this->featureStatus((string) $value, $column, $line),
                'source_type' => 'csv',
                'conflict' => false,
            ];
        }

        return [
            'external_id' => $externalId,
            'source_version' => null,
            'source_updated_at' => null,
            'place' => [
                'suggested_place_type' => $this->nullIfBlank($data['place_type'] ?? null) ?? 'rest-area',
                'name' => $name,
                'alias' => null,
                'description' => null,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'coordinate_source' => $latitude !== null ? 'csv' : null,
                'address' => [
                    'street' => $this->nullIfBlank($data['street'] ?? null),
                    'house_number' => $this->nullIfBlank($data['house_number'] ?? null),
                    'postal_code' => $this->nullIfBlank($data['postal_code'] ?? null),
                    'city' => $this->nullIfBlank($data['city'] ?? null),
                    'country_code' => strtoupper((string) ($this->nullIfBlank($data['country_code'] ?? null) ?? '')),
                ],
                'operator' => [
                    'name' => $this->nullIfBlank($data['operator'] ?? null),
                    'phone' => $this->nullIfBlank($data['phone'] ?? null),
                    'url' => $this->nullIfBlank($data['website'] ?? null),
                    'email' => null,
                ],
                'free_of_charge' => null,
                'parking_spaces_total' => $parkingSpaces,
                'usage_scenarios' => [],
                'site_location' => null,
            ],
            'features' => $features,
            'vehicle_capacities' => [],
            'vehicle_type_hints' => [],
            'access_points' => [],
            'unmapped_equipment' => [],
        ];
    }

    private function featureStatus(string $value, string $column, int $line): string
    {
        $value = mb_strtolower(trim($value));

        return match ($value) {
            '', 'unknown', 'unbekannt', '?' => 'unknown',
            'yes', 'ja', 'available', 'vorhanden', '1', 'true' => 'available',
            'no', 'nein', 'unavailable', 'nicht vorhanden', '0', 'false' => 'unavailable',
            default => throw new RuntimeException(__('admin_imports.errors.manual_csv_invalid_value', ['line' => $line, 'value' => $value, 'column' => $column])), 
        };
    }

    private function coordinate(string $value, float $min, float $max, string $field, int $line): ?float
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $normalized = str_replace(',', '.', $value);
        if (! is_numeric($normalized)) {
            throw new RuntimeException(__('admin_imports.errors.manual_csv_not_number', ['line' => $line, 'field' => $field]));
        }

        $number = (float) $normalized;
        if ($number < $min || $number > $max) {
            throw new RuntimeException(__('admin_imports.errors.manual_csv_range', ['line' => $line, 'field' => $field]));
        }

        return $number;
    }

    private function positiveIntOrNull(string $value, string $field, int $line): ?int
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (! ctype_digit($value)) {
            throw new RuntimeException(__('admin_imports.errors.manual_csv_positive_integer', ['line' => $line, 'field' => $field]));
        }

        $number = (int) $value;

        return $number > 0 ? $number : null;
    }

    private function detectDelimiter(string $line): string
    {
        return substr_count($line, ';') > substr_count($line, ',') ? ';' : ',';
    }

    private function nullIfBlank(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function isEmptyRow(array $row): bool
    {
        return collect($row)->every(fn ($value) => trim((string) $value) === '');
    }

    private function csvLine(array $values): string
    {
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, $values);
        rewind($stream);
        $line = stream_get_contents($stream);
        fclose($stream);

        return $line === false ? '' : $line;
    }
}
