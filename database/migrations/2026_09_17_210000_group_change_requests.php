<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('change_requests', function (Blueprint $table) {
            $table->uuid('group_uuid')->nullable()->after('id');

            $table->index(['group_uuid', 'status'], 'cr_group_status_idx');
            $table->index(['place_id', 'group_uuid'], 'cr_place_group_idx');
        });
    }

    public function down(): void
    {
        Schema::table('change_requests', function (Blueprint $table) {
            $table->dropIndex('cr_group_status_idx');
            $table->dropIndex('cr_place_group_idx');
            $table->dropColumn('group_uuid');
        });
    }
};
