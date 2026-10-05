<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminDirectWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_and_rewards_a_place_without_a_change_request(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create(['email_verified_at' => now()]);
        $this->assignRole($admin, 'admin');
        $placeTypeId = $this->placeType();

        $response = $this->actingAs($admin)
            ->withSession(['locale' => 'de'])
            ->post(route('places.suggest.store'), [
                'name' => 'Direkt veröffentlichter Platz',
                'place_type_id' => $placeTypeId,
                'latitude' => 51.4556,
                'longitude' => 7.0116,
                'intent' => 'submit',
            ]);

        $place = DB::table('places')->where('name', 'Direkt veröffentlichter Platz')->first();

        $this->assertNotNull($place);
        $response->assertRedirect(route('places.show', $place->slug));
        $response->assertSessionHas('ui_dialog', [
            'variant' => 'success',
            'message' => 'Der Platz „Direkt veröffentlichter Platz“ wurde veröffentlicht.',
        ]);
        $this->assertSame('published', $place->publication_status);
        $this->assertSame($admin->id, (int) $place->approved_by);
        $this->assertNotNull($place->approved_at);
        $this->assertDatabaseMissing('change_requests', ['place_id' => $place->id]);
        $this->assertDatabaseHas('xp_ledger', [
            'user_id' => $admin->id,
            'place_id' => $place->id,
            'event_type' => 'place_created',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'entity_type' => 'place',
            'entity_id' => $place->id,
            'action' => 'place_created_by_admin',
            'source' => 'admin',
        ]);
    }

    public function test_admin_interface_uses_direct_entry_wording_in_both_locales(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create(['email_verified_at' => now()]);
        $this->assignRole($admin, 'admin');

        $this->actingAs($admin)
            ->withSession(['locale' => 'de'])
            ->get(route('places.suggest.create'))
            ->assertOk()
            ->assertSee('Platz eintragen')
            ->assertSee('Platz veröffentlichen');

        $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get(route('places.suggest.create'))
            ->assertOk()
            ->assertSee('Add place')
            ->assertSee('Publish place');
    }

    private function assignRole(User $user, string $slug): void
    {
        $roleId = DB::table('roles')->where('slug', $slug)->value('id');

        DB::table('user_roles')->insert([
            'user_id' => $user->id,
            'role_id' => $roleId,
            'assigned_by' => null,
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function placeType(): int
    {
        return (int) DB::table('place_types')->insertGetId([
            'slug' => 'admin-direct-test',
            'icon_id' => null,
            'sort_order' => 10,
            'is_active' => true,
            'is_searchable' => true,
            'internal_comment' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
