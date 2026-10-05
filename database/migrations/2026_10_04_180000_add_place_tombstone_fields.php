<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('places', function (Blueprint $table): void {
            $table->string('name')->nullable()->change();
            $table->string('slug')->nullable()->change();
            $table->timestamp('deleted_at')->nullable()->after('is_active')->index();
            $table->foreignId('deleted_by')->nullable()->after('deleted_at')->constrained('users')->nullOnDelete();
            $table->string('deletion_reason', 64)->nullable()->after('deleted_by')->index();
            $table->text('deletion_note')->nullable()->after('deletion_reason');
        });

        $now = now();
        DB::table('permissions')->updateOrInsert(
            ['slug' => 'places.delete_permanently'],
            [
                'category' => 'places',
                'name' => 'Plätze endgültig löschen',
                'description' => 'Platzdaten vollständig entfernen und nur einen internen Tombstone behalten.',
                'is_active' => true,
                'updated_at' => $now,
                'created_at' => $now,
            ],
        );

        $permissionId = DB::table('permissions')->where('slug', 'places.delete_permanently')->value('id');
        $adminRoleId = DB::table('roles')->where('slug', 'admin')->value('id');

        if ($permissionId && $adminRoleId) {
            DB::table('role_permissions')->updateOrInsert(
                ['role_id' => $adminRoleId, 'permission_id' => $permissionId],
                ['updated_at' => $now, 'created_at' => $now],
            );
        }
    }

    public function down(): void
    {
        DB::table('places')
            ->whereNull('name')
            ->orWhereNull('slug')
            ->orderBy('id')
            ->get(['id', 'name', 'slug'])
            ->each(function ($place): void {
                DB::table('places')->where('id', $place->id)->update([
                    'name' => $place->name ?? 'Deleted place #'.$place->id,
                    'slug' => $place->slug ?? 'deleted-place-'.$place->id,
                ]);
            });

        $permissionId = DB::table('permissions')->where('slug', 'places.delete_permanently')->value('id');
        if ($permissionId) {
            DB::table('role_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('user_permission_overrides')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }

        Schema::table('places', function (Blueprint $table): void {
            $table->string('name')->nullable(false)->change();
            $table->string('slug')->nullable(false)->change();
            $table->dropConstrainedForeignId('deleted_by');
            $table->dropIndex(['deleted_at']);
            $table->dropIndex(['deletion_reason']);
            $table->dropColumn(['deleted_at', 'deletion_reason', 'deletion_note']);
        });
    }
};
