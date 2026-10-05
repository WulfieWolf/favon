<?php

namespace App\Services\Imports;

class Datex2ParkingMapper
{
    private const VEHICLE_TYPE_MAP = [
        'car' => 'car',
        'carWithTrailer' => 'car-with-trailer',
        'lorry' => 'truck',
        'bus' => 'coach',
    ];

    private const EQUIPMENT_MAP = [
        'refuseBin' => 'waste-bins',
        'picnicFacilities' => 'rest-picnic-area',
        'toilet' => 'toilet',
        'shower' => 'shower',
        'playground' => 'playground',
        'defibrillator' => 'defibrillator',
        'firstAidEquipment' => 'first-aid-equipment',
        'freshWater' => 'fresh-water',
        'dumpingStation' => 'dumping-station',
    ];

    public function map(array $record): array
    {
        [$latitude, $longitude, $coordinateSource] = $this->coordinates($record);

        $features = [];
        $unmappedEquipment = [];

        foreach ($record['equipment'] ?? [] as $equipment) {
            $sourceType = $equipment['type'] ?? null;
            if (! is_string($sourceType) || $sourceType === '') {
                continue;
            }

            $featureSlug = self::EQUIPMENT_MAP[$sourceType] ?? null;
            if ($featureSlug === null) {
                $unmappedEquipment[$sourceType] = ($unmappedEquipment[$sourceType] ?? 0) + 1;
                continue;
            }

            $status = match ($equipment['availability'] ?? null) {
                'available' => 'available',
                'notAvailable' => 'unavailable',
                default => 'unknown',
            };

            // If the source contains the same equipment more than once, a
            // definite value wins over unknown. Conflicting definite values are
            // retained as a conflict instead of being guessed.
            if (! isset($features[$featureSlug])) {
                $features[$featureSlug] = [
                    'status' => $status,
                    'source_type' => $sourceType,
                    'conflict' => false,
                ];
                continue;
            }

            $previous = $features[$featureSlug]['status'];
            if ($previous === 'unknown' && $status !== 'unknown') {
                $features[$featureSlug]['status'] = $status;
            } elseif ($status !== 'unknown' && $previous !== 'unknown' && $previous !== $status) {
                $features[$featureSlug]['conflict'] = true;
            }
        }

        $positiveVehicleCapacities = collect($record['parking_spaces_by_vehicle'] ?? [])
            ->filter(fn ($value) => is_int($value) && $value > 0)
            ->all();

        $vehicleTypeCapacities = collect($positiveVehicleCapacities)
            ->mapWithKeys(function ($capacity, $sourceType) {
                $vehicleSlug = self::VEHICLE_TYPE_MAP[$sourceType] ?? null;

                return $vehicleSlug ? [$vehicleSlug => $capacity] : [];
            })
            ->all();

        return [
            'external_id' => $record['external_id'] ?? null,
            'source_version' => $record['source_version'] ?? null,
            'source_updated_at' => $record['source_updated_at'] ?? null,

            'place' => [
                'suggested_place_type' => 'rest-area',
                'name' => $record['name'] ?? null,
                'alias' => $record['alias'] ?? null,
                'description' => $record['description'] ?? null,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'coordinate_source' => $coordinateSource,
                'address' => $record['address'] ?? [],
                'operator' => $record['operator'] ?? [],
                'free_of_charge' => $record['free_of_charge'] ?? null,
                'parking_spaces_total' => $record['parking_spaces_total'] ?? null,
                'usage_scenarios' => $record['usage_scenarios'] ?? [],
                'site_location' => $record['site_location'] ?? null,
            ],

            'features' => $features,

            // Capacity by DATEX vehicle type is preserved as source data.
            // Only "car" is a direct semantic match to Camperwolf's vehicle
            // catalogue. carWithTrailer is deliberately NOT treated as caravan.
            'vehicle_capacities' => $positiveVehicleCapacities,
            'vehicle_type_capacities' => $vehicleTypeCapacities,
            'vehicle_type_hints' => array_keys($vehicleTypeCapacities),

            'access_points' => $record['access_points'] ?? [],
            'unmapped_equipment' => $unmappedEquipment,
        ];
    }

    private function coordinates(array $record): array
    {
        if (is_numeric($record['latitude'] ?? null) && is_numeric($record['longitude'] ?? null)) {
            return [(float) $record['latitude'], (float) $record['longitude'], 'parking_location'];
        }

        $accessPoints = $record['access_points'] ?? [];

        foreach (['vehicleEntrance', 'vehicleExit'] as $preferredCategory) {
            foreach ($accessPoints as $access) {
                if (($access['category'] ?? null) !== $preferredCategory) {
                    continue;
                }

                if (is_numeric($access['latitude'] ?? null) && is_numeric($access['longitude'] ?? null)) {
                    return [(float) $access['latitude'], (float) $access['longitude'], 'access_'.$preferredCategory];
                }
            }
        }

        foreach ($accessPoints as $access) {
            if (is_numeric($access['latitude'] ?? null) && is_numeric($access['longitude'] ?? null)) {
                return [(float) $access['latitude'], (float) $access['longitude'], 'access_other'];
            }
        }

        return [null, null, null];
    }
}
