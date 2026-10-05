<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PlaceContactRevealTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_is_not_in_place_page_html_and_can_be_revealed_separately(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::factory()->create();
        $placeTypeId = (int) DB::table('place_types')->where('is_active', true)->value('id');

        $placeId = (int) DB::table('places')->insertGetId([
            'place_type_id' => $placeTypeId,
            'name' => 'Kontakt Testplatz',
            'slug' => 'kontakt-testplatz',
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

        DB::table('place_contacts')->insert([
            'place_id' => $placeId,
            'contact_type' => 'email',
            'value' => 'kontakt@example.test',
            'sort_order' => 10,
            'is_active' => true,
            'version_valid_from' => now(),
            'version_valid_until' => null,
            'internal_comment' => null,
            'created_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withSession(['locale' => 'de'])
            ->get(route('places.show', 'kontakt-testplatz'))
            ->assertOk()
            ->assertSee('E-Mail anzeigen')
            ->assertDontSee('kontakt@example.test', false);

        $response = $this->getJson(route('places.contact.email', 'kontakt-testplatz'))
            ->assertOk()
            ->assertJson([
                'email' => 'kontakt@example.test',
            ]);

        $cacheControl = (string) $response->headers->get('Cache-Control');
        $this->assertStringContainsString('private', $cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('max-age=0', $cacheControl);
    }

    public function test_email_reveal_returns_404_for_unpublished_or_missing_email(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::factory()->create();
        $placeTypeId = (int) DB::table('place_types')->where('is_active', true)->value('id');

        DB::table('places')->insert([
            'place_type_id' => $placeTypeId,
            'name' => 'Ohne Kontakt',
            'slug' => 'ohne-kontakt',
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

        $this->getJson(route('places.contact.email', 'ohne-kontakt'))
            ->assertNotFound();
    }
}
