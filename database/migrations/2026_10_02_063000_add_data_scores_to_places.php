<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('places', function (Blueprint $table): void {
            $table->unsignedSmallInteger('data_score_basis_known')->default(0)->after('opening_status');
            $table->unsignedSmallInteger('data_score_basis_total')->default(0)->after('data_score_basis_known');
            $table->unsignedSmallInteger('data_score_feature_known')->default(0)->after('data_score_basis_total');
            $table->unsignedSmallInteger('data_score_feature_total')->default(0)->after('data_score_feature_known');
            $table->decimal('data_score_basis', 6, 4)->nullable()->after('data_score_feature_total');
            $table->decimal('data_score_features', 6, 4)->nullable()->after('data_score_basis');
            $table->decimal('data_score', 6, 4)->nullable()->after('data_score_features');
            $table->boolean('data_score_dirty')->default(true)->after('data_score');
            $table->timestamp('data_score_calculated_at')->nullable()->after('data_score_dirty');

            $table->index('data_score', 'places_data_score_idx');
            $table->index(['data_score_dirty', 'id'], 'places_data_score_dirty_idx');
        });
    }

    public function down(): void
    {
        Schema::table('places', function (Blueprint $table): void {
            $table->dropIndex('places_data_score_idx');
            $table->dropIndex('places_data_score_dirty_idx');
            $table->dropColumn([
                'data_score_basis_known',
                'data_score_basis_total',
                'data_score_feature_known',
                'data_score_feature_total',
                'data_score_basis',
                'data_score_features',
                'data_score',
                'data_score_dirty',
                'data_score_calculated_at',
            ]);
        });
    }
};
