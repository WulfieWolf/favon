<?php

namespace Tests\Unit;

use App\Services\Imports\NiedersachsenHubMapper;
use PHPUnit\Framework\TestCase;

class NiedersachsenHubMapperTest extends TestCase
{
    public function test_it_maps_only_structured_niedersachsen_data(): void
    {
        $item = [
            'global_id' => 'h_test',
            'title' => 'Test Stellplatz',
            'categories' => ['Wohnmobilstellplatz'],
            'texts' => [
                ['rel' => 'details', 'type' => 'text/plain', 'value' => 'Im Freitext steht kein Strom vorhanden.'],
            ],
            'country' => 'Deutschland',
            'zip' => '12345',
            'city' => 'Testort',
            'street' => 'Teststraße 1',
            'geo' => ['main' => ['latitude' => 52.1, 'longitude' => 9.2]],
            'features' => [
                'Allgemeine Stromversorgung',
                'Haustiere erlaubt',
                'Ruhige Lage',
                'für Familien',
                'Stellplatz min. 100 m²',
            ],
            'payment' => ['Barzahlung', 'Visa', 'Maestro', 'PayPal'],
            'numbers' => [
                ['type' => 'CountCampsites', 'value' => 15],
                ['type' => 'MinLengthOfStay', 'value' => 2],
                ['type' => 'DistanceToForest', 'value' => 750],
            ],
            'attributes' => [
                ['key' => 'license', 'value' => 'CC-BY'],
                ['key' => 'licenseurl', 'value' => 'https://creativecommons.org/licenses/by/4.0/'],
                ['key' => 'Currency', 'value' => 'EUR'],
            ],
            'media_objects' => [
                [
                    'rel' => 'default',
                    'url' => 'https://example.test/photo.jpg',
                    'type' => 'image/jpeg',
                    'source' => 'Tourismus Test',
                    'license' => 'CC-BY-SA',
                    'width' => 1920,
                    'height' => 1080,
                ],
            ],
            'changed' => '2026-09-28T10:00:00+02:00',
        ];

        $mapped = (new NiedersachsenHubMapper)->map($item);

        self::assertSame('h_test', $mapped['external_id']);
        self::assertSame('motorhome-pitch', $mapped['place']['suggested_place_type']);
        self::assertSame('open', $mapped['place']['opening_status']);
        self::assertSame(15, $mapped['place']['parking_spaces_total']);
        self::assertSame(2, $mapped['place']['minimum_stay_nights']);
        self::assertSame(100.0, $mapped['place']['pitch_area_min_m2']);

        self::assertSame('available', $mapped['features']['electricity']['status']);
        self::assertSame('available', $mapped['features']['dogs-allowed']['status']);
        self::assertSame('available', $mapped['features']['quiet-location']['status']);
        self::assertSame('available', $mapped['features']['suitable-families']['status']);
        self::assertSame('available', $mapped['features']['payment-cash']['status']);
        self::assertSame('available', $mapped['features']['payment-credit-card']['status']);
        self::assertSame('available', $mapped['features']['payment-debit-card']['status']);
        self::assertSame('available', $mapped['features']['payment-paypal']['status']);

        self::assertSame('known', $mapped['features']['distance-to-forest']['status']);
        self::assertSame(0.75, $mapped['features']['distance-to-forest']['value_number']);
        self::assertSame('m', $mapped['features']['distance-to-forest']['source_unit']);

        self::assertSame('CC-BY', $mapped['license']['code']);
        self::assertCount(1, $mapped['media']);
        self::assertSame('CC-BY-SA', $mapped['media'][0]['license']);

        // The free-text sentence is preserved as description, but never parsed
        // into a negative or additional feature value.
        self::assertSame('Im Freitext steht kein Strom vorhanden.', $mapped['place']['description']);
        self::assertArrayNotHasKey('shower', $mapped['features']);
    }
}
