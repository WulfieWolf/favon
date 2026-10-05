<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AdminDebugService;
use Database\Seeders\AdminAccessPermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminSystemToolsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_system_tools_and_toggle_debug_mode(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(AdminAccessPermissionSeeder::class);

        $user = User::factory()->create();
        $adminRoleId = DB::table('roles')->where('slug', 'admin')->value('id');

        DB::table('user_roles')->updateOrInsert(
            ['user_id' => $user->id, 'role_id' => $adminRoleId],
            [
                'assigned_by' => null,
                'assigned_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        $this->actingAs($user)
            ->get(route('admin.system.index'))
            ->assertOk();

        $this->actingAs($user)
            ->put(route('admin.system.debug'), ['enabled' => true])
            ->assertRedirect();

        $this->assertTrue((bool) session(AdminDebugService::SESSION_KEY));

        $this->actingAs($user)
            ->put(route('admin.system.debug'), ['enabled' => false])
            ->assertRedirect();

        $this->assertFalse((bool) session(AdminDebugService::SESSION_KEY, false));
    }

    public function test_regular_user_cannot_open_system_tools(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(AdminAccessPermissionSeeder::class);

        $user = User::factory()->create();
        $userRoleId = DB::table('roles')->where('slug', 'user')->value('id');

        DB::table('user_roles')->updateOrInsert(
            ['user_id' => $user->id, 'role_id' => $userRoleId],
            [
                'assigned_by' => null,
                'assigned_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        $this->actingAs($user)
            ->get(route('admin.system.index'))
            ->assertNotFound();
    }
}
