<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SiteAccessService;
use Database\Seeders\AdminAccessPermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SiteAccessModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_closed_keeps_guest_frontend_available_but_blocks_registration(): void
    {
        app(SiteAccessService::class)->set(SiteAccessService::REGISTRATION_CLOSED, 'Testphase');

        $this->get(route('home'))->assertOk();

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Testphase');

        $this->post(route('register.store'), [
            'name' => 'Blocked User',
            'email' => 'blocked@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'legal_acceptance' => '1',
        ])->assertRedirect(route('register'));

        $this->assertDatabaseMissing('users', ['email' => 'blocked@example.com']);
    }

    public function test_lockdown_blocks_guests_but_keeps_login_available(): void
    {
        app(SiteAccessService::class)->set(SiteAccessService::LOCKDOWN, 'Wartung');

        $this->get(route('home'))
            ->assertStatus(503)
            ->assertSee('Wartung');

        $this->get(route('login'))->assertOk();
    }

    public function test_admin_can_still_use_site_during_lockdown(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(AdminAccessPermissionSeeder::class);

        $admin = User::factory()->create();
        $adminRoleId = DB::table('roles')->where('slug', 'admin')->value('id');

        DB::table('user_roles')->insert([
            'user_id' => $admin->id,
            'role_id' => $adminRoleId,
            'assigned_by' => null,
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(SiteAccessService::class)->set(SiteAccessService::LOCKDOWN, 'Wartung');

        $this->actingAs($admin)
            ->get(route('admin.system.index'))
            ->assertOk();
    }

    public function test_cli_can_recover_normal_mode(): void
    {
        app(SiteAccessService::class)->set(SiteAccessService::LOCKDOWN, 'Wartung');

        $this->artisan('favon:mode normal')
            ->assertSuccessful();

        $this->assertSame(SiteAccessService::NORMAL, app(SiteAccessService::class)->mode());
    }
}
