<?php

namespace App\Services;

use Illuminate\Support\Arr;

class ExternalImportGuard
{
    public function validate(array $records, array $rules = [], ?int $previousRecordCount = null): array
    {
        $errors = [];
        $warnings = [];
        $count = count($records);

        $minRecords = isset($rules['min_records']) ? (int) $rules['min_records'] : null;
        $maxRecords = isset($rules['max_records']) ? (int) $rules['max_records'] : null;
        $maxDropRatio = isset($rules['max_drop_ratio']) ? (float) $rules['max_drop_ratio'] : 0.80;
        $maxGrowthFactor = isset($rules['max_growth_factor']) ? (float) $rules['max_growth_factor'] : 20.0;

        if ($minRecords !== null && $count < $minRecords) {
            $errors[] = ['code' => 'record_count_below_minimum', 'actual' => $count, 'minimum' => $minRecords];
        }

        if ($maxRecords !== null && $count > $maxRecords) {
            $errors[] = ['code' => 'record_count_above_maximum', 'actual' => $count, 'maximum' => $maxRecords];
        }

        if ($previousRecordCount !== null && $previousRecordCount > 0) {
            $dropRatio = 1 - ($count / $previousRecordCount);
            if ($dropRatio > $maxDropRatio) {
                $errors[] = [
                    'code' => 'record_count_drop_too_large',
                    'actual' => $count,
                    'previous' => $previousRecordCount,
                    'drop_ratio' => round($dropRatio, 4),
                ];
            }

            if ($count > ($previousRecordCount * $maxGrowthFactor)) {
                $errors[] = [
                    'code' => 'record_count_growth_too_large',
                    'actual' => $count,
                    'previous' => $previousRecordCount,
                    'growth_factor' => round($count / $previousRecordCount, 4),
                ];
            }
        }

        $required = array_values(array_filter($rules['required_fields'] ?? [], 'is_string'));
        $types = is_array($rules['field_types'] ?? null) ? $rules['field_types'] : [];
        $missingCounts = array_fill_keys($required, 0);
        $typeErrors = [];

        foreach ($records as $index => $record) {
            if (! is_array($record)) {
                $errors[] = ['code' => 'record_not_object', 'index' => $index];
                continue;
            }

            foreach ($required as $field) {
                $value = Arr::get($record, $field);
                if ($value === null || $value === '') {
                    $missingCounts[$field]++;
                }
            }

            foreach ($types as $field => $type) {
                $value = Arr::get($record, $field);
                if ($value === null) {
                    continue;
                }

                if (! $this->matchesType($value, (string) $type)) {
                    $typeErrors[$field] = ($typeErrors[$field] ?? 0) + 1;
                }
            }
        }

        $maxMissingRatio = isset($rules['max_missing_required_ratio'])
            ? (float) $rules['max_missing_required_ratio']
            : 0.05;

        if ($count > 0) {
            foreach ($missingCounts as $field => $missingCount) {
                $ratio = $missingCount / $count;
                if ($ratio > $maxMissingRatio) {
                    $errors[] = [
                        'code' => 'required_field_missing_too_often',
                        'field' => $field,
                        'missing' => $missingCount,
                        'ratio' => round($ratio, 4),
                    ];
                } elseif ($missingCount > 0) {
                    $warnings[] = [
                        'code' => 'required_field_missing',
                        'field' => $field,
                        'missing' => $missingCount,
                        'ratio' => round($ratio, 4),
                    ];
                }
            }

            foreach ($typeErrors as $field => $invalidCount) {
                $ratio = $invalidCount / $count;
                $entry = [
                    'code' => 'field_type_mismatch',
                    'field' => $field,
                    'invalid' => $invalidCount,
                    'ratio' => round($ratio, 4),
                    'expected' => (string) $types[$field],
                ];

                if ($ratio > 0.01) {
                    $errors[] = $entry;
                } else {
                    $warnings[] = $entry;
                }
            }
        }

        return [
            'valid' => $errors === [],
            'record_count' => $count,
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    private function matchesType(mixed $value, string $type): bool
    {
        return match ($type) {
            'string' => is_string($value),
            'integer' => is_int($value),
            'number' => is_int($value) || is_float($value),
            'boolean' => is_bool($value),
            'array' => is_array($value) && array_is_list($value),
            'object' => is_array($value) && ! array_is_list($value),
            default => true,
        };
    }
}
