<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_records', function (Blueprint $table): void {
            $table->string('tombstone_review_hash', 64)->nullable()->after('normalized_hash');
        });
    }

    public function down(): void
    {
        Schema::table('external_records', function (Blueprint $table): void {
            $table->dropColumn('tombstone_review_hash');
        });
    }
};
