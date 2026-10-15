<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PlaceSubmissionModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_open_favon_place_submission_form(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['locale' => 'de'])
            ->get(route('places.create'))
            ->assertOk()
            ->assertSee('Gay-Sauna')
            ->assertSee('Park / Wald')
            ->assertSee('Gloryhole')
            ->assertSee('Zielgruppe')
            ->assertSee('MM')
            ->assertSee('MF')
            ->assertSee('Straße und Hausnummer')
            ->assertSee('Deutschland')
            ->assertSee('Adresse oder Ort suchen')
            ->assertSee('data-favon-address-search', false)
            ->assertSee('data-favon-place-picker', false);
    }


    public function test_dashboard_uses_localized_place_type_names(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::factory()->create();
        $typeId = (int) DB::table('place_types')->where('slug', 'public-toilet')->value('id');

        DB::table('places')->insert([
            'place_type_id' => $typeId,
            'name' => 'Test Klappe',
            'slug' => 'test-klappe',
            'latitude' => 51.2,
            'longitude' => 7.2,
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => 'unclear',
            'is_active' => true,
            'created_by' => $user->id,
            'approved_by' => $user->id,
            'approved_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['locale' => 'de'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Öffentliche Toilette / Klappe');

        $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Public toilet / cottage');
    }

    public function test_owner_can_correct_and_approve_submission_and_user_is_notified(): void
    {
        $this->seed(DatabaseSeeder::class);

        config(['favon.owner_email' => 'owner@example.test']);
        $owner = User::factory()->create(['email' => 'owner@example.test']);
        $creator = User::factory()->create();
        $typeId = (int) DB::table('place_types')->where('slug', 'park-forest')->value('id');

        $this->actingAs($creator)->post(route('places.store'), [
            'place_type_id' => $typeId,
            'name' => 'Park mit Tippfehler',
            'latitude' => 51.1,
            'longitude' => 7.1,
            'environment' => 'outdoor',
            'target_audience' => ['mm'],
            'has_gloryhole' => '0',
        ])->assertRedirect(route('dashboard'));

        $place = DB::table('places')->where('name', 'Park mit Tippfehler')->first();

        $this->actingAs($owner)
            ->post(route('admin.place-submissions.approve', $place->id), [
                'place_type_id' => $typeId,
                'name' => 'Park ohne Tippfehler',
                'latitude' => 51.1001,
                'longitude' => 7.1001,
                'description' => 'Hinterer Bereich des Parks.',
                'environment' => 'outdoor',
                'target_audience' => ['mm', 'mf'],
                'has_gloryhole' => '0',
                'city' => 'Essen',
                'country_code' => 'DE',
            ])
            ->assertRedirect(route('admin.place-submissions.index'));

        $published = DB::table('places')->where('id', $place->id)->first();

        $this->assertSame('published', $published->publication_status);
        $this->assertSame('Park ohne Tippfehler', $published->name);
        $this->assertTrue((bool) $published->target_mm);
        $this->assertTrue((bool) $published->target_mf);
        $this->assertNull($published->target_audience);
        $this->assertSame((int) $owner->id, (int) $published->approved_by);
        $this->assertNotNull($published->approved_at);

        $notification = DB::table('user_notifications')
            ->where('user_id', $creator->id)
            ->where('type', 'place_submission_approved')
            ->first();

        $this->assertNotNull($notification);
        $this->assertStringContainsString('Park ohne Tippfehler', $notification->message);
    }

    public function test_owner_rejection_notifies_creator_then_deletes_pending_place(): void
    {
        $this->seed(DatabaseSeeder::class);

        config(['favon.owner_email' => 'owner@example.test']);
        $owner = User::factory()->create(['email' => 'owner@example.test']);
        $creator = User::factory()->create();
        $typeId = (int) DB::table('place_types')->where('slug', 'public-toilet')->value('id');

        $this->actingAs($creator)->post(route('places.store'), [
            'place_type_id' => $typeId,
            'name' => 'Privater Ort',
            'latitude' => 51.2,
            'longitude' => 7.2,
            'target_audience' => ['mm'],
        ])->assertRedirect(route('dashboard'));

        $placeId = (int) DB::table('places')->where('name', 'Privater Ort')->value('id');

        $this->actingAs($owner)
            ->post(route('admin.place-submissions.reject', $placeId), [
                'reason' => 'Privatadresse',
            ])
            ->assertRedirect(route('admin.place-submissions.index'));

        $this->assertDatabaseMissing('places', ['id' => $placeId]);

        $notification = DB::table('user_notifications')
            ->where('user_id', $creator->id)
            ->where('type', 'place_submission_rejected')
            ->first();

        $this->assertNotNull($notification);
        $this->assertStringContainsString('Privater Ort', $notification->message);
        $this->assertStringContainsString('Privatadresse', $notification->message);
    }


    public function test_published_profile_shows_description_and_audience_filters_match_either_selection(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();
        $typeId = (int) DB::table('place_types')->where('slug', 'park-forest')->value('id');

        foreach ([
            ['MM Platz', 'mm-platz', true, false, 'Weg hinter dem Parkeingang.'],
            ['MF Platz', 'mf-platz', false, true, null],
            ['Beide Platz', 'beide-platz', true, true, null],
        ] as [$name, $slug, $mm, $mf, $description]) {
            DB::table('places')->insert([
                'place_type_id' => $typeId,
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'target_mm' => $mm,
                'target_mf' => $mf,
                'latitude' => 51.2,
                'longitude' => 7.2,
                'publication_status' => 'published',
                'legal_status' => 'unclear',
                'opening_status' => 'unclear',
                'is_active' => true,
                'created_by' => $user->id,
                'approved_by' => $user->id,
                'approved_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->actingAs($user)->get(route('places.show', 'mm-platz'))
            ->assertOk()
            ->assertSee('Weg hinter dem Parkeingang.');

        $this->actingAs($user)->get(route('dashboard', ['target_audience' => ['mm']]))
            ->assertOk()->assertSee('MM Platz')->assertSee('Beide Platz')->assertDontSee('MF Platz');

        $this->actingAs($user)->get(route('dashboard', ['target_audience' => ['mf']]))
            ->assertOk()->assertDontSee('MM Platz')->assertSee('Beide Platz')->assertSee('MF Platz');

        $this->actingAs($user)->get(route('dashboard', ['target_audience' => ['mm', 'mf']]))
            ->assertOk()->assertSee('MM Platz')->assertSee('Beide Platz')->assertSee('MF Platz');
    }


    public function test_only_owner_can_edit_published_places_without_notifying_creator(): void
    {
        $this->seed(DatabaseSeeder::class);
        config(['favon.owner_email' => 'owner@example.test']);
        $owner = User::factory()->create(['email' => 'owner@example.test']);
        $creator = User::factory()->create();
        $typeId = (int) DB::table('place_types')->where('slug', 'park-forest')->value('id');

        DB::table('places')->insert([
            'place_type_id' => $typeId,
            'name' => 'Alter Platz',
            'slug' => 'alter-platz',
            'description' => 'Alte Beschreibung',
            'latitude' => 51.2,
            'longitude' => 7.2,
            'target_mm' => true,
            'target_mf' => false,
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => 'unclear',
            'is_active' => true,
            'created_by' => $creator->id,
            'approved_by' => $owner->id,
            'approved_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $placeId = (int) DB::table('places')->where('slug', 'alter-platz')->value('id');
        $payload = [
            'place_type_id' => $typeId,
            'name' => 'Neuer Platzname',
            'description' => 'Neue Beschreibung',
            'latitude' => 51.3,
            'longitude' => 7.3,
            'target_audience' => ['mm', 'mf'],
            'city' => 'Essen',
            'country_code' => 'DE',
        ];

        $this->actingAs($creator)->get(route('places.show', 'alter-platz'))
            ->assertOk()->assertDontSee(route('admin.places.edit', $placeId));

        $this->actingAs($creator)->get(route('admin.places.edit', $placeId))->assertForbidden();
        $this->actingAs($creator)->put(route('admin.places.update', $placeId), $payload)->assertForbidden();

        $this->actingAs($owner)->get(route('places.show', 'alter-platz'))
            ->assertOk()->assertSee(route('admin.places.edit', $placeId));
        $this->actingAs($owner)->get(route('admin.places.edit', $placeId))
            ->assertOk()->assertSee('Alte Beschreibung');

        $this->actingAs($owner)->put(route('admin.places.update', $placeId), $payload)
            ->assertRedirect(route('places.show', 'alter-platz'));

        $this->assertDatabaseHas('places', [
            'id' => $placeId,
            'name' => 'Neuer Platzname',
            'description' => 'Neue Beschreibung',
            'target_mm' => true,
            'target_mf' => true,
            'publication_status' => 'published',
        ]);
        $this->assertDatabaseHas('place_addresses', ['place_id' => $placeId, 'city' => 'Essen']);
        $this->assertSame(0, DB::table('user_notifications')->where('user_id', $creator->id)->count());
    }

    public function test_regular_user_cannot_open_admin_submission_queue(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.place-submissions.index'))
            ->assertNotFound();
    }
}
