<?php

namespace App\Services\Imports;

use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;
use XMLReader;

class Datex2ParkingParser
{
    public function parseFile(string $path): iterable
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException(__('admin_imports.errors.datex_xml_read', ['path' => $path]));
        }

        $reader = new XMLReader();
        if (! $reader->open($path, null, LIBXML_NONET | LIBXML_NOBLANKS)) {
            throw new RuntimeException(__('admin_imports.errors.datex_xml_open', ['path' => $path]));
        }

        try {
            while ($reader->read()) {
                if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'parkingRecord') {
                    continue;
                }

                $expanded = $reader->expand();
                if (! $expanded instanceof \DOMElement) {
                    continue;
                }

                $dom = new DOMDocument();
                $record = $dom->importNode($expanded, true);
                if (! $record instanceof DOMElement) {
                    continue;
                }

                $dom->appendChild($record);
                $xpath = new DOMXPath($dom);
                yield $this->parseRecord($xpath, $record);
            }
        } finally {
            $reader->close();
        }
    }

    public function parse(string $xml): array
    {
        $previous = libxml_use_internal_errors(true);

        try {
            $dom = new DOMDocument();
            $loaded = $dom->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS);

            if (! $loaded) {
                $messages = array_map(
                    static fn ($error) => trim($error->message),
                    libxml_get_errors(),
                );

                throw new RuntimeException('Invalid DATEX-II XML: '.implode('; ', array_filter($messages)));
            }

            $xpath = new DOMXPath($dom);
            $records = [];

            foreach ($xpath->query('//*[local-name()="parkingRecord"]') as $record) {
                if (! $record instanceof DOMElement) {
                    continue;
                }

                $normalized = $this->parseRecord($xpath, $record);

                if ($normalized['external_id'] === null) {
                    continue;
                }

                $records[] = $normalized;
            }

            return $records;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private function parseRecord(DOMXPath $xpath, DOMElement $record): array
    {
        $externalId = $this->clean($record->getAttribute('id'));
        $sourceVersion = $this->clean($record->getAttribute('version'));

        $vehicleSpaces = [];
        foreach ($xpath->query('.//*[local-name()="groupOfParkingSpaces"]', $record) as $group) {
            if (! $group instanceof DOMElement) {
                continue;
            }

            $vehicleType = $this->firstText(
                $xpath,
                './/*[local-name()="vehicleType"][1]',
                $group,
            );

            if ($vehicleType === null) {
                $vehicleType = $this->firstText(
                    $xpath,
                    './/*[local-name()="vehicleType2"][1]',
                    $group,
                );
            }

            if ($vehicleType === null) {
                continue;
            }

            $spaces = $this->positiveInt(
                $this->firstText($xpath, './/*[local-name()="parkingNumberOfSpaces"][1]', $group),
            );

            $vehicleSpaces[$vehicleType] = $spaces;
        }

        $equipment = [];
        foreach ($xpath->query('.//*[local-name()="parkingEquipmentOrServiceFacility"]/*[local-name()="parkingEquipmentOrServiceFacility"]', $record) as $facility) {
            if (! $facility instanceof DOMElement) {
                continue;
            }

            $type = $this->firstText($xpath, './/*[local-name()="equipmentType"][1]', $facility);
            $availability = $this->firstText($xpath, './/*[local-name()="availability"][1]', $facility);

            if ($type !== null) {
                $equipment[] = [
                    'type' => $type,
                    'availability' => $availability,
                ];
            }
        }

        $usageScenarios = [];
        foreach ($xpath->query('.//*[local-name()="parkingUsageScenario"]/*[local-name()="parkingUsageScenario"]/*[local-name()="parkingUsageScenario"]', $record) as $scenario) {
            $value = $this->clean($scenario->textContent);
            if ($value !== null && ! in_array($value, $usageScenarios, true)) {
                $usageScenarios[] = $value;
            }
        }

        $accessPoints = [];
        foreach ($xpath->query('./*[local-name()="parkingAccess"]', $record) as $access) {
            if (! $access instanceof DOMElement) {
                continue;
            }

            $lat = $this->number($this->firstText($xpath, './/*[local-name()="latitude"][1]', $access));
            $lon = $this->number($this->firstText($xpath, './/*[local-name()="longitude"][1]', $access));

            $accessPoints[] = [
                'external_id' => $this->clean($access->getAttribute('id')),
                'category' => $this->firstText($xpath, './*[local-name()="accessCategory"][1]', $access),
                'name' => $this->firstValue($xpath, './*[local-name()="accessName"]', $access),
                'road' => $this->firstValue($xpath, './/*[local-name()="roadIdentifier"]', $access),
                'road_type' => $this->firstText($xpath, './/*[local-name()="typeOfRoad"][1]', $access),
                'direction' => $this->firstValue($xpath, './/*[local-name()="roadDestination"]', $access),
                'latitude' => $lat,
                'longitude' => $lon,
            ];
        }

        return [
            'external_id' => $externalId,
            'source_version' => $sourceVersion,
            'source_updated_at' => $this->firstText($xpath, './*[local-name()="parkingRecordVersionTime"][1]', $record),
            'name' => $this->firstValue($xpath, './*[local-name()="parkingName"]', $record),
            'alias' => $this->firstValue($xpath, './*[local-name()="parkingAlias"]', $record),
            'description' => $this->firstValue($xpath, './*[local-name()="parkingDescription"]', $record),
            'latitude' => $this->number($this->firstText($xpath, './*[local-name()="parkingLocation"]//*[local-name()="latitude"][1]', $record)),
            'longitude' => $this->number($this->firstText($xpath, './*[local-name()="parkingLocation"]//*[local-name()="longitude"][1]', $record)),
            'parking_spaces_total' => $this->positiveInt($this->firstText($xpath, './*[local-name()="parkingNumberOfSpaces"][1]', $record)),
            'parking_spaces_by_vehicle' => $vehicleSpaces,
            'free_of_charge' => $this->bool($this->firstText($xpath, './*[local-name()="tariffsAndPayment"]/*[local-name()="freeOfCharge"][1]', $record)),
            'operator' => [
                'name' => $this->firstValue($xpath, './*[local-name()="operator"]/*[local-name()="contactOrganisationName"]', $record),
                'phone' => $this->firstText($xpath, './*[local-name()="operator"]/*[local-name()="contactDetailsTelephoneNumber"][1]', $record),
                'email' => $this->firstText($xpath, './*[local-name()="operator"]/*[local-name()="contactDetailsEMail"][1]', $record),
                'url' => $this->firstText($xpath, './*[local-name()="operator"]/*[local-name()="urlLinkAddress"][1]', $record),
            ],
            'address' => [
                'street' => $this->firstText($xpath, './*[local-name()="parkingSiteAddress"]/*[local-name()="contactDetailsStreet"][1]', $record),
                'house_number' => $this->firstText($xpath, './*[local-name()="parkingSiteAddress"]/*[local-name()="contactDetailsHouseNumber"][1]', $record),
                'postal_code' => $this->firstText($xpath, './*[local-name()="parkingSiteAddress"]/*[local-name()="contactDetailsPostcode"][1]', $record),
                'city' => $this->firstValue($xpath, './*[local-name()="parkingSiteAddress"]/*[local-name()="contactDetailsCity"]', $record),
                'country_code' => $this->firstText($xpath, './*[local-name()="parkingSiteAddress"]/*[local-name()="country"][1]', $record),
            ],
            'usage_scenarios' => $usageScenarios,
            'equipment' => $equipment,
            'access_points' => $accessPoints,
            'site_location' => $this->firstText($xpath, './*[local-name()="interUrbanParkingSiteLocation"][1]', $record),
            'security' => [
                'level' => $this->firstText($xpath, './*[local-name()="parkingStandardsAndSecurity"]/*[local-name()="labelSecurityLevel"][1]', $record),
                'service_level' => $this->firstText($xpath, './*[local-name()="parkingStandardsAndSecurity"]/*[local-name()="labelServiceLevel"][1]', $record),
                'certified_secure' => $this->bool($this->firstText($xpath, './*[local-name()="parkingStandardsAndSecurity"]/*[local-name()="certifiedSecureParking"][1]', $record)),
            ],
        ];
    }

    private function firstValue(DOMXPath $xpath, string $expression, DOMElement $context): ?string
    {
        $container = $xpath->query($expression, $context)?->item(0);
        if (! $container instanceof DOMElement) {
            return null;
        }

        foreach ($xpath->query('.//*[local-name()="value"]', $container) as $value) {
            $clean = $this->clean($value->textContent);
            if ($clean !== null) {
                return $clean;
            }
        }

        return null;
    }

    private function firstText(DOMXPath $xpath, string $expression, DOMElement $context): ?string
    {
        $node = $xpath->query($expression, $context)?->item(0);

        return $node ? $this->clean($node->textContent) : null;
    }

    private function clean(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? $value);

        if ($value === '') {
            return null;
        }

        $normalized = mb_strtolower($value);
        if (in_array($normalized, ['n/a', 'n.a.', 'na', 'unknown', 'nicht definiert', 'undefined', 'null'], true)) {
            return null;
        }

        return $value;
    }

    private function positiveInt(?string $value): ?int
    {
        if ($value === null || ! preg_match('/^-?\d+$/', $value)) {
            return null;
        }

        $number = (int) $value;

        return $number > 0 ? $number : null;
    }

    private function number(?string $value): ?float
    {
        if ($value === null || ! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function bool(?string $value): ?bool
    {
        return match (mb_strtolower((string) $value)) {
            'true', '1', 'yes' => true,
            'false', '0', 'no' => false,
            default => null,
        };
    }
}
