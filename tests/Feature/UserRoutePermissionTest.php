<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserRoutePermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_denied_review_notification_and_support_permissions_block_direct_routes(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $this->assignRole($user, 'user');

        $this->deny($user, 'reviews.create');
        $this->deny($user, 'reviews.delete_own');
        $this->deny($user, 'reports.create');
        $this->deny($user, 'notifications.view_own');
        $this->deny($user, 'notifications.manage_own');
        $this->deny($user, 'support.view_own');
        $this->deny($user, 'support.reply_own');

        $this->actingAs($user)
            ->post(route('reviews.store', ['slug' => 'permission-test']), [])
            ->assertNotFound();

        $this->actingAs($user)
            ->delete(route('reviews.destroy', ['slug' => 'permission-test']))
            ->assertNotFound();

        $this->actingAs($user)
            ->post(route('reviews.report', ['review' => 999999]), [])
            ->assertNotFound();

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertNotFound();

        $this->actingAs($user)
            ->post(route('notifications.read-all'))
            ->assertNotFound();

        $this->actingAs($user)
            ->get(route('support.my.index'))
            ->assertNotFound();

        $this->actingAs($user)
            ->post(route('support.my.reply', ['ticket' => 999999]), [])
            ->assertNotFound();
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

    private function deny(User $user, string $permissionSlug): void
    {
        $permissionId = DB::table('permissions')->where('slug', $permissionSlug)->value('id');

        DB::table('user_permission_overrides')->updateOrInsert(
            ['user_id' => $user->id, 'permission_id' => $permissionId],
            [
                'allowed' => false,
                'set_by' => null,
                'reason' => 'test',
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }
}
