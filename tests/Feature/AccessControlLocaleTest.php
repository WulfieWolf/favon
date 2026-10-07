<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessControlLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_preview_and_access_errors_follow_the_selected_locale(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = User::factory()->create(['email_verified_at' => now()]);
        $target = User::factory()->create(['email_verified_at' => now()]);
        config(['favon.owner_email' => $owner->email]);

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->post(route('role-preview.update'), ['role' => 'guest'])
            ->assertRedirect()
            ->assertSessionHas('ui_toast', 'Role preview enabled: Guest');

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->post(route('role-preview.update'), ['role' => 'default'])
            ->assertRedirect()
            ->assertSessionHas('ui_toast', 'Role preview disabled. Super-admin permissions are active again.');

        $this->actingAs($owner)
            ->withSession(['locale' => 'de'])
            ->post(route('role-preview.update'), ['role' => 'guest'])
            ->assertRedirect()
            ->assertSessionHas('ui_toast', 'Rollen-Vorschau aktiviert: Gast');

        $this->actingAs($owner)
            ->withSession(['locale' => 'de'])
            ->post(route('role-preview.update'), ['role' => 'default'])
            ->assertRedirect()
            ->assertSessionHas('ui_toast', 'Rollen-Vorschau deaktiviert. Superadmin-Rechte sind wieder aktiv.');

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->post(route('admin.users.roles.assign', $target), ['role' => 'missing-role'])
            ->assertRedirect()
            ->assertSessionHasErrors(['access' => 'Role not found or inactive.']);

        $this->actingAs($owner)
            ->withSession(['locale' => 'de'])
            ->post(route('admin.users.roles.assign', $target), ['role' => 'missing-role'])
            ->assertRedirect()
            ->assertSessionHasErrors(['access' => 'Rolle wurde nicht gefunden oder ist inaktiv.']);
    }
}
