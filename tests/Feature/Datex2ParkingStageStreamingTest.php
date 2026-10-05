<?php

namespace Tests\Feature;

use App\Services\Imports\Datex2ParkingStageService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class Datex2ParkingStageStreamingTest extends TestCase
{
    use RefreshDatabase;

    public function test_fixture_is_staged_through_streaming_path(): void
    {
        $this->seed(DatabaseSeeder::class);

        $result = app(Datex2ParkingStageService::class)->stagePath(
            base_path('tests/Fixtures/datex2-parking-sample.xml'),
            true,
            false,
        );

        $this->assertSame(2, $result['mapped_records']);
        $this->assertSame(2, $result['staging']['records']);
        $this->assertSame(2, $result['staging']['new']);
        $this->assertSame(0, $result['staging']['missing_marked']);
        $this->assertTrue($result['validation']['valid']);

        $sourceId = (int) $result['staging']['source_id'];

        $this->assertSame(
            2,
            DB::table('external_records')
                ->where('external_source_id', $sourceId)
                ->where('status', 'active')
                ->count(),
        );

        $this->assertDatabaseHas('external_records', [
            'external_source_id' => $sourceId,
            'external_id' => 'DE-BW-081165',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('external_records', [
            'external_source_id' => $sourceId,
            'external_id' => 'DE-HE-003277',
            'status' => 'active',
        ]);
    }
}
