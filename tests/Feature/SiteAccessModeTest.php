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

    public function test_registration_closed_keeps_gateway_available_and_blocks_new_telegram_accounts(): void
    {
        $this->seed(RolePermissionSeeder::class);
        config(['telegram.bot_token' => '123456:TEST_TOKEN']);

        app(SiteAccessService::class)->set(SiteAccessService::REGISTRATION_CLOSED, 'Testphase');

        $this->get(route('home'))->assertOk();

        $payload = [
            'id' => '99887766',
            'auth_date' => now()->timestamp,
        ];
        ksort($payload);
        $check = collect($payload)->map(fn ($value, $key) => $key.'='.$value)->implode("\n");
        $payload['hash'] = hash_hmac(
            'sha256',
            $check,
            hash('sha256', (string) config('telegram.bot_token'), true),
        );

        $this->get(route('telegram.callback', $payload))
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors('telegram');

        $this->assertDatabaseCount('community_accounts', 0);
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
