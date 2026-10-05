<?php

use Database\Seeders\V1FeatureCatalogSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feature_categories', function (Blueprint $table) {
            $table->boolean('is_system')->default(false)->after('is_searchable');
        });

        Schema::table('features', function (Blueprint $table) {
            $table->boolean('is_system')->default(false)->after('is_searchable');
        });

        Schema::create('feature_place_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feature_id')->constrained('features')->cascadeOnDelete();
            $table->foreignId('place_type_id')->constrained('place_types')->cascadeOnDelete();
            $table->string('visibility', 16)->default('extended');
            $table->timestamps();

            $table->unique(['feature_id', 'place_type_id']);
            $table->index(['place_type_id', 'visibility', 'feature_id'], 'feature_place_type_visibility_idx');
        });

        Schema::create('catalog_releases', function (Blueprint $table) {
            $table->string('release_key', 100)->primary();
            $table->timestamp('applied_at');
        });

        (new V1FeatureCatalogSeeder)->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_releases');
        Schema::dropIfExists('feature_place_types');

        Schema::table('features', function (Blueprint $table) {
            $table->dropColumn('is_system');
        });

        Schema::table('feature_categories', function (Blueprint $table) {
            $table->dropColumn('is_system');
        });
    }
};
