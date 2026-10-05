<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('places', function (Blueprint $table) {
            $table->id();
            $table->foreignId('place_type_id')->constrained('place_types')->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('address_line')->nullable();
            $table->string('postal_code', 32)->nullable();
            $table->string('city')->nullable();
            $table->char('country_code', 2)->nullable();
            $table->string('publication_status', 32)->default('pending');
            $table->string('legal_status', 32)->default('unclear');
            $table->string('opening_status', 32)->default('unclear');
            $table->boolean('is_active')->default(true);
            $table->text('internal_comment')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['latitude', 'longitude']);
            $table->index(['country_code', 'city']);
            $table->index(['publication_status', 'is_active']);
        });

        Schema::create('place_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('place_id')->constrained('places')->cascadeOnDelete();
            $table->string('locale', 16);
            $table->text('description')->nullable();
            $table->text('directions')->nullable();
            $table->text('access_information')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('internal_comment')->nullable();
            $table->timestamps();

            $table->unique(['place_id', 'locale']);
            $table->index(['locale', 'is_active']);
        });

        Schema::create('place_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('place_id')->constrained('places')->cascadeOnDelete();
            $table->foreignId('feature_id')->constrained('features')->restrictOnDelete();
            $table->foreignId('feature_option_id')->nullable()->constrained('feature_options')->restrictOnDelete();
            $table->boolean('is_active')->default(true);
            $table->text('internal_comment')->nullable();
            $table->timestamps();

            $table->unique(['place_id', 'feature_id']);
            $table->index(['feature_id', 'feature_option_id', 'is_active']);
        });

        Schema::create('place_feature_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('place_feature_id')->constrained('place_features')->cascadeOnDelete();
            $table->string('locale', 16);
            $table->text('note');
            $table->boolean('is_active')->default(true);
            $table->text('internal_comment')->nullable();
            $table->timestamps();

            $table->unique(['place_feature_id', 'locale']);
            $table->index(['locale', 'is_active']);
        });

        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->string('note_type', 64)->default('general');
            $table->boolean('is_active')->default(true);
            $table->text('internal_comment')->nullable();
            $table->timestamps();

            $table->index(['note_type', 'is_active']);
        });

        Schema::create('note_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_id')->constrained('notes')->cascadeOnDelete();
            $table->string('locale', 16);
            $table->text('text');
            $table->boolean('is_active')->default(true);
            $table->text('internal_comment')->nullable();
            $table->timestamps();

            $table->unique(['note_id', 'locale']);
            $table->index(['locale', 'is_active']);
        });

        Schema::create('place_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('place_id')->constrained('places')->cascadeOnDelete();
            $table->foreignId('note_id')->constrained('notes')->restrictOnDelete();
            $table->unsignedInteger('sort_order')->default(10);
            $table->boolean('is_active')->default(true);
            $table->text('internal_comment')->nullable();
            $table->timestamps();

            $table->unique(['place_id', 'note_id']);
            $table->index(['place_id', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('place_notes');
        Schema::dropIfExists('note_translations');
        Schema::dropIfExists('notes');
        Schema::dropIfExists('place_feature_notes');
        Schema::dropIfExists('place_features');
        Schema::dropIfExists('place_translations');
        Schema::dropIfExists('places');
    }
};
