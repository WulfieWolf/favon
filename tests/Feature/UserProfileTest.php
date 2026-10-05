<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use App\Services\PublicHandleService;
use Database\Seeders\VehicleTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_profile_respects_registered_only_visibility(): void
    {
        $user = User::factory()->create();
        $this->createProfile($user, [
            'bio' => 'Nur für Mitglieder sichtbar.',
            'hometown_city' => 'Essen',
            'hometown_country_code' => 'DE',
        ]);

        $this->get(route('users.profile', app(PublicHandleService::class)->automaticForUserId((int) $user->id)))
            ->assertOk()
            ->assertDontSee('Nur für Mitglieder sichtbar.')
            ->assertDontSee('Essen');

        $viewer = User::factory()->create();

        $this->actingAs($viewer)
            ->get(route('users.profile', app(PublicHandleService::class)->automaticForUserId((int) $user->id)))
            ->assertOk()
            ->assertSee('Nur für Mitglieder sichtbar.')
            ->assertSee('Essen');
    }

    public function test_birth_date_is_never_rendered_and_only_calculated_age_can_be_visible(): void
    {
        $user = User::factory()->create();
        $profile = $this->createProfile($user, [
            'birth_date' => now()->subYears(40)->subDays(2)->toDateString(),
        ]);

        DB::table('user_settings')->where('user_id', $user->id)->update([
            'age_visibility' => 'public',
        ]);

        $response = $this->get(route('users.profile', $profile->public_handle));

        $response->assertOk()
            ->assertSee('40')
            ->assertDontSee($profile->birth_date->format('Y-m-d'));
    }



    public function test_public_profile_shows_gamification_when_enabled(): void
    {
        $user = User::factory()->create();
        $profile = $this->createProfile($user);

        DB::table('xp_ledger')->insert([
            'user_id' => $user->id,
            'event_type' => 'manual_award',
            'source_type' => null,
            'source_id' => null,
            'place_id' => null,
            'action_key' => 'manual',
            'dedupe_key' => null,
            'xp' => 12,
            'description' => 'Test-XP für das Profil',
            'rule_version' => 'v1',
            'metadata' => null,
            'awarded_by' => null,
            'created_at' => now(),
        ]);

        $this->get(route('users.profile', $profile->public_handle))
            ->assertOk()
            ->assertSee('Level & XP')
            ->assertSee('Test-XP für das Profil')
            ->assertSee('+12 XP');
    }


    public function test_public_profile_groups_feature_xp_from_the_same_place_and_minute(): void
    {
        $user = User::factory()->create();
        $profile = $this->createProfile($user);
        $placeId = $this->createTestPlace($user->id, 'XP-Testplatz');

        for ($feature = 1; $feature <= 5; $feature++) {
            DB::table('xp_ledger')->insert([
                'user_id' => $user->id,
                'event_type' => 'place_info',
                'source_type' => 'place_feature',
                'source_id' => $feature,
                'place_id' => $placeId,
                'action_key' => 'feature:'.$feature,
                'dedupe_key' => 'test-feature-'.$feature,
                'xp' => 1,
                'description' => 'Merkmal bei „XP-Testplatz“ angegeben',
                'rule_version' => 'v1',
                'metadata' => null,
                'awarded_by' => null,
                'created_at' => now()->startOfMinute(),
            ]);
        }

        $this->withSession(['locale' => 'de'])->get(route('users.profile', $profile->public_handle))
            ->assertOk()
            ->assertSee('5 Merkmale bei „XP-Testplatz“ angegeben/aktualisiert')
            ->assertSee('+5 XP');
    }


    public function test_public_profile_groups_basisinfo_and_photo_xp(): void
    {
        $user = User::factory()->create();
        $profile = $this->createProfile($user);
        $placeId = $this->createTestPlace($user->id, 'Gruppenplatz');
        $time = now()->startOfMinute();

        foreach ([
            ['place_addresses.street', 'Straße bei „Gruppenplatz“ angegeben'],
            ['place_addresses.house_number', 'Hausnummer bei „Gruppenplatz“ angegeben'],
            ['place_addresses.city', 'Ort bei „Gruppenplatz“ angegeben'],
        ] as $index => [$actionKey, $description]) {
            DB::table('xp_ledger')->insert([
                'user_id' => $user->id,
                'event_type' => 'place_info',
                'source_type' => 'place',
                'source_id' => $placeId,
                'place_id' => $placeId,
                'action_key' => $actionKey,
                'dedupe_key' => 'basis-'.$index,
                'xp' => 1,
                'description' => $description,
                'rule_version' => 'v1',
                'metadata' => null,
                'awarded_by' => null,
                'created_at' => $time,
            ]);
        }

        for ($photo = 1; $photo <= 5; $photo++) {
            DB::table('xp_ledger')->insert([
                'user_id' => $user->id,
                'event_type' => 'photo_upload',
                'source_type' => 'photo',
                'source_id' => $photo,
                'place_id' => $placeId,
                'action_key' => 'photo',
                'dedupe_key' => 'photo-'.$photo,
                'xp' => 1,
                'description' => 'Foto zu „Gruppenplatz“ hochgeladen',
                'rule_version' => 'v1',
                'metadata' => null,
                'awarded_by' => null,
                'created_at' => $time,
            ]);
        }

        $this->withSession(['locale' => 'de'])->get(route('users.profile', $profile->public_handle))
            ->assertOk()
            ->assertSee('3 Basisinfos bei „Gruppenplatz“ angegeben/aktualisiert')
            ->assertSee('5 Fotos zu „Gruppenplatz“ hochgeladen')
            ->assertSee('+3 XP')
            ->assertSee('+5 XP');
    }

    public function test_join_date_can_be_hidden_from_public_profile(): void
    {
        $user = User::factory()->create([
            'created_at' => now()->subYears(2),
        ]);
        $profile = $this->createProfile($user);

        DB::table('user_settings')->where('user_id', $user->id)->update([
            'show_join_date' => false,
        ]);

        $this->get(route('users.profile', $profile->public_handle))
            ->assertOk()
            ->assertDontSee(__('community_profile.member_since', [
                'date' => $user->created_at->translatedFormat('F Y'),
            ]));
    }

    public function test_public_profile_uses_english_profile_labels(): void
    {
        app()->setLocale('en');

        $user = User::factory()->create();
        $profile = $this->createProfile($user, [
            'bio' => 'English profile text.',
        ]);

        DB::table('user_settings')->where('user_id', $user->id)->update([
            'bio_visibility' => 'public',
        ]);

        $this->get(route('users.profile', $profile->public_handle))
            ->assertOk()
            ->assertSee('Profile')
            ->assertSee('About me')
            ->assertSee('English profile text.')
            ->assertSee('Member since');
    }

    public function test_public_profile_hides_gamification_when_disabled(): void
    {
        $user = User::factory()->create();
        $profile = $this->createProfile($user);

        DB::table('user_settings')->where('user_id', $user->id)->update([
            'show_gamification' => false,
        ]);

        DB::table('xp_ledger')->insert([
            'user_id' => $user->id,
            'event_type' => 'manual_award',
            'source_type' => null,
            'source_id' => null,
            'place_id' => null,
            'action_key' => 'manual',
            'dedupe_key' => null,
            'xp' => 12,
            'description' => 'Unsichtbare Test-XP',
            'rule_version' => 'v1',
            'metadata' => null,
            'awarded_by' => null,
            'created_at' => now(),
        ]);

        $this->get(route('users.profile', $profile->public_handle))
            ->assertOk()
            ->assertDontSee('Level & XP')
            ->assertDontSee('Unsichtbare Test-XP');
    }

    public function test_profile_photo_visibility_is_enforced_by_download_route(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $profile = $this->createProfile($user);

        Storage::disk('local')->put('profile-photos/'.$user->id.'/avatar.jpg', 'fake-image');

        $photoId = DB::table('photos')->insertGetId([
            'user_id' => $user->id,
            'storage_path' => 'profile-photos/'.$user->id.'/avatar.jpg',
            'original_filename' => 'avatar.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 10,
            'width' => 100,
            'height' => 100,
            'status' => 'approved',
            'is_active' => true,
            'internal_comment' => 'test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user->update(['profile_photo_id' => $photoId]);

        DB::table('user_settings')->where('user_id', $user->id)->update([
            'profile_photo_visibility' => 'registered',
        ]);

        $this->get(route('users.profile.photo', $profile->public_handle))
            ->assertNotFound();

        $viewer = User::factory()->create();

        $this->actingAs($viewer)
            ->get(route('users.profile.photo', $profile->public_handle))
            ->assertOk();
    }


    public function test_custom_handle_cannot_use_reserved_automatic_prefix(): void
    {
        $user = User::factory()->create();
        $this->createProfile($user);

        $this->actingAs($user);

        Livewire::test('pages::settings.community-profile')
            ->set('newHandle', 'CW-ABCDE')
            ->call('checkHandle')
            ->assertSet('handleCheckOpen', true)
            ->assertSet('handleCheckAvailable', false);
    }


    public function test_handle_check_reports_available_name_before_finalizing(): void
    {
        $user = User::factory()->create();
        $this->createProfile($user);

        $this->actingAs($user);

        Livewire::test('pages::settings.community-profile')
            ->set('newHandle', 'wulfie.test')
            ->call('checkHandle')
            ->assertSet('handleCheckOpen', true)
            ->assertSet('handleCheckAvailable', true);

        $this->assertDatabaseMissing('user_profiles', [
            'user_id' => $user->id,
            'public_alias' => 'wulfie.test',
        ]);
    }

    public function test_finalizing_handle_preserves_other_profile_edits(): void
    {
        $user = User::factory()->create();
        $this->createProfile($user);

        $this->seed(VehicleTypeSeeder::class);
        $automaticHandle = app(PublicHandleService::class)->automaticForUserId((int) $user->id);

        $this->actingAs($user);

        Livewire::test('pages::settings.community-profile')
            ->set('newHandle', 'wulfie.final')
            ->set('bio', 'Diese Änderung darf nicht verloren gehen.')
            ->set('vehicleType', 'campervan')
            ->set('vehicleDetails', 'Ford Transit')
            ->call('checkHandle')
            ->assertSet('handleCheckAvailable', true)
            ->call('finalizeHandle')
            ->assertRedirect(route('community-profile.edit'));

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'public_handle' => $automaticHandle,
            'public_alias' => 'wulfie.final',
            'bio' => 'Diese Änderung darf nicht verloren gehen.',
            'vehicle_type' => 'campervan',
            'vehicle_details' => 'Ford Transit',
        ]);
    }

    public function test_social_links_are_not_rendered_on_public_profile(): void
    {
        $user = User::factory()->create();
        $profile = $this->createProfile($user);

        DB::table('user_profile_social_links')->insert([
            'user_id' => $user->id,
            'platform' => 'website',
            'url' => 'https://example.com/secret-link',
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('users.profile', $profile->public_handle))
            ->assertOk()
            ->assertDontSee('example.com/secret-link');
    }

    public function test_display_handle_can_only_be_finalized_once(): void
    {
        $user = User::factory()->create();
        $this->createProfile($user);

        $this->actingAs($user);

        $automaticHandle = app(PublicHandleService::class)->automaticForUserId((int) $user->id);

        Livewire::test('pages::settings.community-profile')
            ->set('newHandle', 'peter.mueller')
            ->call('finalizeHandle')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'public_handle' => $automaticHandle,
            'public_alias' => 'peter.mueller',
        ]);

        $this->get(route('users.profile', $automaticHandle))
            ->assertOk()
            ->assertSee('peter.mueller');

        $this->get(route('users.profile', 'peter.mueller'))
            ->assertOk()
            ->assertSee($automaticHandle);

        Livewire::test('pages::settings.community-profile')
            ->set('newHandle', 'anderer.name')
            ->call('checkHandle')
            ->assertSet('handleCheckOpen', true)
            ->assertSet('handleCheckAvailable', false);

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'public_handle' => $automaticHandle,
            'public_alias' => 'peter.mueller',
        ]);
    }

    public function test_profile_settings_store_personal_data_and_visibility(): void
    {
        $this->seed(VehicleTypeSeeder::class);

        $user = User::factory()->create();
        $this->createProfile($user);

        $this->actingAs($user);

        Livewire::test('pages::settings.community-profile')
            ->set('bio', 'Mit dem Camper unterwegs.')
            ->set('birthDate', '1980-05-12')
            ->set('gender', 'nonbinary_other')
            ->set('genderCustom', 'queer')
            ->set('vehicleType', 'campervan')
            ->set('vehicleDetails', 'Ford Transit')
            ->set('bioVisibility', 'registered')
            ->set('ageVisibility', 'private')
            ->set('genderVisibility', 'private')
            ->set('vehicleVisibility', 'registered')
            ->call('saveProfile')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'bio' => 'Mit dem Camper unterwegs.',
            'birth_date' => '1980-05-12 00:00:00',
            'gender' => 'nonbinary_other',
            'gender_custom' => 'queer',
            'vehicle_type' => 'campervan',
            'vehicle_details' => 'Ford Transit',
        ]);

        $this->assertDatabaseHas('user_settings', [
            'user_id' => $user->id,
            'bio_visibility' => 'registered',
            'age_visibility' => 'private',
            'gender_visibility' => 'private',
            'vehicle_visibility' => 'registered',
        ]);
    }


    private function createTestPlace(int $userId, string $name): int
    {
        $placeTypeId = DB::table('place_types')->insertGetId([
            'slug' => 'profile-test-'.uniqid(),
            'icon_id' => null,
            'sort_order' => 10,
            'is_active' => true,
            'is_searchable' => true,
            'internal_comment' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('places')->insertGetId([
            'place_type_id' => $placeTypeId,
            'name' => $name,
            'slug' => 'xp-testplatz-'.uniqid(),
            'latitude' => 51.0,
            'longitude' => 7.0,
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => 'unclear',
            'is_active' => true,
            'created_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createProfile(User $user, array $values = []): UserProfile
    {
        $handle = app(PublicHandleService::class)->automaticForUserId((int) $user->id);

        $profile = UserProfile::create(array_merge([
            'user_id' => $user->id,
            'public_handle' => $handle,
        ], $values));

        DB::table('user_settings')->insert([
            'user_id' => $user->id,
            'show_real_name' => false,
            'show_reviews_in_profile' => true,
            'show_photos_in_profile' => true,
            'show_join_date' => true,
            'show_activity_counts' => true,
            'allow_email_notifications' => true,
            'profile_photo_visibility' => 'public',
            'bio_visibility' => 'registered',
            'hometown_visibility' => 'registered',
            'age_visibility' => 'private',
            'gender_visibility' => 'private',
            'vehicle_visibility' => 'registered',
            'social_links_visibility' => 'registered',
            'show_gamification' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $profile;
    }
}
