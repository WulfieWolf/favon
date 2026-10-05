<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PlaceInfoSuggestionTest extends TestCase
{
    use RefreshDatabase;

    public function test_registered_user_can_open_and_submit_existing_place_information_suggestion(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::factory()->create();

        $placeTypeId = DB::table('place_types')->where('is_active', true)->value('id');
        $vehicleTypeId = DB::table('vehicle_types')->where('is_active', true)->value('id');

        $placeId = DB::table('places')->insertGetId([
            'place_type_id' => $placeTypeId,
            'name' => 'Suggestion Testplatz',
            'slug' => 'suggestion-testplatz',
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

        DB::table('place_addresses')->insert([
            'place_id' => $placeId,
            'country_code' => 'DE',
            'region_id' => null,
            'postal_code' => '45127',
            'city' => 'Essen',
            'street' => null,
            'house_number' => null,
            'address_addition' => null,
            'is_active' => true,
            'version_valid_from' => now(),
            'version_valid_until' => null,
            'internal_comment' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['locale' => 'de'])
            ->get(route('places.info-suggest.edit', 'suggestion-testplatz'))
            ->assertOk()
            ->assertSee('Informationen ergänzen')
            ->assertSee('Änderungen vorschlagen');

        $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->get(route('places.info-suggest.edit', 'suggestion-testplatz'))
            ->assertOk()
            ->assertSee('Add information')
            ->assertSee('Suggest changes')
            ->assertSee('Suggestion Testplatz');

        $this->actingAs($user)
            ->post(route('places.info-suggest.update', 'suggestion-testplatz'), [
                'name' => 'Suggestion Testplatz Neu',
                'place_type_id' => $placeTypeId,
                'latitude' => 51.4505,
                'longitude' => 7.0105,
                'legal_status' => 'overnight_allowed',
                'operator_name' => 'Test Betreiber',
                'pitch_count' => 24,
                'opening_status' => 'open',
                'website' => 'https://example.test/camping',
                'description' => 'Ein ausreichend langer Beschreibungstext für den Testplatz.',
                'directions' => 'Zufahrt über die Teststraße.',
                'access_information' => 'Einfahrt rund um die Uhr möglich.',
                'country_code' => 'DE',
                'postal_code' => '45127',
                'city' => 'Essen',
                'street' => 'Teststraße',
                'house_number' => '1',
                'address_addition' => null,
                'vehicle_type_ids' => [$vehicleTypeId],
                'vehicle_capacities' => [$vehicleTypeId => 12],
                'comment' => 'Teständerung',
            ])
            ->assertRedirect(route('places.show', 'suggestion-testplatz'));

        $this->assertGreaterThan(0, DB::table('change_requests')->where('place_id', $placeId)->count());
        $this->assertSame(
            1,
            DB::table('change_requests')
                ->where('place_id', $placeId)
                ->distinct()
                ->count('group_uuid'),
        );

        $this->assertDatabaseHas('change_requests', [
            'place_id' => $placeId,
            'operation' => 'create',
            'status' => 'pending',
            'submitted_by' => $user->id,
        ]);

        $vehicleFieldId = DB::table('suggestable_fields')
            ->where('target_table', 'place_vehicle_types')
            ->where('target_field', 'vehicle_type_id')
            ->value('id');

        $vehicleRequest = DB::table('change_requests')
            ->where('place_id', $placeId)
            ->where('suggestable_field_id', $vehicleFieldId)
            ->where('operation', 'create')
            ->first();

        $this->assertNotNull($vehicleRequest);
        $this->assertSame(
            ['vehicle_type_id' => (int) $vehicleTypeId, 'capacity' => 12],
            json_decode($vehicleRequest->proposed_value, true),
        );


        $this->assertDatabaseHas('change_requests', [
            'place_id' => $placeId,
            'operation' => 'update',
            'status' => 'pending',
            'submitted_by' => $user->id,
        ]);

        $nameFieldId = DB::table('suggestable_fields')
            ->where('target_table', 'places')
            ->where('target_field', 'name')
            ->value('id');

        $this->assertDatabaseHas('change_requests', [
            'place_id' => $placeId,
            'suggestable_field_id' => $nameFieldId,
            'target_record_id' => $placeId,
            'operation' => 'update',
            'status' => 'pending',
        ]);
    }

    public function test_admin_saves_existing_place_information_directly_without_pending_moderation(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::factory()->create();
        $this->assignRole($admin, 'admin');
        [$placeId, $slug, $placeTypeId] = $this->publishedPlace($admin, 'admin-direct-info');

        $this->actingAs($admin)
            ->withSession(['locale' => 'de'])
            ->get(route('places.info-suggest.edit', $slug))
            ->assertOk()
            ->assertSee('Platz bearbeiten')
            ->assertSee('Änderungen speichern');

        $payload = $this->infoPayload($placeTypeId, 'Direkt geänderter Admin-Platz');
        $payload['phone'] = '+49 201 123456';
        $payload['email'] = 'kontakt@example.test';

        $this->actingAs($admin)
            ->withSession(['locale' => 'de'])
            ->post(route('places.info-suggest.update', $slug), $payload)
            ->assertRedirect(route('places.show', $slug))
            ->assertSessionHas('ui_dialog', [
                'variant' => 'success',
                'message' => 'Die Platzinformationen wurden direkt gespeichert.',
            ]);

        $this->assertSame('Direkt geänderter Admin-Platz', DB::table('places')->where('id', $placeId)->value('name'));
        $this->assertDatabaseHas('place_contacts', [
            'place_id' => $placeId,
            'contact_type' => 'telephone',
            'value' => '+49 201 123456',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('place_contacts', [
            'place_id' => $placeId,
            'contact_type' => 'email',
            'value' => 'kontakt@example.test',
            'is_active' => true,
        ]);
        $this->assertDatabaseMissing('change_requests', [
            'place_id' => $placeId,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('change_requests', [
            'place_id' => $placeId,
            'status' => 'approved',
            'submitted_by' => $admin->id,
            'reviewed_by' => $admin->id,
        ]);
    }

    public function test_admin_can_save_vehicle_type_with_optional_capacity(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::factory()->create();
        $this->assignRole($admin, 'admin');
        [$placeId, $slug, $placeTypeId] = $this->publishedPlace($admin, 'vehicle-capacity-admin');

        $vehicleTypeId = (int) DB::table('vehicle_types')->where('slug', 'car')->value('id');
        $payload = $this->infoPayload($placeTypeId, 'Vehicle Capacity Place');
        $payload['vehicle_type_ids'] = [$vehicleTypeId];
        $payload['vehicle_capacities'] = [$vehicleTypeId => 25];

        $this->actingAs($admin)
            ->post(route('places.info-suggest.update', $slug), $payload)
            ->assertRedirect(route('places.show', $slug));

        $this->assertDatabaseHas('place_vehicle_types', [
            'place_id' => $placeId,
            'vehicle_type_id' => $vehicleTypeId,
            'capacity' => 25,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['locale' => 'de'])
            ->get(route('places.show', $slug));

        $response->assertOk();

        $html = $response->getContent();
        $position = strpos($html, 'PKW');
        $snippet = $position === false
            ? 'PKW nicht im gerenderten Profil gefunden.'
            : substr($html, max(0, $position - 120), 320);

        $this->assertNotFalse($position, $snippet);
        $this->assertStringContainsString('PKW (25)', preg_replace('/\s+/', ' ', $snippet), $snippet);
    }

    public function test_system_owner_saves_existing_place_information_directly_without_admin_role(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::factory()->create(['email' => 'owner-direct@example.test']);
        config()->set('camperwolf.owner_email', $owner->email);

        [$placeId, $slug, $placeTypeId] = $this->publishedPlace($owner, 'owner-direct-info');

        $this->actingAs($owner)
            ->post(route('places.info-suggest.update', $slug), $this->infoPayload($placeTypeId, 'Direkt geänderter Owner-Platz'))
            ->assertRedirect(route('places.show', $slug));

        $this->assertSame('Direkt geänderter Owner-Platz', DB::table('places')->where('id', $placeId)->value('name'));
        $this->assertDatabaseMissing('change_requests', [
            'place_id' => $placeId,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('change_requests', [
            'place_id' => $placeId,
            'status' => 'approved',
            'submitted_by' => $owner->id,
            'reviewed_by' => $owner->id,
        ]);
    }

    private function assignRole(User $user, string $roleSlug): void
    {
        $roleId = DB::table('roles')->where('slug', $roleSlug)->value('id');

        DB::table('user_roles')->insert([
            'user_id' => $user->id,
            'role_id' => $roleId,
            'assigned_by' => null,
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function publishedPlace(User $creator, string $slug): array
    {
        $placeTypeId = (int) DB::table('place_types')->where('is_active', true)->value('id');
        $placeId = (int) DB::table('places')->insertGetId([
            'place_type_id' => $placeTypeId,
            'name' => 'Direktbearbeitung Testplatz',
            'slug' => $slug,
            'latitude' => 51.45,
            'longitude' => 7.01,
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => 'unclear',
            'is_active' => true,
            'created_by' => $creator->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$placeId, $slug, $placeTypeId];
    }

    private function infoPayload(int $placeTypeId, string $name): array
    {
        return [
            'name' => $name,
            'place_type_id' => $placeTypeId,
            'latitude' => 51.4505,
            'longitude' => 7.0105,
            'legal_status' => 'overnight_allowed',
            'operator_name' => null,
            'pitch_count' => null,
            'opening_status' => 'open',
            'website' => null,
            'phone' => null,
            'email' => null,
            'description' => null,
            'directions' => null,
            'access_information' => null,
            'country_code' => null,
            'postal_code' => null,
            'city' => null,
            'street' => null,
            'house_number' => null,
            'address_addition' => null,
            'vehicle_type_ids' => [],
            'vehicle_capacities' => [],
            'comment' => null,
        ];
    }

}
