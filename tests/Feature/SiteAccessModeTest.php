<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SiteAccessService;
use App\Services\TelegramLoginService;
use Database\Seeders\AdminAccessPermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class SiteAccessModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_closed_keeps_gateway_available_and_blocks_new_telegram_accounts(): void
    {
        $this->seed(RolePermissionSeeder::class);

        app(SiteAccessService::class)->set(SiteAccessService::REGISTRATION_CLOSED, 'Testphase');

        $this->get(route('home'))->assertOk();

        try {
            app(TelegramLoginService::class)->resolveUser('99887766', 'de');
            $this->fail('A new Telegram account was created while registration was closed.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Favon registration is currently closed.', $exception->getMessage());
        }

        $this->assertDatabaseCount('community_accounts', 0);
        $this->assertDatabaseCount('users', 0);
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
