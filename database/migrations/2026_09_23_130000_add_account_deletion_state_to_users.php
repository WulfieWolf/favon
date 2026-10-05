<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('account_status', 24)->default('active')->after('last_seen_at')->index();
            $table->timestamp('deletion_requested_at')->nullable()->after('account_status');
            $table->timestamp('deletion_scheduled_for')->nullable()->after('deletion_requested_at')->index();
            $table->timestamp('deleted_at')->nullable()->after('deletion_scheduled_for')->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['account_status']);
            $table->dropIndex(['deletion_scheduled_for']);
            $table->dropIndex(['deleted_at']);
            $table->dropColumn([
                'account_status',
                'deletion_requested_at',
                'deletion_scheduled_for',
                'deleted_at',
            ]);
        });
    }
};
