<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminAccessPermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ManualBadgePermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_moderator_cannot_manage_manual_badges_but_admin_can_reach_the_action(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(AdminAccessPermissionSeeder::class);

        $moderator = User::factory()->create();
        $admin = User::factory()->create();
        $target = User::factory()->create();

        $this->assignRole($moderator, 'mod');
        $this->assignRole($admin, 'admin');

        $this->actingAs($moderator)
            ->post(route('admin.users.badges.grant', $target), [])
            ->assertNotFound();

        $this->actingAs($admin)
            ->post(route('admin.users.badges.grant', $target), [])
            ->assertSessionHasErrors('badge_id');
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
