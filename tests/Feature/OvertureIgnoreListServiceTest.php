<?php

namespace Tests\Feature;

use App\Services\Imports\OvertureIgnoreListService;
use App\Services\Imports\OverturePlacesCsvStageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OvertureIgnoreListServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_does_not_change_records_and_apply_ignores_by_external_id(): void
    {
        $sourceId = DB::table('external_sources')->insertGetId([
            'slug' => OverturePlacesCsvStageService::SOURCE_SLUG,
            'name' => 'Overture Test',
            'source_type' => 'test',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('external_records')->insert([
            'external_source_id' => $sourceId,
            'external_id' => 'stable-overture-id',
            'status' => 'active',
            'normalized_hash' => hash('sha256', '{}'),
            'normalized_data' => '{}',
            'first_seen_at' => now(),
            'last_seen_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $path = storage_path('framework/testing/overture-ignore.csv');
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents(
            $path,
            "external_id,name,ignore_reason\n"
            ."stable-overture-id,Irrelevant Test,Eindeutig kein Campingbezug\n",
        );

        try {
            $service = app(OvertureIgnoreListService::class);

            $preview = $service->run($path, false);
            $this->assertSame(1, $preview['would_ignore']);
            $this->assertSame(0, $preview['ignored']);
            $this->assertNull(DB::table('external_records')->where('external_id', 'stable-overture-id')->value('classification'));

            $applied = $service->run($path, true);
            $this->assertSame(1, $applied['ignored']);
            $this->assertSame('ignored', DB::table('external_records')->where('external_id', 'stable-overture-id')->value('classification'));

            $again = $service->run($path, true);
            $this->assertSame(1, $again['already_ignored']);
            $this->assertSame(0, $again['ignored']);
        } finally {
            @unlink($path);
        }
    }
}
