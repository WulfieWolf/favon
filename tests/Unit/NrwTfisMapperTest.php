<?php

namespace Tests\Unit;

use App\Services\Imports\NrwTfisMapper;
use PHPUnit\Framework\TestCase;

class NrwTfisMapperTest extends TestCase
{
    public function test_it_maps_camping_and_uses_info_ext_as_name_fallback(): void
    {
        $mapped = (new NrwTfisMapper)->map($this->feature([
            'uuid' => 'camp-1',
            'fkt' => 'Campingplatz',
            'nam' => null,
            'info_ext' => 'Jugendzeltplatz',
            'snr' => 'Camping',
        ], [7.1, 51.2]));

        self::assertSame('camp-1', $mapped['external_id']);
        self::assertSame('Jugendzeltplatz', $mapped['place']['name']);
        self::assertSame('info_ext', $mapped['source_name_origin']);
        self::assertSame('campground', $mapped['place']['suggested_place_type']);
        self::assertSame('open', $mapped['place']['opening_status']);
        self::assertSame(51.2, $mapped['place']['latitude']);
        self::assertSame(7.1, $mapped['place']['longitude']);
    }

    public function test_it_maps_explicit_hiking_parking(): void
    {
        $mapped = (new NrwTfisMapper)->map($this->feature([
            'uuid' => 'hike-1',
            'fkt' => 'Wanderparkplatz',
            'nam' => 'Waldparkplatz',
        ], [8.0, 50.0]));

        self::assertSame('hiking-parking', $mapped['place']['suggested_place_type']);
        self::assertSame('Waldparkplatz', $mapped['place']['name']);
        self::assertSame('nam', $mapped['source_name_origin']);
    }

    public function test_it_recognizes_hiking_parking_from_info_ext(): void
    {
        $mapped = (new NrwTfisMapper)->map($this->feature([
            'uuid' => 'park-1',
            'fkt' => 'Parkplatz',
            'info_ext' => 'Wanderparkplatz',
        ], [6.5, 51.0]));

        self::assertSame('hiking-parking', $mapped['place']['suggested_place_type']);
        self::assertSame('Wanderparkplatz', $mapped['place']['name']);
    }

    public function test_it_keeps_unnamed_parking_source_only_capable(): void
    {
        $mapped = (new NrwTfisMapper)->map($this->feature([
            'uuid' => 'park-2',
            'fkt' => 'Parkplatz',
            'nam' => null,
            'info_ext' => null,
        ], [6.6, 51.1]));

        self::assertSame('parking', $mapped['place']['suggested_place_type']);
        self::assertNull($mapped['place']['name']);
        self::assertNull($mapped['source_name_origin']);
        self::assertSame('dl-de-zero-2.0', $mapped['license']['code']);
    }

    private function feature(array $properties, array $coordinates): array
    {
        return [
            'type' => 'Feature',
            'properties' => $properties,
            'geometry' => [
                'type' => 'Point',
                'coordinates' => $coordinates,
            ],
        ];
    }
}
