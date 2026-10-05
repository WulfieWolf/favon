<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReviewPhotoLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_interface_is_available_in_german_and_english(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::factory()->create();
        $placeTypeId = DB::table('place_types')->where('is_active', true)->value('id');

        DB::table('places')->insert([
            'place_type_id' => $placeTypeId,
            'name' => 'Untranslated user place name',
            'slug' => 'review-locale-test-place',
            'latitude' => 51.45,
            'longitude' => 7.01,
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => 'unclear',
            'is_active' => true,
            'created_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['locale' => 'de'])
            ->get(route('places.show', 'review-locale-test-place'))
            ->assertOk()
            ->assertSee('Untranslated user place name')
            ->assertSee('Bewertungen &amp; Rezensionen', false)
            ->assertSee('Sauberkeit')
            ->assertSee('Noch keine Bewertung');

        $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->get(route('places.show', 'review-locale-test-place'))
            ->assertOk()
            ->assertSee('Untranslated user place name')
            ->assertSee('Ratings &amp; reviews', false)
            ->assertSee('Cleanliness')
            ->assertSee('No ratings yet');
    }

    public function test_photo_library_is_available_in_german_and_english(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['locale' => 'de'])
            ->get(route('my.photos.index'))
            ->assertOk()
            ->assertSee('Meine Fotos')
            ->assertSee('Du hast noch keine Fotos hochgeladen.');

        $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->get(route('my.photos.index'))
            ->assertOk()
            ->assertSee('My photos')
            ->assertSee('You have not uploaded any photos yet.');
    }
}
