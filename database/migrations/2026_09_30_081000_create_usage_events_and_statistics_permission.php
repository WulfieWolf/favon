<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_events', function (Blueprint $table): void {
            $table->id();
            $table->string('event_type', 80);
            $table->string('area', 160);
            $table->string('content_type', 80)->nullable();
            $table->unsignedBigInteger('content_id')->nullable();
            $table->string('audience', 20);
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
            $table->index(['event_type', 'created_at']);
            $table->index(['area', 'created_at']);
            $table->index(['audience', 'created_at']);
            $table->index(['content_type', 'content_id', 'created_at'], 'usage_content_created_idx');
        });

        $now = now();
        DB::table('permissions')->updateOrInsert(
            ['slug' => 'statistics.view'],
            [
                'name' => 'Statistik ansehen',
                'category' => 'statistics',
                'description' => 'Aggregierte Bestands-, Aktivitäts- und Nutzungsstatistiken ansehen.',
                'sort_order' => 9990,
                'is_active' => true,
                'updated_at' => $now,
                'created_at' => $now,
            ],
        );

        $permissionId = DB::table('permissions')->where('slug', 'statistics.view')->value('id');
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
        $permissionId = DB::table('permissions')->where('slug', 'statistics.view')->value('id');

        if ($permissionId) {
            DB::table('role_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('user_permission_overrides')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }

        Schema::dropIfExists('usage_events');
    }
};
