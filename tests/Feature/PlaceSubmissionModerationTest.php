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
            ->assertSee('Männer')
            ->assertSee('Frauen')
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
            'target_audience' => 'men',
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
                'target_audience' => 'women',
                'has_gloryhole' => '0',
                'city' => 'Essen',
                'country_code' => 'DE',
            ])
            ->assertRedirect(route('admin.place-submissions.index'));

        $published = DB::table('places')->where('id', $place->id)->first();

        $this->assertSame('published', $published->publication_status);
        $this->assertSame('Park ohne Tippfehler', $published->name);
        $this->assertSame('women', $published->target_audience);
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
            'target_audience' => 'men',
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

    public function test_regular_user_cannot_open_admin_submission_queue(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.place-submissions.index'))
            ->assertNotFound();
    }
}
