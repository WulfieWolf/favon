<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminAccessPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('permissions')->updateOrInsert(
            ['slug' => 'admin.access'],
            [
                'name' => 'Adminbereich betreten',
                'category' => 'admin',
                'description' => 'Zugriff auf den internen Moderations- und Administrationsbereich.',
                'sort_order' => 10,
                'is_active' => true,
                'updated_at' => $now,
                'created_at' => $now,
            ],
        );

        $permissionId = DB::table('permissions')->where('slug', 'admin.access')->value('id');
        $roleIds = DB::table('roles')->whereIn('slug', ['mod', 'admin'])->pluck('id');

        foreach ($roleIds as $roleId) {
            DB::table('role_permissions')->updateOrInsert(
                ['role_id' => $roleId, 'permission_id' => $permissionId],
                ['updated_at' => $now, 'created_at' => $now],
            );
        }
    }
}
