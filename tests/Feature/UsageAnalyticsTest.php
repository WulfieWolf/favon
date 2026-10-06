<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PermissionService;
use App\Services\UsageAnalyticsService;
use Database\Seeders\AdminAccessPermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UsageAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_usage_event_is_stored_without_personal_identifiers(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $request = Request::create('/places/example', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Linux; Android 16) AppleWebKit/537.36 Chrome/153 Mobile Safari/537.36',
        ]);

        app(UsageAnalyticsService::class)->track(
            $request,
            'page_view',
            'places.show',
            'place',
            123,
            ['source' => 'test'],
        );

        $this->assertDatabaseHas('usage_events', [
            'event_type' => 'page_view',
            'area' => 'places.show',
            'content_type' => 'place',
            'content_id' => 123,
            'audience' => 'guest',
            'traffic_type' => 'human',
        ]);

        $columns = Schema::getColumnListing('usage_events');

        $this->assertNotContains('user_id', $columns);
        $this->assertNotContains('ip_address', $columns);
        $this->assertNotContains('session_id', $columns);
        $this->assertNotContains('fingerprint', $columns);
        $this->assertNotContains('user_agent', $columns);
    }


    public function test_guest_bot_is_classified_without_storing_user_agent(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $request = Request::create('/places/example', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        ]);

        app(UsageAnalyticsService::class)->track(
            $request,
            'page_view',
            'places.show',
            'place',
            123,
        );

        $this->assertDatabaseHas('usage_events', [
            'event_type' => 'page_view',
            'area' => 'places.show',
            'audience' => 'guest',
            'traffic_type' => 'bot',
        ]);

        $this->assertNotContains('user_agent', Schema::getColumnListing('usage_events'));
    }

    public function test_guest_without_user_agent_is_classified_as_unknown(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $request = Request::create('/places/example', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => '',
        ]);

        app(UsageAnalyticsService::class)->track(
            $request,
            'page_view',
            'places.show',
        );

        $this->assertDatabaseHas('usage_events', [
            'area' => 'places.show',
            'audience' => 'guest',
            'traffic_type' => 'unknown',
        ]);
    }

    public function test_audience_is_classified_without_storing_user_identity(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $moderator = User::factory()->create();
        $admin = User::factory()->create();

        $this->assignRole($moderator, 'mod');
        $this->assignRole($admin, 'admin');

        $service = app(UsageAnalyticsService::class);

        $moderatorRequest = Request::create('/admin', 'GET');
        $moderatorRequest->setUserResolver(fn () => $moderator);
        $service->track($moderatorRequest, 'page_view', 'admin.index');

        $adminRequest = Request::create('/admin/statistics', 'GET');
        $adminRequest->setUserResolver(fn () => $admin);
        $service->track($adminRequest, 'page_view', 'admin.statistics.index');

        $this->assertDatabaseHas('usage_events', [
            'area' => 'admin.index',
            'audience' => 'moderator',
        ]);
        $this->assertDatabaseHas('usage_events', [
            'area' => 'admin.statistics.index',
            'audience' => 'admin',
        ]);
    }

    public function test_statistics_permission_is_admin_only_by_default(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(AdminAccessPermissionSeeder::class);

        $moderator = User::factory()->create();
        $admin = User::factory()->create();

        $this->assignRole($moderator, 'mod');
        $this->assignRole($admin, 'admin');

        $permissions = app(PermissionService::class);

        $this->assertFalse($permissions->can($moderator, 'statistics.view'));
        $this->assertTrue($permissions->can($admin, 'statistics.view'));

        $this->actingAs($moderator)
            ->get(route('admin.statistics.index'))
            ->assertNotFound();

        $this->actingAs($admin)
            ->get(route('admin.statistics.index'))
            ->assertOk();
    }

    public function test_admin_dashboard_is_grouped_and_statistics_are_hidden_from_moderators(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(AdminAccessPermissionSeeder::class);

        $moderator = User::factory()->create();
        $admin = User::factory()->create();

        $this->assignRole($moderator, 'mod');
        $this->assignRole($admin, 'admin');

        $this->actingAs($admin)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee(__('admin.overview.users_rights'))
            ->assertSee(__('admin.overview.support'))
            ->assertSee(__('admin.overview.statistics'))
            ->assertSee(__('admin.overview.system_tools'));

        $this->actingAs($moderator)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertDontSee(__('admin.overview.statistics'));
    }

    public function test_successful_html_page_view_is_recorded_by_middleware(): void
    {
        $this->get(route('legal.imprint'))
            ->assertOk();

        $this->assertDatabaseHas('usage_events', [
            'event_type' => 'page_view',
            'area' => 'legal.imprint',
            'audience' => 'guest',
        ]);
    }

    private function assignRole(User $user, string $roleSlug): void
    {
        $roleId = DB::table('roles')->where('slug', $roleSlug)->value('id');

        DB::table('user_roles')->updateOrInsert(
            ['user_id' => $user->id, 'role_id' => $roleId],
            [
                'assigned_by' => null,
                'assigned_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }
}
