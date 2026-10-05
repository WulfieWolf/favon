<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AccountDeletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccountDeletionContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_text_and_photo_file_are_removed_but_rating_history_remains(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        DB::table('user_profiles')->insert([
            'user_id' => $user->id,
            'public_handle' => 'CW-CONTENT-TEST',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $typeId = DB::table('place_types')->insertGetId([
            'slug' => 'content-delete-test',
            'icon_id' => null,
            'sort_order' => 10,
            'is_active' => true,
            'is_searchable' => true,
            'internal_comment' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $placeId = DB::table('places')->insertGetId([
            'place_type_id' => $typeId,
            'name' => 'Test place',
            'slug' => 'content-delete-test-place',
            'latitude' => 51,
            'longitude' => 7,
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => 'unclear',
            'is_active' => true,
            'created_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $reviewId = DB::table('place_reviews')->insertGetId([
            'place_id' => $placeId,
            'user_id' => $user->id,
            'status' => 'active',
            'current_published_at' => now()->subDay(),
            'current_expires_at' => now()->addYear(),
            'verified_visit' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $versionId = DB::table('place_review_versions')->insertGetId([
            'review_id' => $reviewId,
            'version_number' => 1,
            'rating_cleanliness' => 4,
            'rating_functionality' => 4,
            'rating_condition' => 4,
            'rating_safety' => 4,
            'rating_usability' => 4,
            'overall_score' => 4.0,
            'review_text' => 'Text to remove',
            'is_public' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addYear(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('place_reviews')->where('id', $reviewId)->update(['current_version_id' => $versionId]);

        Storage::disk('local')->put('photos/delete-test/detail.webp', 'data');
        $photoId = DB::table('photos')->insertGetId([
            'uuid' => '11111111-1111-4111-8111-111111111112',
            'user_id' => $user->id,
            'storage_path' => 'photos/delete-test/detail.webp',
            'status' => 'approved',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(AccountDeletionService::class)->finalize($user->id);

        $this->assertDatabaseHas('place_review_versions', [
            'id' => $versionId,
            'review_text' => null,
            'overall_score' => 4.0,
            'is_public' => false,
        ]);
        $this->assertDatabaseHas('place_reviews', [
            'id' => $reviewId,
            'status' => 'deleted',
        ]);
        $this->assertDatabaseHas('photos', [
            'id' => $photoId,
            'status' => 'deleted',
            'is_active' => false,
        ]);
        Storage::disk('local')->assertMissing('photos/delete-test/detail.webp');
    }
}
