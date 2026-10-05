<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PhotoDeletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class PhotoDeletionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_unused_profile_photo_uses_shared_physical_deletion_path(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $uuid = (string) Str::uuid();
        $path = 'profile-photos/'.$uuid.'.webp';

        Storage::disk('local')->put($path, 'profile');

        $photoId = DB::table('photos')->insertGetId([
            'uuid' => $uuid,
            'user_id' => $user->id,
            'storage_path' => $path,
            'status' => 'approved',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->where('id', $user->id)->update(['profile_photo_id' => $photoId]);

        $this->assertTrue(app(PhotoDeletionService::class)->purgeUnusedProfilePhoto($photoId, (int) $user->id));

        Storage::disk('local')->assertMissing($path);
        $this->assertDatabaseMissing('photos', ['id' => $photoId]);
        $this->assertNull(DB::table('users')->where('id', $user->id)->value('profile_photo_id'));
    }
}
