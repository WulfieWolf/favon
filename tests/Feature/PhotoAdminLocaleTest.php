<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PhotoAdminLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_photo_moderation_and_library_are_available_in_german_and_english(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::factory()->create(['email_verified_at' => now()]);
        $author = User::factory()->create(['name' => 'Photo Author']);
        $reporter = User::factory()->create(['name' => 'Photo Reporter']);
        config(['camperwolf.owner_email' => $owner->email]);

        $placeId = DB::table('places')->insertGetId([
            'place_type_id' => DB::table('place_types')->where('is_active', true)->value('id'),
            'name' => 'Photo Locale Place',
            'slug' => 'photo-locale-place',
            'latitude' => 51.45,
            'longitude' => 7.01,
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => 'unclear',
            'is_active' => true,
            'created_by' => $author->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $uuid = (string) Str::uuid();
        $photoId = DB::table('photos')->insertGetId([
            'uuid' => $uuid,
            'user_id' => $author->id,
            'storage_path' => 'photos/'.$uuid.'/detail.webp',
            'source_path' => null,
            'preview_path' => 'photos/'.$uuid.'/preview.webp',
            'original_filename' => null,
            'mime_type' => 'image/webp',
            'file_size' => 1024,
            'preview_file_size' => 512,
            'width' => 1600,
            'height' => 1200,
            'status' => 'pending',
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
            'place_review_id' => null,
            'photo_id' => $photoId,
            'photo_type' => 'community',
            'sort_order' => 10,
            'is_thumbnail_eligible' => true,
            'thumbnail_excluded_by' => null,
            'thumbnail_excluded_at' => null,
            'thumbnail_exclusion_reason' => null,
            'is_active' => true,
            'internal_comment' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('photo_reports')->insert([
            'photo_id' => $photoId,
            'reported_by' => $reporter->id,
            'reason' => 'wrong_place',
            'comment' => 'Original photo report comment',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($owner)
            ->withSession(['locale' => 'de'])
            ->get(route('admin.photos.index'))
            ->assertOk()
            ->assertSee('Neue Uploads prüfen und gemeldete Fotos bearbeiten.')
            ->assertSee('Falscher Platz')
            ->assertSee('Original photo report comment');

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.photos.index'))
            ->assertOk()
            ->assertSee('Review new uploads and process reported photos.')
            ->assertSee('Wrong place')
            ->assertSee('Select rejection reason')
            ->assertSee('Optional additional note for the author');

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.photos.library'))
            ->assertOk()
            ->assertSee('Review and manage all uploaded images in one place.')
            ->assertSee('Place, author, email or UUID')
            ->assertSee('Photo Locale Place');

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->post(route('admin.photos.approve', $photoId))
            ->assertSessionHas('ui_toast', 'Photo approved.');

        $this->assertDatabaseHas('photos', ['id' => $photoId, 'status' => 'approved']);
    }
}
