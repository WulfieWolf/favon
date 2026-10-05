<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('place_features', function (Blueprint $table) {
            $table->dropIndex('pf_browse_feature_count_idx');
        });
    }

    public function down(): void
    {
        Schema::table('place_features', function (Blueprint $table) {
            $table->index(
                ['is_active', 'valid_until', 'feature_id', 'place_id', 'status'],
                'pf_browse_feature_count_idx',
            );
        });
    }
};
