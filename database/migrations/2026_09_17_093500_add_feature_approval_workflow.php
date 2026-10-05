<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('features', function (Blueprint $table) {
            $table->string('approval_status', 32)->default('pending')->after('unit_type');
            $table->foreignId('suggested_by')->nullable()->after('approval_status')->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->after('suggested_by')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');

            $table->index(['approval_status', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('features', function (Blueprint $table) {
            $table->dropIndex(['approval_status', 'is_active']);
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropConstrainedForeignId('suggested_by');
            $table->dropColumn(['reviewed_at', 'approval_status']);
        });
    }
};
