<?php

namespace App\Services\Imports;

use Illuminate\Support\Facades\DB;

class ResearchPlaceCsvExporter
{
    public function export(bool $onlyMissing = true): string
    {
        $vehicleTypes = DB::table('vehicle_types')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('slug')
            ->get(['id', 'slug']);

        $places = DB::table('places as p')
            ->join('place_types as pt', 'pt.id', '=', 'p.place_type_id')
            ->where('p.is_active', true)
            ->where('p.publication_status', 'published')
            ->orderBy('p.id')
            ->get([
                'p.id',
                'p.name',
                'pt.slug as place_type',
                'p.latitude',
                'p.longitude',
                'p.legal_status',
                'p.opening_status',
            ]);

        if ($places->isEmpty()) {
            return $this->csv([], $vehicleTypes->pluck('slug')->all());
        }

        $placeIds = $places->pluck('id')->map(fn ($id) => (int) $id)->all();

        $addresses = DB::table('place_addresses')
            ->whereIn('place_id', $placeIds)
            ->where('is_active', true)
            ->whereNull('version_valid_until')
            ->orderByDesc('id')
            ->get([
                'place_id',
                'country_code',
                'postal_code',
                'city',
                'street',
                'house_number',
                'address_addition',
            ])
            ->keyBy('place_id');

        $details = DB::table('place_details')
            ->whereIn('place_id', $placeIds)
            ->where('is_active', true)
            ->whereNull('version_valid_until')
            ->orderByDesc('id')
            ->get(['place_id', 'operator_name', 'pitch_count'])
            ->keyBy('place_id');

        $contacts = DB::table('place_contacts')
            ->whereIn('place_id', $placeIds)
            ->where('is_active', true)
            ->whereNull('version_valid_until')
            ->whereIn('contact_type', ['website', 'url', 'phone', 'telephone', 'email'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['place_id', 'contact_type', 'value'])
            ->groupBy('place_id');

        $vehicleRows = DB::table('place_vehicle_types')
            ->whereIn('place_id', $placeIds)
            ->where('is_active', true)
            ->whereNull('version_valid_until')
            ->get(['place_id', 'vehicle_type_id', 'capacity'])
            ->groupBy('place_id');

        $vehicleSlugsById = $vehicleTypes->pluck('slug', 'id');

        $rows = [];
        foreach ($places as $place) {
            $placeId = (int) $place->id;
            $address = $addresses->get($placeId);
            $detail = $details->get($placeId);
            $placeContacts = $contacts->get($placeId, collect());

            $website = $placeContacts->first(fn ($contact) => in_array($contact->contact_type, ['website', 'url'], true));
            $phone = $placeContacts->first(fn ($contact) => in_array($contact->contact_type, ['phone', 'telephone'], true));
            $email = $placeContacts->firstWhere('contact_type', 'email');

            $vehicleValues = [];
            foreach ($vehicleRows->get($placeId, collect()) as $vehicleRow) {
                $slug = $vehicleSlugsById->get((int) $vehicleRow->vehicle_type_id);
                if (! $slug) {
                    continue;
                }

                $vehicleValues[$slug] = $vehicleRow->capacity !== null
                    ? (string) ((int) $vehicleRow->capacity)
                    : 'yes';
            }

            $missing = $this->missingFields(
                $address,
                $detail,
                $website?->value,
                $phone?->value,
                $email?->value,
                $vehicleValues,
            );

            if ($onlyMissing && $missing === []) {
                continue;
            }

            $row = [
                'place_id' => $placeId,
                'missing_fields' => implode('|', $missing),
                'author' => '',
                'source_label' => '',
                'source_url' => '',
                'researched_at' => '',
                'notes' => '',
                'current_name' => $place->name,
                'current_place_type' => $place->place_type,
                'current_latitude' => $this->excelDecimal($place->latitude),
                'current_longitude' => $this->excelDecimal($place->longitude),
                'current_legal_status' => $place->legal_status,
                'current_opening_status' => $place->opening_status,
                'current_country_code' => $address?->country_code,
                'current_postal_code' => $address?->postal_code,
                'current_city' => $address?->city,
                'current_street' => $address?->street,
                'current_house_number' => $address?->house_number,
                'current_address_addition' => $address?->address_addition,
                'current_operator' => $detail?->operator_name,
                'current_parking_spaces' => $detail?->pitch_count,
                'current_website' => $website?->value,
                'current_phone' => $phone?->value,
                'current_email' => $email?->value,
                'name' => '',
                'place_type' => '',
                'latitude' => '',
                'longitude' => '',
                'legal_status' => '',
                'opening_status' => '',
                'country_code' => '',
                'postal_code' => '',
                'city' => '',
                'street' => '',
                'house_number' => '',
                'address_addition' => '',
                'operator' => '',
                'parking_spaces' => '',
                'website' => '',
                'phone' => '',
                'email' => '',
            ];

            foreach ($vehicleTypes as $vehicleType) {
                $row['current_suitable_'.$vehicleType->slug] = $vehicleValues[$vehicleType->slug] ?? '';
                $row['suitable_'.$vehicleType->slug] = '';
            }

            $rows[] = $row;
        }

        return $this->csv($rows, $vehicleTypes->pluck('slug')->all());
    }

    private function missingFields(
        ?object $address,
        ?object $detail,
        ?string $website,
        ?string $phone,
        ?string $email,
        array $vehicleValues,
    ): array {
        $fields = [
            'country_code' => $address?->country_code,
            'postal_code' => $address?->postal_code,
            'city' => $address?->city,
            'street' => $address?->street,
            'operator' => $detail?->operator_name,
            'parking_spaces' => $detail?->pitch_count,
            'website' => $website,
            'phone' => $phone,
            'email' => $email,
        ];

        $missing = [];
        foreach ($fields as $field => $value) {
            if ($value === null || trim((string) $value) === '') {
                $missing[] = $field;
            }
        }

        if ($vehicleValues === []) {
            $missing[] = 'suitable_for';
        }

        return $missing;
    }

    private function excelDecimal(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $formatted = number_format((float) $value, 7, ',', '');

        return rtrim(rtrim($formatted, '0'), ',');
    }

    private function csv(array $rows, array $vehicleSlugs): string
    {
        $headers = [
            'place_id',
            'missing_fields',
            'author',
            'source_label',
            'source_url',
            'researched_at',
            'notes',
            'current_name',
            'current_place_type',
            'current_latitude',
            'current_longitude',
            'current_legal_status',
            'current_opening_status',
            'current_country_code',
            'current_postal_code',
            'current_city',
            'current_street',
            'current_house_number',
            'current_address_addition',
            'current_operator',
            'current_parking_spaces',
            'current_website',
            'current_phone',
            'current_email',
            'name',
            'place_type',
            'latitude',
            'longitude',
            'legal_status',
            'opening_status',
            'country_code',
            'postal_code',
            'city',
            'street',
            'house_number',
            'address_addition',
            'operator',
            'parking_spaces',
            'website',
            'phone',
            'email',
        ];

        foreach ($vehicleSlugs as $slug) {
            $headers[] = 'current_suitable_'.$slug;
            $headers[] = 'suitable_'.$slug;
        }

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, $headers, ';', '"', '');

        foreach ($rows as $row) {
            fputcsv(
                $stream,
                array_map(fn ($header) => $row[$header] ?? '', $headers),
                ';',
                '"',
                '',
            );
        }

        rewind($stream);
        $content = stream_get_contents($stream);
        fclose($stream);

        return $content === false ? '' : $content;
    }
}
