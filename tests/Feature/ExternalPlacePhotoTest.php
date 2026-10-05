<?php

namespace Tests\Feature;

use App\Services\PlacePhotoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ExternalPlacePhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_external_photo_without_review_is_visible_for_place(): void
    {
        $placeTypeId = DB::table('place_types')->where('is_active', true)->value('id');
        self::assertNotNull($placeTypeId);

        $placeId = (int) DB::table('places')->insertGetId([
            'place_type_id' => $placeTypeId,
            'name' => 'External Photo Place',
            'slug' => 'external-photo-place',
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
            'slug' => 'photo-test-source',
            'name' => 'Photo Test Source',
            'source_type' => 'api',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $recordId = (int) DB::table('external_records')->insertGetId([
            'external_source_id' => $sourceId,
            'external_id' => 'photo-record-1',
            'place_id' => $placeId,
            'status' => 'active',
            'first_seen_at' => now(),
            'last_seen_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $uuid = (string) Str::uuid();
        $photoId = (int) DB::table('photos')->insertGetId([
            'uuid' => $uuid,
            'user_id' => null,
            'external_source_id' => $sourceId,
            'external_record_id' => $recordId,
            'external_media_id' => hash('sha256', 'https://example.test/photo.jpg'),
            'source_url' => 'https://example.test/photo.jpg',
            'source_author' => 'Test Author',
            'source_license_code' => 'CC-BY',
            'source_license_url' => 'https://creativecommons.org/licenses/by/4.0/',
            'source_provider' => 'Photo Test Source',
            'source_retrieved_at' => now(),
            'storage_path' => 'photos/'.$uuid.'/detail.webp',
            'source_path' => null,
            'preview_path' => 'photos/'.$uuid.'/preview.webp',
            'mime_type' => 'image/webp',
            'file_size' => 1000,
            'preview_file_size' => 500,
            'width' => 1200,
            'height' => 800,
            'status' => 'approved',
            'moderated_at' => now(),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('place_photos')->insert([
            'place_id' => $placeId,
            'place_review_id' => null,
            'photo_id' => $photoId,
            'photo_type' => 'external',
            'sort_order' => 1000,
            'is_thumbnail_eligible' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $photos = app(PlacePhotoService::class)->publicPhotosForPlace($placeId);

        self::assertSame(1, $photos->total());
        self::assertSame($uuid, $photos->first()->uuid);
        self::assertSame('CC-BY', $photos->first()->source_license_code);
        self::assertSame('Test Author', $photos->first()->source_author);

        $thumbnail = app(PlacePhotoService::class)
            ->thumbnailsForPlaces(collect([$placeId]))
            ->get($placeId);

        self::assertNotNull($thumbnail);
        self::assertSame($uuid, $thumbnail->uuid);
    }
}
