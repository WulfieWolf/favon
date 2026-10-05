<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PlaceProfileLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_place_profile_system_texts_are_available_in_german_and_english(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::factory()->create();
        $placeTypeId = DB::table('place_types')->where('is_active', true)->value('id');

        DB::table('places')->insert([
            'place_type_id' => $placeTypeId,
            'name' => 'Unübersetzter Nutzername',
            'slug' => 'locale-test-place',
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

        $this->withSession(['locale' => 'de'])
            ->get(route('places.show', 'locale-test-place'))
            ->assertOk()
            ->assertSee('Unübersetzter Nutzername')
            ->assertSee('Platzdetails')
            ->assertSee('Öffnungszeiten')
            ->assertDontSee('<h2 class="text-base font-semibold">Preise</h2>', false);

        $this->withSession(['locale' => 'en'])
            ->get(route('places.show', 'locale-test-place'))
            ->assertOk()
            ->assertSee('Unübersetzter Nutzername')
            ->assertSee('Place details')
            ->assertSee('Opening hours')
            ->assertDontSee('<h2 class="text-base font-semibold">Prices</h2>', false);
    }

    public function test_place_profile_shows_last_content_update_and_outdated_warning(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::factory()->create();
        $placeTypeId = DB::table('place_types')->where('is_active', true)->value('id');

        $placeId = DB::table('places')->insertGetId([
            'place_type_id' => $placeTypeId,
            'name' => 'Alter Testplatz',
            'slug' => 'old-test-place',
            'latitude' => 51.45,
            'longitude' => 7.01,
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => 'unclear',
            'is_active' => true,
            'created_by' => $user->id,
            'created_at' => now()->subYears(2),
            'updated_at' => now(),
        ]);

        DB::table('place_history')->insert([
            'place_id' => $placeId,
            'actor_type' => 'user',
            'action' => 'place_created',
            'summary' => 'Platz erstellt',
            'metadata' => json_encode([]),
            'is_public' => true,
            'user_id' => $user->id,
            'created_at' => now()->subYears(2),
        ]);

        $this->withSession(['locale' => 'de'])
            ->get(route('places.show', 'old-test-place'))
            ->assertOk()
            ->assertSee('DatenScore')
            ->assertSee('Letzte Änderung:')
            ->assertSee('Die letzte erfasste Änderung liegt länger als ein Jahr zurück. Einzelne Angaben können inzwischen veraltet sein.');

        $this->withSession(['locale' => 'en'])
            ->get(route('places.show', 'old-test-place'))
            ->assertOk()
            ->assertSee('DataScore')
            ->assertSee('Last change:')
            ->assertSee('The last recorded change was more than a year ago. Some information may now be outdated.');
    }
}
