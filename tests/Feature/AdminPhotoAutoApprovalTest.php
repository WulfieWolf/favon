<?php

namespace Tests\Feature;

use App\Jobs\ProcessPhotoUpload;
use App\Models\User;
use App\Services\PlacePhotoService;
use App\Services\PlaceReviewService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminPhotoAutoApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_upload_is_marked_for_approval_after_processing(): void
    {
        Storage::fake('local');
        Queue::fake();
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create(['email_verified_at' => now()]);
        $roleId = DB::table('roles')->where('slug', 'admin')->value('id');
        DB::table('user_roles')->insert([
            'user_id' => $admin->id,
            'role_id' => $roleId,
            'assigned_by' => null,
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $placeTypeId = DB::table('place_types')->insertGetId([
            'slug' => 'admin-photo-test',
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
            'name' => 'Admin-Fotoplatz',
            'slug' => 'admin-fotoplatz',
            'latitude' => 51.0,
            'longitude' => 7.0,
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => 'unclear',
            'is_active' => true,
            'created_by' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $review = app(PlaceReviewService::class)->submit($admin, $placeId, [
            'cleanliness' => 4,
            'functionality' => 4,
            'condition' => 4,
            'safety' => 4,
            'usability' => 4,
        ], null);

        $count = app(PlacePhotoService::class)->queueUploads(
            $admin,
            (int) $placeId,
            (int) $review['review_id'],
            [UploadedFile::fake()->image('platz.jpg', 1200, 800)],
        );

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('photos', [
            'user_id' => $admin->id,
            'status' => 'processing',
            'internal_comment' => PlacePhotoService::AUTO_APPROVE_INTERNAL_COMMENT,
        ]);
        Queue::assertPushed(ProcessPhotoUpload::class);
    }
}
