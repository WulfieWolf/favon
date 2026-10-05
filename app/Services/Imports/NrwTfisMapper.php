<?php

namespace App\Services\Imports;

class NrwTfisMapper
{
    public function map(array $feature): array
    {
        $properties = is_array($feature['properties'] ?? null) ? $feature['properties'] : [];
        $geometry = is_array($feature['geometry'] ?? null) ? $feature['geometry'] : [];
        $coordinates = is_array($geometry['coordinates'] ?? null) ? $geometry['coordinates'] : [];

        $function = $this->text($properties['fkt'] ?? null);
        $name = $this->text($properties['nam'] ?? null);
        $info = $this->text($properties['info_ext'] ?? null);
        $publicName = $name ?? $info;

        return [
            'external_id' => (string) ($properties['uuid'] ?? ''),
            'source_updated_at' => null,
            'place' => [
                'name' => $publicName,
                'latitude' => $this->coordinate($coordinates[1] ?? null),
                'longitude' => $this->coordinate($coordinates[0] ?? null),
                'coordinate_source' => 'nrw-tfis',
                'suggested_place_type' => $this->placeType($function, $info),
                'opening_status' => 'open',
                'address' => [
                    'country_code' => 'DE',
                ],
            ],
            'features' => [],
            'license' => [
                'code' => 'dl-de-zero-2.0',
                'name' => 'Datenlizenz Deutschland - Zero - Version 2.0',
                'url' => 'https://www.govdata.de/dl-de/zero-2-0',
            ],
            'media' => [],
            'source_name_origin' => $name !== null ? 'nam' : ($info !== null ? 'info_ext' : null),
            'source_properties' => [
                'fkt' => $function,
                'nam' => $name,
                'snr' => $this->text($properties['snr'] ?? null),
                'hmp' => $this->text($properties['hmp'] ?? null),
                'info_ext' => $info,
            ],
        ];
    }

    private function placeType(?string $function, ?string $info): string
    {
        if ($function === 'Campingplatz') {
            return 'campground';
        }

        if ($function === 'Wanderparkplatz') {
            return 'hiking-parking';
        }

        if ($function === 'Parkplatz' && $this->containsHikingParking($info)) {
            return 'hiking-parking';
        }

        return 'parking';
    }

    private function containsHikingParking(?string $value): bool
    {
        if ($value === null) {
            return false;
        }

        return str_contains(mb_strtolower($value), 'wanderparkplatz');
    }

    private function text(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function coordinate(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}
