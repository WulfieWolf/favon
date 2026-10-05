<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->text('suspension_reason')->nullable()->after('account_status');
            $table->timestamp('suspended_until')->nullable()->after('suspension_reason')->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['suspended_until']);
            $table->dropColumn(['suspension_reason', 'suspended_until']);
        });
    }
};
