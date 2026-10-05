<?php

namespace Tests\Feature;

use App\Services\Imports\ManualCsvPlaceParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualCsvPlaceParserTest extends TestCase
{
    use RefreshDatabase;

    public function test_template_can_be_parsed_and_maps_features(): void
    {
        $parser = app(ManualCsvPlaceParser::class);
        $path = tempnam(sys_get_temp_dir(), 'camperwolf-csv-');

        file_put_contents($path, $parser->template());

        try {
            $records = $parser->parseFile($path);
        } finally {
            @unlink($path);
        }

        $this->assertCount(1, $records);
        $this->assertSame('example-001', $records[0]['external_id']);
        $this->assertSame('Beispiel Stellplatz', $records[0]['place']['name']);
        $this->assertSame(51.4556, $records[0]['place']['latitude']);
        $this->assertSame(25, $records[0]['place']['parking_spaces_total']);
        $this->assertSame('available', $records[0]['features']['waste-bins']['status']);
        $this->assertSame('unknown', $records[0]['features']['shower']['status']);
        $this->assertSame('unavailable', $records[0]['features']['dumping-station']['status']);
    }

    public function test_unknown_columns_fail_closed(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'camperwolf-csv-');
        file_put_contents($path, "external_id,name,mystery\n1,Test,foo\n");

        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('Unbekannte CSV-Spalte: mystery');

            app(ManualCsvPlaceParser::class)->parseFile($path);
        } finally {
            @unlink($path);
        }
    }

    public function test_partial_coordinates_are_rejected(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'camperwolf-csv-');
        file_put_contents($path, "external_id,name,latitude,longitude\n1,Test,51.2,\n");

        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('latitude und longitude müssen gemeinsam gesetzt oder leer sein');

            app(ManualCsvPlaceParser::class)->parseFile($path);
        } finally {
            @unlink($path);
        }
    }
}
