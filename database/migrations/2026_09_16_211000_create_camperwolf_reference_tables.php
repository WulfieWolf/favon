<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('icons', function (Blueprint $table) {
            $table->id();
            $table->string('icon_key', 100)->unique();
            $table->string('relative_path');
            $table->boolean('is_active')->default(true);
            $table->text('internal_comment')->nullable();
            $table->timestamps();
        });

        Schema::create('feature_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->foreignId('icon_id')->nullable()->constrained('icons')->restrictOnDelete();
            $table->integer('sort_order')->default(10);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_searchable')->default(true);
            $table->text('internal_comment')->nullable();
            $table->timestamps();
        });

        Schema::create('features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('feature_categories')->restrictOnDelete();
            $table->string('slug', 100)->unique();
            $table->foreignId('icon_id')->nullable()->constrained('icons')->restrictOnDelete();
            $table->integer('sort_order')->default(10);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_searchable')->default(true);
            $table->text('internal_comment')->nullable();
            $table->timestamps();
        });

        Schema::create('feature_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feature_id')->constrained('features')->restrictOnDelete();
            $table->string('slug', 100);
            $table->integer('sort_order')->default(10);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_searchable')->default(true);
            $table->text('internal_comment')->nullable();
            $table->timestamps();

            $table->unique(['feature_id', 'slug']);
        });

        Schema::create('place_types', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->foreignId('icon_id')->nullable()->constrained('icons')->restrictOnDelete();
            $table->integer('sort_order')->default(10);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_searchable')->default(true);
            $table->text('internal_comment')->nullable();
            $table->timestamps();
        });

        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 64);
            $table->unsignedBigInteger('entity_id');
            $table->string('locale', 10);
            $table->string('field', 64);
            $table->text('value');
            $table->boolean('is_active')->default(true);
            $table->text('internal_comment')->nullable();
            $table->timestamps();

            $table->unique(['entity_type', 'entity_id', 'locale', 'field'], 'translations_entity_locale_field_unique');
            $table->index(['entity_type', 'entity_id']);
            $table->index('locale');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translations');
        Schema::dropIfExists('place_types');
        Schema::dropIfExists('feature_options');
        Schema::dropIfExists('features');
        Schema::dropIfExists('feature_categories');
        Schema::dropIfExists('icons');
    }
};
