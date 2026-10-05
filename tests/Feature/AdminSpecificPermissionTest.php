<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminAccessPermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminSpecificPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_open_public_support_form(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->get(route('support.report'))
            ->assertOk();
    }

    public function test_moderator_cannot_merge_places_while_admin_can_open_merge_tools(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(AdminAccessPermissionSeeder::class);

        $moderator = User::factory()->create();
        $admin = User::factory()->create();

        $this->assignRole($moderator, 'mod');
        $this->assignRole($admin, 'admin');

        $this->actingAs($moderator)
            ->get(route('admin.place-merges.index'))
            ->assertNotFound();

        $this->actingAs($admin)
            ->get(route('admin.place-merges.index'))
            ->assertOk();
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
