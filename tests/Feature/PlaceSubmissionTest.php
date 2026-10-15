<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PlaceSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_submit_a_pending_place_with_one_primary_type(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $placeTypeId = (int) DB::table('place_types')->where('slug', 'park-forest')->value('id');

        $response = $this->actingAs($user)->post(route('places.store'), [
            'place_type_id' => $placeTypeId,
            'name' => 'Stadtpark Süd',
            'latitude' => 51.455643,
            'longitude' => 7.011555,
            'description' => 'Treffpunkt im hinteren Bereich des Parks.',
            'environment' => 'outdoor',
            'target_audience' => ['mm', 'mf'],
            'has_gloryhole' => '0',
            'street_address' => 'Beispielstraße 12',
            'postal_code' => '45127',
            'city' => 'Essen',
            'country_code' => 'de',
        ]);

        $response
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('place_submission_success');

        $place = DB::table('places')->where('name', 'Stadtpark Süd')->first();

        $this->assertNotNull($place);
        $this->assertSame('pending', $place->publication_status);
        $this->assertSame((int) $user->id, (int) $place->created_by);
        $this->assertNull($place->approved_by);
        $this->assertNull($place->approved_at);
        $this->assertSame('outdoor', $place->environment);
        $this->assertTrue((bool) $place->target_mm);
        $this->assertTrue((bool) $place->target_mf);
        $this->assertNull($place->target_audience);
        $this->assertFalse((bool) $place->has_gloryhole);
        $this->assertSame('Treffpunkt im hinteren Bereich des Parks.', $place->description);

        $this->assertDatabaseHas('place_place_types', [
            'place_id' => $place->id,
            'place_type_id' => $placeTypeId,
            'is_primary' => true,
        ]);

        $this->assertDatabaseHas('place_addresses', [
            'place_id' => $place->id,
            'street' => 'Beispielstraße',
            'house_number' => '12',
            'postal_code' => '45127',
            'city' => 'Essen',
            'country_code' => 'DE',
            'is_active' => true,
        ]);
    }

    public function test_pending_submission_is_not_visible_in_normal_browse(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $placeTypeId = (int) DB::table('place_types')->where('slug', 'rest-area')->value('id');

        $this->actingAs($user)->post(route('places.store'), [
            'place_type_id' => $placeTypeId,
            'name' => 'Noch nicht freigegeben',
            'latitude' => 51.0,
            'longitude' => 7.0,
            'environment' => 'outdoor',
            'target_audience' => ['mm'],
            'has_gloryhole' => '1',
        ])->assertRedirect(route('dashboard'));

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Noch nicht freigegeben');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Noch nicht freigegeben');
    }

    public function test_submission_rejects_invalid_core_fields_and_target_audience(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $placeTypeId = (int) DB::table('place_types')->where('slug', 'gay-sauna')->value('id');

        DB::table('place_types')->where('id', $placeTypeId)->update(['is_active' => false]);

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->post(route('places.store'), [
                'place_type_id' => $placeTypeId,
                'name' => '',
                'latitude' => 95,
                'longitude' => 181,
                'environment' => 'somewhere',
                'target_audience' => ['everyone'],
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors([
                'place_type_id',
                'name',
                'latitude',
                'longitude',
                'environment',
                'target_audience.0',
            ]);

        $this->assertDatabaseCount('places', 0);
    }

    public function test_optional_description_environment_gloryhole_and_address_may_be_omitted(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $placeTypeId = (int) DB::table('place_types')->where('slug', 'public-toilet')->value('id');

        $this->actingAs($user)
            ->post(route('places.store'), [
                'place_type_id' => $placeTypeId,
                'name' => 'Testplatz',
                'latitude' => 51.1,
                'longitude' => 7.1,
                'target_audience' => ['mf'],
            ])
            ->assertRedirect(route('dashboard'));

        $place = DB::table('places')->where('name', 'Testplatz')->first();

        $this->assertNotNull($place);
        $this->assertNull($place->description);
        $this->assertNull($place->environment);
        $this->assertFalse((bool) $place->target_mm);
        $this->assertTrue((bool) $place->target_mf);
        $this->assertNull($place->has_gloryhole);
        $this->assertDatabaseMissing('place_addresses', ['place_id' => $place->id]);
    }

    public function test_user_without_submission_permission_cannot_submit(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $permissionId = (int) DB::table('permissions')->where('slug', 'places.submit')->value('id');

        DB::table('user_permission_overrides')->insert([
            'user_id' => $user->id,
            'permission_id' => $permissionId,
            'allowed' => false,
            'set_by' => null,
            'reason' => 'test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $placeTypeId = (int) DB::table('place_types')->where('slug', 'park-forest')->value('id');

        $this->actingAs($user)
            ->post(route('places.store'), [
                'place_type_id' => $placeTypeId,
                'name' => 'Blocked place',
                'latitude' => 51.0,
                'longitude' => 7.0,
                'environment' => 'outdoor',
                'has_gloryhole' => '0',
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('places', 0);
    }
}
