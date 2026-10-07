<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SecurityMonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_see_security_metrics_and_recent_events(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::factory()->create(['email_verified_at' => now()]);
        $this->assignRole($admin, 'admin');

        DB::table('security_events')->insert([
            'event_type' => 'rate_limited',
            'user_id' => null,
            'route_name' => 'support.store',
            'source_ip_hash' => str_repeat('a', 64),
            'context' => json_encode(['method' => 'POST'], JSON_UNESCAPED_SLASHES),
            'created_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.system.index'))
            ->assertOk()
            ->assertSee(__('admin.system.security_title'))
            ->assertSee(__('admin.system.security_events.rate_limited'))
            ->assertSee('support.store')
            ->assertSee('aaaaaaaaaaaa…');
    }

    public function test_security_cleanup_removes_only_events_older_than_ninety_days(): void
    {
        DB::table('security_events')->insert([
            [
                'event_type' => 'rate_limited',
                'user_id' => null,
                'route_name' => null,
                'source_ip_hash' => null,
                'context' => null,
                'created_at' => now()->subDays(91),
            ],
            [
                'event_type' => 'browse_query_rejected',
                'user_id' => null,
                'route_name' => null,
                'source_ip_hash' => null,
                'context' => null,
                'created_at' => now()->subDays(30),
            ],
        ]);

        $this->artisan('security:cleanup')->assertSuccessful();

        $this->assertDatabaseMissing('security_events', [
            'event_type' => 'rate_limited',
        ]);
        $this->assertDatabaseHas('security_events', [
            'event_type' => 'browse_query_rejected',
        ]);
    }


    public function test_system_owner_can_open_system_tools_without_admin_role(): void
    {
        $this->seed(DatabaseSeeder::class);

        config()->set('favon.owner_email', 'owner-security@example.test');

        $owner = User::factory()->create([
            'email' => 'owner-security@example.test',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($owner)
            ->get(route('admin.system.index'))
            ->assertOk()
            ->assertSee(__('admin.system.security_title'));
    }

    private function assignRole(User $user, string $roleSlug): void
    {
        $roleId = DB::table('roles')->where('slug', $roleSlug)->value('id');

        DB::table('user_roles')->updateOrInsert(
            ['user_id' => $user->id, 'role_id' => $roleId],
            [
                'assigned_by' => null,
                'assigned_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }
}
