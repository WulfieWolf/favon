<?php

namespace App\Services\Imports;

class NiedersachsenHubMapper
{
    private const FEATURE_MAP = [
        'Spielplatz' => ['playground'],
        'Kinderspielplatz' => ['playground'],
        'Haustiere erlaubt' => ['dogs-allowed'],
        'Allgemeine Abwasserentsorgung' => ['dumping-station'],
        'Allgemeine Frischwasserversorgung' => ['fresh-water'],
        'Allgemeine Abfallentsorgungsmöglichkeit' => ['waste-bins'],
        'Allgemeine Stromversorgung' => ['electricity'],
        'Stellplatz mit Stromanschluss' => ['electricity'],
        'Sanitäranlage mit WC und Duschen' => ['toilet', 'shower'],
        'Sanitäranlage mit WC' => ['toilet'],
        'WLAN' => ['wifi'],
        'WLAN am Stellplatz' => ['wifi'],
        'Waschmaschine' => ['washing-machine'],
        'Trockner' => ['dryer'],
        'Gasflaschenaustausch' => ['gas-bottle-exchange'],
        'Gasverkauf' => ['gas-bottle-sales'],
        'Grillplatz' => ['barbecue-area'],
        'Kiosk' => ['kiosk-on-site'],
        'Gastronomie am Platz' => ['restaurant-on-site'],
        'Restaurant vorhanden' => ['restaurant-on-site'],
        'Gaststätte' => ['restaurant-on-site'],
        'Restaurant' => ['restaurant-on-site'],
        'Brötchenservice' => ['bread-roll-service'],
        'eAuto-Ladestation' => ['ev-charging'],
        'Fahrradvermietung (gegen Gebühr)' => ['rental-bike'],
        'Eigene Mietfahrräder' => ['rental-bike'],
        'Bootsvermietung' => ['rental-boat'],
        'Mietwohnwagen' => ['rental-caravan'],
        'Miethütte' => ['rental-cabin'],
        'Mietmobilheim' => ['rental-mobile-home'],
        'Aufenthaltsraum' => ['common-room'],
        'Gästeküche' => ['kitchen'],
        'Kochgelegenheit' => ['kitchen'],
        'Kochmöglichkeit' => ['kitchen'],
        'Gefriermöglichkeit' => ['freezer'],
        'Gästekühlschrank' => ['fridge'],
        'Haartrockner' => ['hairdryer'],
        'Wickelraum' => ['baby-changing'],
        'Wickelauflage' => ['baby-changing'],
        'Allgemeiner Geschirrspülbereich' => ['dishwashing-sink'],
        'WC für Menschen mit Behinderung Lobby' => ['accessible-toilet'],
        'Schwimmbad' => ['swimming-pool'],
        'Freibad' => ['swimming-pool'],
        'Hallenbad' => ['swimming-pool'],
        'Fahrradunterstellmöglichkeit' => ['bicycle-storage'],
        'Fahrradunterstellplatz (abschliessbar)' => ['bicycle-storage'],
        'Stellplatz mit Wasseranschluss' => ['water-hookup-at-pitch'],
        'Abwasseranschluss am Platz' => ['wastewater-hookup-at-pitch'],
        'eBike-Ladestation' => ['ebike-charging'],

        'Ruhige Lage' => ['quiet-location'],
        'Zentrale Lage' => ['central-location'],
        'Ländliche Lage' => ['rural-location'],
        'Ortsrand' => ['edge-of-town'],
        'Am See' => ['at-lake'],
        'Am Strand' => ['at-beach'],
        'Flussnähe' => ['near-river'],
        'Waldnähe' => ['near-forest'],
        'An der Nordsee' => ['at-coast'],
        'Am Hafen' => ['at-harbour'],

        'für Familien' => ['suitable-families'],
        'Familienfreundlich' => ['suitable-families'],
        'Kinderfreundlich' => ['suitable-families'],
        'für Kinder (jedes Alter)' => ['suitable-families'],
        'für Gruppen' => ['suitable-groups'],
        'Gruppen' => ['suitable-groups'],
        'Stellplätze für Gruppen' => ['suitable-groups'],
        'Kinderwagentauglich' => ['stroller-friendly'],

        'Barzahlung vor Ort' => ['payment-cash'],
        'Visa' => ['payment-credit-card'],
        'Mastercard' => ['payment-credit-card'],
        'American Express' => ['payment-credit-card'],
        'Diners Club' => ['payment-credit-card'],
        'Weitere Kreditkarten' => ['payment-credit-card'],
        'Maestro' => ['payment-debit-card'],
        'Debitkarte' => ['payment-debit-card'],
        'Paypal' => ['payment-paypal'],
        'Apple Pay' => ['payment-apple-pay'],
        'Google Pay' => ['payment-google-pay'],
        'Überweisung' => ['payment-bank-transfer'],
        'Rechnung' => ['payment-invoice'],
    ];

    private const PAYMENT_MAP = [
        'Barzahlung' => 'payment-cash',
        'Giro Pay' => null,
        'PayPal' => 'payment-paypal',
        'Apple Pay' => 'payment-apple-pay',
        'Google Pay' => 'payment-google-pay',
        'Überweisung' => 'payment-bank-transfer',
        'Rechnung' => 'payment-invoice',
        'Sofortüberweisung' => null,
        'Visa' => 'payment-credit-card',
        'Mastercard' => 'payment-credit-card',
        'American Express' => 'payment-credit-card',
        'Diners Club' => 'payment-credit-card',
        'Weitere Kreditkarten' => 'payment-credit-card',
        'Maestro' => 'payment-debit-card',
    ];

    private const NUMBER_MAP = [
        'DistanceToCityCenter' => 'distance-to-city-center',
        'DistanceToForest' => 'distance-to-forest',
        'DistanceToBicyclePath' => 'distance-to-bicycle-path',
        'DistanceToHikingTrail' => 'distance-to-hiking-trail',
        'DistanceToStation' => 'distance-to-station',
        'DistanceToBusStop' => 'distance-to-bus-stop',
        'DistanceToLake' => 'distance-to-lake',
    ];

    public function map(array $item): array
    {
        $features = [];
        $sourceFeatures = array_values(array_filter(
            $item['features'] ?? [],
            fn ($value) => is_string($value) && trim($value) !== '',
        ));

        foreach ($sourceFeatures as $sourceFeature) {
            foreach (self::FEATURE_MAP[$sourceFeature] ?? [] as $slug) {
                $features[$slug] = $this->available($sourceFeature);
            }
        }

        foreach ($item['payment'] ?? [] as $payment) {
            if (! is_string($payment)) {
                continue;
            }

            $slug = self::PAYMENT_MAP[$payment] ?? null;
            if ($slug) {
                $features[$slug] = $this->available($payment);
            }
        }

        if (in_array('Girocard/EC-Karte', $sourceFeatures, true) || in_array('Debitkarte', $sourceFeatures, true)) {
            $features['payment-debit-card'] = $this->available('Girocard/EC-Karte / Debitkarte');
        }

        $numbers = $this->numbers($item['numbers'] ?? []);

        foreach (self::NUMBER_MAP as $sourceType => $slug) {
            if (! isset($numbers[$sourceType]) || ! is_numeric($numbers[$sourceType])) {
                continue;
            }

            // destination.one distance values are metres; Camperwolf distance features use kilometres.
            $features[$slug] = [
                'status' => 'known',
                'conflict' => false,
                'value_number' => round(((float) $numbers[$sourceType]) / 1000, 3),
                'unit_key' => 'km',
                'source_value' => (float) $numbers[$sourceType],
                'source_unit' => 'm',
            ];
        }

        $pitchArea = in_array('Stellplatz min. 100 m²', $sourceFeatures, true) ? 100.0 : null;
        $categories = is_array($item['categories'] ?? null) ? $item['categories'] : [];

        return [
            'external_id' => (string) ($item['global_id'] ?? $item['id'] ?? ''),
            'source_updated_at' => $item['changed'] ?? null,
            'place' => [
                'name' => $this->text($item['title'] ?? null),
                'latitude' => $this->number($item['geo']['main']['latitude'] ?? null),
                'longitude' => $this->number($item['geo']['main']['longitude'] ?? null),
                'coordinate_source' => 'destination.one',
                'suggested_place_type' => $this->placeType($categories),
                'opening_status' => 'open',
                'address' => [
                    'country' => $this->text($item['country'] ?? null),
                    'country_code' => 'DE',
                    'postal_code' => $this->text($item['zip'] ?? null),
                    'city' => $this->text($item['city'] ?? null),
                    'street' => $this->text($item['street'] ?? null),
                ],
                'operator' => [
                    'name' => $this->text($item['company'] ?? null),
                    'phone' => $this->text($item['phone'] ?? null),
                    'email' => $this->text($item['email'] ?? null),
                    'url' => $this->text($item['web'] ?? null),
                ],
                'description' => $this->plainText($item['texts'] ?? [], 'details'),
                'parking_spaces_total' => $this->positiveInt($numbers['CountCampsites'] ?? null),
                'minimum_stay_nights' => $this->positiveInt($numbers['MinLengthOfStay'] ?? null),
                'pitch_area_min_m2' => $pitchArea,
                'min_price' => $this->positiveNumber($numbers['MinPrice'] ?? null),
                'currency' => $this->attribute($item['attributes'] ?? [], 'Currency') ?? 'EUR',
            ],
            'features' => $features,
            'license' => [
                'code' => $this->attribute($item['attributes'] ?? [], 'license'),
                'url' => $this->attribute($item['attributes'] ?? [], 'licenseurl'),
            ],
            'media' => $this->media($item['media_objects'] ?? []),
            'source_categories' => $categories,
            'source_features' => $sourceFeatures,
            'source_payments' => array_values(array_filter($item['payment'] ?? [], 'is_string')),
            'source_numbers' => $numbers,
        ];
    }

    private function media(array $mediaObjects): array
    {
        $media = [];

        foreach ($mediaObjects as $object) {
            if (! is_array($object) || ! str_starts_with((string) ($object['type'] ?? ''), 'image/')) {
                continue;
            }

            $url = $this->text($object['url'] ?? null);
            $license = $this->text($object['license'] ?? null);

            if ($url === null || $license === null) {
                continue;
            }

            $media[] = [
                'id' => hash('sha256', $url),
                'url' => $url,
                'mime_type' => $this->text($object['type'] ?? null),
                'rel' => $this->text($object['rel'] ?? null),
                'license' => $license,
                'source' => $this->text($object['source'] ?? null),
                'author' => $this->text($object['author'] ?? null),
                'copyright' => $this->text($object['copyright'] ?? null),
                'alt' => $this->text($object['alt'] ?? null),
                'width' => $this->positiveInt($object['width'] ?? null),
                'height' => $this->positiveInt($object['height'] ?? null),
            ];
        }

        return $media;
    }

    private function numbers(array $numbers): array
    {
        $result = [];

        foreach ($numbers as $number) {
            if (! is_array($number)) {
                continue;
            }

            $type = $this->text($number['type'] ?? null);
            if ($type !== null && array_key_exists('value', $number) && is_numeric($number['value'])) {
                $result[$type] = (float) $number['value'];
            }
        }

        return $result;
    }

    private function attribute(array $attributes, string $key): ?string
    {
        foreach ($attributes as $attribute) {
            if (is_array($attribute) && ($attribute['key'] ?? null) === $key) {
                return $this->text($attribute['value'] ?? null);
            }
        }

        return null;
    }

    private function plainText(array $texts, string $rel): ?string
    {
        foreach ($texts as $text) {
            if (is_array($text) && ($text['rel'] ?? null) === $rel && ($text['type'] ?? null) === 'text/plain') {
                return $this->text($text['value'] ?? null);
            }
        }

        return null;
    }

    private function placeType(array $categories): string
    {
        if (in_array('Wohnmobilstellplatz', $categories, true)) {
            return 'motorhome-pitch';
        }

        if (in_array('Campingplatz', $categories, true)) {
            return 'campground';
        }

        if (in_array('Zelten', $categories, true)) {
            return 'tent-site';
        }

        return 'camping-outdoor';
    }

    private function available(string $source): array
    {
        return ['status' => 'available', 'conflict' => false, 'source_value' => $source];
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

    private function positiveNumber(mixed $value): ?float
    {
        return is_numeric($value) && (float) $value > 0 ? (float) $value : null;
    }

    private function positiveInt(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }
}
