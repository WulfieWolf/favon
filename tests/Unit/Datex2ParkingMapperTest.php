<?php

namespace Tests\Unit;

use App\Services\Imports\Datex2ParkingMapper;
use App\Services\Imports\Datex2ParkingParser;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class Datex2ParkingMapperTest extends TestCase
{
    #[Test]
    public function it_maps_known_equipment_and_preserves_source_specific_data(): void
    {
        $xml = file_get_contents(base_path('tests/Fixtures/datex2-parking-sample.xml'));
        $records = app(Datex2ParkingParser::class)->parse($xml);
        $mapped = app(Datex2ParkingMapper::class)->map($records[0]);

        $this->assertSame('rest-area', $mapped['place']['suggested_place_type']);
        $this->assertSame('parking_location', $mapped['place']['coordinate_source']);
        $this->assertSame('available', $mapped['features']['waste-bins']['status']);
        $this->assertSame('available', $mapped['features']['rest-picnic-area']['status']);
        $this->assertSame(15, $mapped['vehicle_capacities']['car']);
        $this->assertSame(3, $mapped['vehicle_capacities']['lorry']);
        $this->assertSame(3, $mapped['vehicle_type_capacities']['truck']);
        $this->assertSame(15, $mapped['vehicle_type_capacities']['car']);
        $this->assertContains('truck', $mapped['vehicle_type_hints']);
        $this->assertContains('car', $mapped['vehicle_type_hints']);
        $this->assertArrayNotHasKey('caravan', array_flip($mapped['vehicle_type_hints']));
    }

    #[Test]
    public function it_maps_all_supported_datex_vehicle_capacities_to_camperwolf_types(): void
    {
        $mapped = app(Datex2ParkingMapper::class)->map([
            'external_id' => 'vehicles',
            'name' => 'Vehicle Mapping',
            'equipment' => [],
            'parking_spaces_by_vehicle' => [
                'car' => 10,
                'carWithTrailer' => 4,
                'lorry' => 8,
                'bus' => 2,
                'heavyHaulageVehicle' => 1,
            ],
            'access_points' => [],
        ]);

        $this->assertSame([
            'car' => 10,
            'car-with-trailer' => 4,
            'truck' => 8,
            'coach' => 2,
        ], $mapped['vehicle_type_capacities']);
        $this->assertArrayNotHasKey('heavy-haulage', $mapped['vehicle_type_capacities']);
    }

    #[Test]
    public function it_uses_an_entrance_coordinate_only_as_explicit_fallback(): void
    {
        $mapped = app(Datex2ParkingMapper::class)->map([
            'external_id' => 'fallback',
            'name' => 'Fallback',
            'latitude' => null,
            'longitude' => null,
            'equipment' => [],
            'parking_spaces_by_vehicle' => [],
            'access_points' => [
                [
                    'category' => 'vehicleExit',
                    'latitude' => 51.1,
                    'longitude' => 7.1,
                ],
                [
                    'category' => 'vehicleEntrance',
                    'latitude' => 51.2,
                    'longitude' => 7.2,
                ],
            ],
        ]);

        $this->assertSame(51.2, $mapped['place']['latitude']);
        $this->assertSame(7.2, $mapped['place']['longitude']);
        $this->assertSame('access_vehicleEntrance', $mapped['place']['coordinate_source']);
    }

    #[Test]
    public function zero_vehicle_capacities_do_not_create_suitability_hints(): void
    {
        $mapped = app(Datex2ParkingMapper::class)->map([
            'external_id' => 'zero',
            'name' => 'Zero',
            'equipment' => [],
            'parking_spaces_by_vehicle' => [
                'car' => null,
                'carWithTrailer' => null,
                'lorry' => null,
            ],
            'access_points' => [],
        ]);

        $this->assertSame([], $mapped['vehicle_capacities']);
        $this->assertSame([], $mapped['vehicle_type_capacities']);
        $this->assertSame([], $mapped['vehicle_type_hints']);
    }
}
