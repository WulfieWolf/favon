<?php

namespace App\Services\Imports;

class BayernAtkisMapper
{
    private const TYPE_MAP = [
        'adv:AX_Platz' => [
            '5310' => 'parking',
            '5320' => 'rest-area',
            '5330' => 'rest-area',
            '5370' => 'motorhome-pitch',
        ],
        'adv:AX_SportFreizeitUndErholungsflaeche' => [
            '4330' => 'campground',
        ],
    ];

    public function supports(string $featureType, string $function): bool
    {
        return isset(self::TYPE_MAP[$featureType][$function]);
    }

    public function map(array $item): array
    {
        $featureType = trim((string) ($item['feature_type'] ?? ''));
        $function = trim((string) ($item['function'] ?? ''));
        $name = $this->text($item['name'] ?? null);
        $secondNames = collect(is_array($item['second_names'] ?? null) ? $item['second_names'] : [])
            ->map(fn ($value) => $this->text($value))
            ->filter()
            ->values()
            ->all();

        return [
            'external_id' => trim((string) ($item['external_id'] ?? '')),
            'source_updated_at' => $this->text($item['source_updated_at'] ?? null),
            'place' => [
                'name' => $name ?? ($secondNames[0] ?? null),
                'latitude' => is_numeric($item['latitude'] ?? null) ? (float) $item['latitude'] : null,
                'longitude' => is_numeric($item['longitude'] ?? null) ? (float) $item['longitude'] : null,
                'coordinate_source' => 'bayern-atkis-surface-representative-point',
                'suggested_place_type' => self::TYPE_MAP[$featureType][$function] ?? null,
                'opening_status' => $this->openingStatus($item['condition'] ?? null),
                'address' => [
                    'country_code' => 'DE',
                ],
            ],
            'features' => [],
            'license' => [
                'code' => 'cc-by-4.0',
                'name' => 'Creative Commons Attribution 4.0 International',
                'url' => 'https://creativecommons.org/licenses/by/4.0/',
            ],
            'media' => [],
            'source_name_origin' => $name !== null ? 'name' : ($secondNames !== [] ? 'zweitname' : null),
            'source_properties' => [
                'feature_type' => $featureType,
                'function' => $function,
                'name' => $name,
                'second_names' => $secondNames,
                'condition' => $this->text($item['condition'] ?? null),
                'geometry_transformation' => 'ATKIS surface geometry reduced to an arithmetic representative point for Camperwolf.',
            ],
        ];
    }

    public function relevantFunctions(): array
    {
        return self::TYPE_MAP;
    }

    private function openingStatus(mixed $condition): string
    {
        return trim((string) $condition) === '2100'
            ? 'permanently_closed'
            : 'open';
    }

    private function text(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
