<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permissionId = DB::table('permissions')->where('slug', 'support.create')->value('id');
        $guestRoleId = DB::table('roles')->where('slug', 'guest')->value('id');

        if ($permissionId && $guestRoleId) {
            DB::table('role_permissions')
                ->where('role_id', $guestRoleId)
                ->where('permission_id', $permissionId)
                ->delete();
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('slug', 'support.create')->value('id');
        $guestRoleId = DB::table('roles')->where('slug', 'guest')->value('id');

        if ($permissionId && $guestRoleId) {
            DB::table('role_permissions')->updateOrInsert(
                ['role_id' => $guestRoleId, 'permission_id' => $permissionId],
                ['updated_at' => now(), 'created_at' => now()],
            );
        }
    }
};
