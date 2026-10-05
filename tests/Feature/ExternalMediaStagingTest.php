<?php

namespace Tests\Feature;

use App\Jobs\ProcessPhotoUpload;
use App\Services\Imports\ExternalPlacePhotoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExternalMediaStagingTest extends TestCase
{
    use RefreshDatabase;

    public function test_external_media_is_staged_without_download_and_can_be_imported_explicitly(): void
    {
        Storage::fake('local');
        Queue::fake();
        Http::fake([
            'https://example.test/source.jpg' => Http::response('jpeg-bytes', 200, ['Content-Type' => 'image/jpeg']),
        ]);

        $placeTypeId = DB::table('place_types')->where('is_active', true)->value('id');
        self::assertNotNull($placeTypeId);

        $placeId = (int) DB::table('places')->insertGetId([
            'place_type_id' => $placeTypeId,
            'name' => 'Media Staging Place',
            'slug' => 'media-staging-place',
            'latitude' => 52.0,
            'longitude' => 9.0,
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => 'unclear',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sourceId = (int) DB::table('external_sources')->insertGetId([
            'slug' => 'media-staging-source',
            'name' => 'Media Staging Source',
            'source_type' => 'api',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $mediaId = hash('sha256', 'https://example.test/source.jpg');

        $recordId = (int) DB::table('external_records')->insertGetId([
            'external_source_id' => $sourceId,
            'external_id' => 'media-stage-1',
            'place_id' => $placeId,
            'status' => 'active',
            'classification' => 'created',
            'normalized_data' => json_encode([
                'external_id' => 'media-stage-1',
                'place' => ['name' => 'Media Staging Place'],
                'media' => [[
                    'id' => $mediaId,
                    'url' => 'https://example.test/source.jpg',
                    'license' => 'CC-BY',
                    'author' => 'Example Author',
                    'width' => 1600,
                    'height' => 1200,
                ]],
            ], JSON_UNESCAPED_SLASHES),
            'first_seen_at' => now(),
            'last_seen_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(ExternalPlacePhotoService::class);

        $inventory = $service->inventoryLinkedSource($sourceId);

        self::assertSame(1, $inventory['media']);
        self::assertSame(1, $inventory['supported']);
        self::assertSame(0, $inventory['imported']);
        self::assertSame(1, $inventory['pending']);
        Http::assertNothingSent();

        $pending = $service->pendingMedia('media-staging-source', 10);

        self::assertCount(1, $pending);
        self::assertSame($recordId.':'.$mediaId, $pending[0]['key']);

        $result = $service->importSelected([$pending[0]['key']]);

        self::assertSame(1, $result['queued']);
        self::assertSame(0, $service->pendingCount('media-staging-source'));

        $this->assertDatabaseHas('photos', [
            'external_source_id' => $sourceId,
            'external_record_id' => $recordId,
            'external_media_id' => $mediaId,
            'status' => 'processing',
        ]);

        Queue::assertPushed(ProcessPhotoUpload::class);
        Http::assertSentCount(1);
    }
}
