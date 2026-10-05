<?php

namespace Tests\Unit;

use App\Services\Imports\Datex2ParkingParser;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class Datex2ParkingParserTest extends TestCase
{
    #[Test]
    public function it_normalizes_datex2_parking_records_without_writing_business_data(): void
    {
        $xml = file_get_contents(base_path('tests/Fixtures/datex2-parking-sample.xml'));
        $records = app(Datex2ParkingParser::class)->parse($xml);

        $this->assertCount(2, $records);

        $immensitz = $records[0];
        $this->assertSame('DE-BW-081165', $immensitz['external_id']);
        $this->assertSame('15', $immensitz['source_version']);
        $this->assertSame('Immensitz Ost', $immensitz['name']);
        $this->assertSame(18, $immensitz['parking_spaces_total']);
        $this->assertSame(3, $immensitz['parking_spaces_by_vehicle']['lorry']);
        $this->assertSame(15, $immensitz['parking_spaces_by_vehicle']['car']);
        $this->assertTrue($immensitz['free_of_charge']);
        $this->assertSame('Niederlassung Südwest', $immensitz['operator']['name']);
        $this->assertSame('An der BAB A 81', $immensitz['address']['street']);
        $this->assertNull($immensitz['address']['house_number']);
        $this->assertNull($immensitz['address']['postal_code']);
        $this->assertNull($immensitz['address']['city']);
        $this->assertSame(['truckParking', 'restArea'], $immensitz['usage_scenarios']);
        $this->assertSame('refuseBin', $immensitz['equipment'][0]['type']);
        $this->assertCount(2, $immensitz['access_points']);
        $this->assertSame('vehicleExit', $immensitz['access_points'][0]['category']);
        $this->assertSame('A 81', $immensitz['access_points'][0]['road']);
        $this->assertSame('Würzburg', $immensitz['access_points'][0]['direction']);
    }

    #[Test]
    public function it_streams_parking_records_from_a_file(): void
    {
        $records = iterator_to_array(
            app(Datex2ParkingParser::class)->parseFile(base_path('tests/Fixtures/datex2-parking-sample.xml')),
            false,
        );

        $this->assertCount(2, $records);
        $this->assertSame('DE-BW-081165', $records[0]['external_id']);
        $this->assertSame('Immensitz Ost', $records[0]['name']);
        $this->assertSame(18, $records[0]['parking_spaces_total']);
        $this->assertSame(3, $records[0]['parking_spaces_by_vehicle']['lorry']);
        $this->assertSame(15, $records[0]['parking_spaces_by_vehicle']['car']);
        $this->assertSame('DE-HE-003277', $records[1]['external_id']);
    }

    #[Test]
    public function zero_capacity_is_treated_as_unknown(): void
    {
        $xml = file_get_contents(base_path('tests/Fixtures/datex2-parking-sample.xml'));
        $records = app(Datex2ParkingParser::class)->parse($xml);

        $record = $records[1];

        $this->assertSame('DE-HE-003277', $record['external_id']);
        $this->assertNull($record['parking_spaces_total']);
        $this->assertArrayHasKey('lorry', $record['parking_spaces_by_vehicle']);
        $this->assertNull($record['parking_spaces_by_vehicle']['lorry']);
        $this->assertSame('9', $record['address']['house_number']);
        $this->assertSame('34593', $record['address']['postal_code']);
        $this->assertSame('Remsfeld', $record['address']['city']);
    }

    #[Test]
    public function invalid_xml_is_rejected(): void
    {
        $this->expectException(\RuntimeException::class);

        app(Datex2ParkingParser::class)->parse('<not-valid');
    }
}
