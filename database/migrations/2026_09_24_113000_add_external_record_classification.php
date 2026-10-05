<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_records', function (Blueprint $table) {
            $table->string('classification', 32)->nullable()->after('status');
            $table->timestamp('classified_at')->nullable()->after('classification');
            $table->index(['external_source_id', 'classification'], 'er_source_classification_idx');
        });
    }

    public function down(): void
    {
        Schema::table('external_records', function (Blueprint $table) {
            $table->dropIndex('er_source_classification_idx');
            $table->dropColumn(['classification', 'classified_at']);
        });
    }
};
