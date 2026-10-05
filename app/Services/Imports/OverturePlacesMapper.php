<?php

namespace App\Services\Imports;

class OverturePlacesMapper
{
    public function map(array $row): array
    {
        $category = $this->text($row['category'] ?? null);
        $sources = $this->jsonArray($row['sources'] ?? null);
        $alternates = $this->jsonArray($row['taxonomy_alternates'] ?? null);
        $hierarchy = $this->jsonArray($row['taxonomy_hierarchy'] ?? null);

        return [
            'external_id' => trim((string) ($row['id'] ?? '')),
            'place' => [
                'name' => $this->text($row['name'] ?? null),
                'latitude' => $this->number($row['latitude'] ?? null),
                'longitude' => $this->number($row['longitude'] ?? null),
                'coordinate_source' => 'overture-places',
                'suggested_place_type' => $this->placeType($category),
                'opening_status' => $this->openingStatus($row['operating_status'] ?? null),
                'address' => [
                    'country_code' => $this->countryCode($row['country'] ?? null),
                    'postal_code' => $this->text($row['postcode'] ?? null),
                    'city' => $this->text($row['locality'] ?? null),
                    'street' => null,
                    'house_number' => null,
                    'address_addition' => null,
                ],
                'operator' => [
                    'url' => $this->text($row['website'] ?? null),
                    'phone' => $this->text($row['phone'] ?? null),
                    'email' => $this->text($row['email'] ?? null),
                ],
            ],
            'features' => [],
            'media' => [],
            'license' => [
                'source_items' => $sources,
            ],
            'source_categories' => array_values(array_filter([$category])),
            'source_properties' => [
                'overture_category' => $category,
                'basic_category' => $this->text($row['basic_category'] ?? null),
                'confidence' => $this->number($row['confidence'] ?? null),
                'operating_status' => $this->text($row['operating_status'] ?? null),
                'address_freeform' => $this->text($row['address'] ?? null),
                'region' => $this->text($row['region'] ?? null),
                'taxonomy_hierarchy' => $hierarchy,
                'taxonomy_alternates' => $alternates,
                'sources' => $sources,
            ],
        ];
    }

    public function placeType(?string $category): ?string
    {
        return match ($category) {
            'campground', 'holiday_park' => 'campground',
            'rv_park' => 'motorhome-pitch',
            default => null,
        };
    }

    private function openingStatus(mixed $value): string
    {
        $value = trim((string) $value);

        return match ($value) {
            'temporarily_closed' => 'temporarily_closed',
            'permanently_closed' => 'permanently_closed',
            default => 'open',
        };
    }

    private function countryCode(mixed $value): string
    {
        $value = strtoupper(trim((string) $value));

        return $value !== '' ? $value : 'DE';
    }

    private function jsonArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function text(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function number(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}
