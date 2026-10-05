<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dev_releases', function (Blueprint $table) {
            $table->id();
            $table->string('stage', 32)->default('pre-alpha');
            $table->unsignedSmallInteger('milestone');
            $table->unsignedInteger('build');
            $table->dateTime('released_at');
            $table->boolean('is_public')->default(true);
            $table->timestamps();

            $table->unique(['milestone', 'build'], 'dev_releases_version_unique');
            $table->index(['is_public', 'released_at']);
        });

        Schema::create('dev_release_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dev_release_id')->constrained('dev_releases')->cascadeOnDelete();
            $table->string('locale', 16);
            $table->string('title');
            $table->text('summary')->nullable();
            $table->timestamps();

            $table->unique(['dev_release_id', 'locale']);
        });

        Schema::create('dev_release_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dev_release_id')->constrained('dev_releases')->cascadeOnDelete();
            $table->string('type', 32);
            $table->unsignedInteger('sort_order')->default(10);
            $table->timestamps();

            $table->index(['dev_release_id', 'type', 'sort_order']);
        });

        Schema::create('dev_release_item_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dev_release_item_id')->constrained('dev_release_items')->cascadeOnDelete();
            $table->string('locale', 16);
            $table->text('text');
            $table->timestamps();

            $table->unique(['dev_release_item_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dev_release_item_translations');
        Schema::dropIfExists('dev_release_items');
        Schema::dropIfExists('dev_release_translations');
        Schema::dropIfExists('dev_releases');
    }
};
