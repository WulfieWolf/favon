<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PlacePhotoService;
use App\Services\PhotoProcessingService;
use App\Services\PlaceReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Imagick;
use ImagickPixel;
use Tests\TestCase;

class PlacePhotoServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_approved_active_review_photos_are_public(): void
    {
        [$author, $placeId, $reviewId] = $this->reviewFixture();
        $approved = $this->createPhoto($author, $placeId, $reviewId, 'approved');
        $pending = $this->createPhoto($author, $placeId, $reviewId, 'pending');

        $photos = app(PlacePhotoService::class)->publicPhotosForReviews(collect([$reviewId]));

        $this->assertSame([$approved], $photos->get($reviewId)->pluck('id')->all());
        $this->assertNotContains($pending, $photos->get($reviewId)->pluck('id')->all());

        DB::table('place_reviews')->where('id', $reviewId)->update(['status' => 'deleted']);

        $this->assertTrue(app(PlacePhotoService::class)
            ->publicPhotosForReviews(collect([$reviewId]))
            ->isEmpty());
    }

    public function test_place_gallery_contains_only_public_review_photos(): void
    {
        [$author, $placeId, $reviewId] = $this->reviewFixture();
        $approved = $this->createPhoto($author, $placeId, $reviewId, 'approved');
        $this->createPhoto($author, $placeId, $reviewId, 'pending');
        $legacyWithoutUuid = $this->createPhoto($author, $placeId, $reviewId, 'approved');
        DB::table('photos')->where('id', $legacyWithoutUuid)->update(['uuid' => null]);

        $gallery = app(PlacePhotoService::class)->publicPhotosForPlace($placeId);

        $this->assertSame(1, $gallery->total());
        $this->assertSame($approved, (int) $gallery->items()[0]->id);
    }

    public function test_administrative_library_can_sort_by_helpful_votes(): void
    {
        [$author, $placeId, $reviewId] = $this->reviewFixture();
        $first = $this->createPhoto($author, $placeId, $reviewId, 'approved');
        $second = $this->createPhoto($author, $placeId, $reviewId, 'approved');
        $voter = User::factory()->create();
        $this->assertTrue(app(PlacePhotoService::class)->toggleHelpful($voter, $second, true));

        $library = app(PlacePhotoService::class)->administrativeLibrary([
            'search' => '',
            'status' => '',
            'activity' => 'all',
        ], 'helpful', 'desc');

        $this->assertSame([$second, $first], collect($library->items())->pluck('id')->map(fn ($id) => (int) $id)->all());
    }

    public function test_helpful_vote_is_unique_and_author_cannot_vote_for_own_photo(): void
    {
        [$author, $placeId, $reviewId] = $this->reviewFixture();
        DB::table('users')->where('id', $author->id)->update(['locale' => 'de']);
        $author->locale = 'de';
        $voter = User::factory()->create();
        $photoId = $this->createPhoto($author, $placeId, $reviewId, 'approved');
        $service = app(PlacePhotoService::class);

        $this->assertFalse($service->toggleHelpful($author, $photoId, true));
        $this->assertTrue($service->toggleHelpful($voter, $photoId, true));
        $this->assertFalse($service->toggleHelpful($voter, $photoId, true));
        $this->assertSame(1, DB::table('photo_helpful_votes')->where('photo_id', $photoId)->count());
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $author->id,
            'type' => 'photo_helpful_milestone',
        ]);
        $this->assertTrue($service->toggleHelpful($voter, $photoId, false));
    }

    public function test_helpful_notifications_are_grouped_by_place_and_external_photos_cannot_be_voted_or_reported(): void
    {
        [$author, $placeId, $reviewId] = $this->reviewFixture();
        $voter = User::factory()->create();
        $first = $this->createPhoto($author, $placeId, $reviewId, 'approved');
        $second = $this->createPhoto($author, $placeId, $reviewId, 'approved');
        $service = app(PlacePhotoService::class);

        $this->assertTrue($service->toggleHelpful($voter, $first, true));

        $notification = DB::table('user_notifications')
            ->where('user_id', $author->id)
            ->where('type', 'photo_helpful_milestone')
            ->first();

        $this->assertNotNull($notification);
        $this->assertStringContainsString('Dein Foto', (string) $notification->message);

        $secondVoter = User::factory()->create();
        $this->assertTrue($service->toggleHelpful($secondVoter, $second, true));

        $notifications = DB::table('user_notifications')
            ->where('user_id', $author->id)
            ->where('type', 'photo_helpful_milestone')
            ->get();

        $this->assertCount(1, $notifications);
        $this->assertStringContainsString('2 deiner Fotos', (string) $notifications->first()->message);

        $sourceId = DB::table('external_sources')->insertGetId([
            'slug' => 'photo-test-external-'.uniqid(),
            'name' => 'Photo test external source',
            'source_type' => 'api',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('photos')->where('id', $second)->update(['external_source_id' => $sourceId]);

        $thirdVoter = User::factory()->create();
        $this->assertFalse($service->toggleHelpful($thirdVoter, $second, true));
        $this->assertFalse($service->report($thirdVoter, $second, 'other', null));
    }

    public function test_thumbnail_priority_and_vote_ties_keep_the_current_choice(): void
    {
        [$author, $placeId, $reviewId] = $this->reviewFixture();
        $moderator = User::factory()->create();
        $first = $this->createPhoto($author, $placeId, $reviewId, 'approved');
        $second = $this->createPhoto($author, $placeId, $reviewId, 'approved');
        $service = app(PlacePhotoService::class);

        DB::table('place_photo_settings')->insert([
            'place_id' => $placeId,
            'fallback_photo_id' => $first,
            'vote_photo_id' => null,
            'admin_photo_id' => null,
            'admin_selected_by' => null,
            'admin_selected_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame($first, (int) $service->thumbnailsForPlaces(collect([$placeId]))->get($placeId)->id);

        $voterOne = User::factory()->create();
        $voterTwo = User::factory()->create();
        $this->assertTrue($service->toggleHelpful($voterOne, $second, true));
        $this->assertSame($second, (int) $service->thumbnailsForPlaces(collect([$placeId]))->get($placeId)->id);

        $this->assertTrue($service->toggleHelpful($voterTwo, $first, true));
        $this->assertSame($second, (int) $service->thumbnailsForPlaces(collect([$placeId]))->get($placeId)->id);

        $service->setAdminThumbnail($moderator, $first);
        $thumbnail = $service->thumbnailsForPlaces(collect([$placeId]))->get($placeId);
        $this->assertSame($first, (int) $thumbnail->id);
        $this->assertSame('admin', $thumbnail->selection_source);
    }

    public function test_public_asset_is_unavailable_after_review_deletion(): void
    {
        Storage::fake('local');
        [$author, $placeId, $reviewId] = $this->reviewFixture();
        $photoId = $this->createPhoto($author, $placeId, $reviewId, 'approved');
        $photo = DB::table('photos')->where('id', $photoId)->first();
        Storage::disk('local')->put($photo->preview_path, 'preview');

        $this->get(route('photos.show', ['uuid' => $photo->uuid, 'variant' => 'preview']))->assertOk();

        app(PlaceReviewService::class)->deleteCurrent($author, $placeId);

        $this->get(route('photos.show', ['uuid' => $photo->uuid, 'variant' => 'preview']))->assertNotFound();
        $this->assertDatabaseHas('place_photos', ['photo_id' => $photoId, 'is_active' => false]);
    }

    public function test_processing_creates_proportional_metadata_free_webp_variants_and_deletes_source(): void
    {
        Storage::fake('local');
        $author = User::factory()->create();
        $uuid = (string) Str::uuid();
        $sourcePath = 'photo-uploads/'.$uuid.'.source';

        $source = new Imagick();
        $source->newImage(2000, 1000, new ImagickPixel('#336699'));
        $source->setImageFormat('jpeg');
        $source->setImageProperty('comment', 'private-camera-data');
        Storage::disk('local')->put($sourcePath, $source->getImageBlob());
        $source->clear();
        $source->destroy();

        $photoId = DB::table('photos')->insertGetId([
            'uuid' => $uuid,
            'user_id' => $author->id,
            'storage_path' => 'photos/'.$uuid.'/detail.webp',
            'source_path' => $sourcePath,
            'preview_path' => null,
            'original_filename' => null,
            'mime_type' => 'image/jpeg',
            'file_size' => Storage::disk('local')->size($sourcePath),
            'preview_file_size' => null,
            'width' => 2000,
            'height' => 1000,
            'status' => 'processing',
            'moderated_by' => null,
            'moderated_at' => null,
            'moderation_reason' => null,
            'processing_error' => null,
            'is_active' => true,
            'internal_comment' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(PhotoProcessingService::class)->process($photoId);

        $photo = DB::table('photos')->where('id', $photoId)->first();
        $this->assertSame('pending', $photo->status);
        $this->assertSame('image/webp', $photo->mime_type);
        $this->assertSame(1920, (int) $photo->width);
        $this->assertSame(960, (int) $photo->height);
        Storage::disk('local')->assertMissing($sourcePath);
        Storage::disk('local')->assertExists($photo->storage_path);
        Storage::disk('local')->assertExists($photo->preview_path);

        $detail = new Imagick(Storage::disk('local')->path($photo->storage_path));
        $preview = new Imagick(Storage::disk('local')->path($photo->preview_path));
        $this->assertSame('WEBP', strtoupper($detail->getImageFormat()));
        $this->assertSame(1920, $detail->getImageWidth());
        $this->assertSame(960, $detail->getImageHeight());
        $this->assertFalse($detail->getImageProperty('comment'));
        $this->assertSame(640, $preview->getImageWidth());
        $this->assertSame(320, $preview->getImageHeight());
        $detail->clear();
        $detail->destroy();
        $preview->clear();
        $preview->destroy();
    }

    private function reviewFixture(): array
    {
        $author = User::factory()->create();
        $placeTypeId = DB::table('place_types')->insertGetId([
            'slug' => 'photo-test-'.uniqid(),
            'icon_id' => null,
            'sort_order' => 10,
            'is_active' => true,
            'is_searchable' => true,
            'internal_comment' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $placeId = DB::table('places')->insertGetId([
            'place_type_id' => $placeTypeId,
            'name' => 'Foto-Testplatz',
            'slug' => 'foto-testplatz-'.uniqid(),
            'latitude' => 51.0,
            'longitude' => 7.0,
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => 'unclear',
            'is_active' => true,
            'created_by' => $author->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $review = app(PlaceReviewService::class)->submit($author, $placeId, [
            'cleanliness' => 4,
            'functionality' => 4,
            'condition' => 4,
            'safety' => 4,
            'usability' => 4,
        ], null);

        return [$author, $placeId, $review['review_id']];
    }

    private function createPhoto(User $author, int $placeId, int $reviewId, string $status): int
    {
        $uuid = (string) Str::uuid();
        $photoId = DB::table('photos')->insertGetId([
            'uuid' => $uuid,
            'user_id' => $author->id,
            'storage_path' => 'photos/'.$uuid.'/detail.webp',
            'source_path' => null,
            'preview_path' => 'photos/'.$uuid.'/preview.webp',
            'original_filename' => null,
            'mime_type' => 'image/webp',
            'file_size' => 1000,
            'preview_file_size' => 500,
            'width' => 1600,
            'height' => 1200,
            'status' => $status,
            'moderated_by' => null,
            'moderated_at' => null,
            'moderation_reason' => null,
            'processing_error' => null,
            'is_active' => true,
            'internal_comment' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('place_photos')->insert([
            'place_id' => $placeId,
            'place_review_id' => $reviewId,
            'photo_id' => $photoId,
            'photo_type' => 'review',
            'sort_order' => $photoId * 10,
            'is_thumbnail_eligible' => true,
            'thumbnail_excluded_by' => null,
            'thumbnail_excluded_at' => null,
            'thumbnail_exclusion_reason' => null,
            'is_active' => true,
            'internal_comment' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $photoId;
    }
}
