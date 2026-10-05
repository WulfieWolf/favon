<?php

namespace Tests\Unit;

use App\Services\Imports\Datex2ParkingDryRunService;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class Datex2ParkingDryRunServiceTest extends TestCase
{
    #[Test]
    public function it_analyzes_a_single_xml_file(): void
    {
        $path = base_path('tests/Fixtures/datex2-parking-sample.xml');
        $result = app(Datex2ParkingDryRunService::class)->analyzePath($path);

        $this->assertCount(1, $result['files']);
        $this->assertSame(2, $result['totals']['records']);
        $this->assertSame(1, $result['totals']['unknown_total_capacity']);
        $this->assertSame(2, $result['totals']['access_points']);
        $this->assertSame(1, $result['distributions']['vehicle_types']['car']);
        $this->assertSame(2, $result['distributions']['vehicle_types']['lorry']);
        $this->assertSame(1, $result['distributions']['equipment_types']['refuseBin']);
        $this->assertSame(1, $result['distributions']['usage_scenarios']['truckParking']);
        $this->assertSame(2, $result['distributions']['usage_scenarios']['restArea']);
        $this->assertSame(2, $result['distributions']['site_locations']['motorway']);
        $this->assertSame(1, $result['distributions']['access_categories']['vehicleExit']);
        $this->assertSame(1, $result['distributions']['access_categories']['vehicleEntrance']);
    }

    #[Test]
    public function it_analyzes_all_xml_files_in_a_directory_but_not_subdirectories(): void
    {
        $dir = storage_path('framework/testing/datex2-dry-run');
        @mkdir($dir.DIRECTORY_SEPARATOR.'nested', 0777, true);

        $fixture = file_get_contents(base_path('tests/Fixtures/datex2-parking-sample.xml'));
        file_put_contents($dir.DIRECTORY_SEPARATOR.'a.xml', $fixture);
        file_put_contents($dir.DIRECTORY_SEPARATOR.'b.XML', $fixture);
        file_put_contents($dir.DIRECTORY_SEPARATOR.'ignored.txt', $fixture);
        file_put_contents($dir.DIRECTORY_SEPARATOR.'nested'.DIRECTORY_SEPARATOR.'c.xml', $fixture);

        try {
            $result = app(Datex2ParkingDryRunService::class)->analyzePath($dir);

            $this->assertCount(2, $result['files']);
            $this->assertSame(4, $result['totals']['records']);
        } finally {
            @unlink($dir.DIRECTORY_SEPARATOR.'a.xml');
            @unlink($dir.DIRECTORY_SEPARATOR.'b.XML');
            @unlink($dir.DIRECTORY_SEPARATOR.'ignored.txt');
            @unlink($dir.DIRECTORY_SEPARATOR.'nested'.DIRECTORY_SEPARATOR.'c.xml');
            @rmdir($dir.DIRECTORY_SEPARATOR.'nested');
            @rmdir($dir);
        }
    }

    #[Test]
    public function it_rejects_paths_without_xml_files(): void
    {
        $dir = storage_path('framework/testing/datex2-empty');
        @mkdir($dir, 0777, true);

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Keine XML-Dateien gefunden.');

            app(Datex2ParkingDryRunService::class)->analyzePath($dir);
        } finally {
            @rmdir($dir);
        }
    }
}
