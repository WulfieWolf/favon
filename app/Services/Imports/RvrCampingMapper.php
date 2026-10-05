<?php

namespace App\Services\Imports;

class RvrCampingMapper
{
    public function map(array $item): array
    {
        $categories = array_values(array_unique(array_filter(
            is_array($item['categories'] ?? null) ? $item['categories'] : [],
            fn ($value) => is_string($value) && trim($value) !== '',
        )));

        return [
            'external_id' => trim((string) ($item['external_id'] ?? $item['poi_id'] ?? '')),
            'source_updated_at' => $this->text($item['geaendert_am'] ?? null),
            'place' => [
                'name' => $this->text($item['name'] ?? null),
                'latitude' => $this->number($item['latitude'] ?? null),
                'longitude' => $this->number($item['longitude'] ?? null),
                'coordinate_source' => 'rvr-poi-wfs',
                'suggested_place_type' => $this->placeType($categories),
                'opening_status' => 'open',
                'address' => [
                    'country_code' => 'DE',
                    'postal_code' => $this->text($item['plz'] ?? null),
                    'city' => $this->text($item['ort'] ?? null),
                    'street' => $this->text($item['strasse'] ?? null),
                    'house_number' => $this->text($item['hausnummer'] ?? null),
                    'address_addition' => $this->text($item['adresse_zusatz'] ?? null),
                ],
                'operator' => [
                    'url' => $this->text($item['link'] ?? null),
                ],
                'description' => $this->text($item['beschreibung'] ?? null),
            ],
            'features' => [],
            'media' => [],
            'source_categories' => $categories,
            'source_properties' => [
                'poi_id' => $this->text($item['poi_id'] ?? null),
                'gid' => $this->text($item['gid'] ?? null),
                'spw_name' => $this->text($item['spw_name'] ?? null),
                'kreis_id' => $this->text($item['kreis_id'] ?? null),
                'kreis' => $this->text($item['kreis'] ?? null),
                'ist_rvr' => $this->boolValue($item['ist_rvr'] ?? null),
                'hauptkategorie' => $this->text($item['hauptkategorie'] ?? null),
                'institution' => $this->text($item['institution'] ?? null),
                'bedeutung_id' => $this->text($item['bedeutung_id'] ?? null),
                'bedeutung' => $this->text($item['bedeutung'] ?? null),
                'fachinfodatum' => $this->text($item['fachinfodatum'] ?? null),
                'ablaufdatum' => $this->text($item['ablaufdatum'] ?? null),
                'rechts_utm' => $this->number($item['rechts_utm'] ?? null),
                'hoch_utm' => $this->number($item['hoch_utm'] ?? null),
                'angelegt_am' => $this->text($item['angelegt_am'] ?? null),
                'geaendert_am' => $this->text($item['geaendert_am'] ?? null),
                'category_assignments' => is_array($item['category_assignments'] ?? null)
                    ? $item['category_assignments']
                    : [],
            ],
        ];
    }

    public function placeType(array $categories): ?string
    {
        if (
            in_array('Campingplätze', $categories, true)
            || in_array('Dauercampingplätze', $categories, true)
        ) {
            return 'campground';
        }

        if (in_array('Wohnmobilstellplätze', $categories, true)) {
            return 'motorhome-pitch';
        }

        if (in_array('Jugendzeltplätze', $categories, true)) {
            return 'tent-site';
        }

        return null;
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
