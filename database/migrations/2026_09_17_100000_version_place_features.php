<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL needs a separate index for the place_id foreign key before
        // the existing composite unique index can be removed.
        Schema::table('place_features', function (Blueprint $table) {
            $table->index('place_id', 'place_features_place_id_index');
        });

        Schema::table('place_features', function (Blueprint $table) {
            $table->dropUnique(['place_id', 'feature_id']);

            $table->timestamp('valid_from')->nullable()->after('unit_key');
            $table->timestamp('valid_until')->nullable()->after('valid_from');

            $table->index(['place_id', 'feature_id', 'is_active']);
            $table->index(['feature_id', 'valid_from', 'valid_until']);
        });

        DB::table('place_features')
            ->whereNull('valid_from')
            ->update([
                'valid_from' => DB::raw('COALESCE(created_at, CURRENT_TIMESTAMP)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('place_features', function (Blueprint $table) {
            $table->dropIndex(['feature_id', 'valid_from', 'valid_until']);
            $table->dropIndex(['place_id', 'feature_id', 'is_active']);
            $table->dropColumn(['valid_from', 'valid_until']);

            $table->unique(['place_id', 'feature_id']);
        });

        Schema::table('place_features', function (Blueprint $table) {
            $table->dropIndex('place_features_place_id_index');
        });
    }
};
