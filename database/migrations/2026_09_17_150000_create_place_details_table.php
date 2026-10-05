<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('place_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('place_id')->constrained('places')->cascadeOnDelete();

            // Optional operator / business name. This is descriptive place data only;
            // operator verification and ownership are modeled separately later.
            $table->string('operator_name')->nullable();

            // Number of usable pitches / spaces where this concept applies.
            // Service-only locations can simply leave this null.
            $table->unsignedInteger('pitch_count')->nullable();

            // Current high-level operating season of the complete place.
            // Expected values: unclear, year-round, seasonal.
            $table->string('operating_mode', 32)->default('unclear');

            $table->boolean('is_active')->default(true);
            $table->text('internal_comment')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('place_id', 'place_details_place_unique');
            $table->index(['operating_mode', 'is_active'], 'place_details_operating_mode_idx');
        });

        Schema::create('place_detail_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('place_detail_id')->constrained('place_details')->cascadeOnDelete();
            $table->string('locale', 16);

            // Optional free-text clarification, for example:
            // "Usually open from April to October".
            $table->text('season_note')->nullable();

            $table->boolean('is_active')->default(true);
            $table->text('internal_comment')->nullable();
            $table->timestamps();

            $table->unique(['place_detail_id', 'locale'], 'place_detail_translations_unique');
            $table->index(['locale', 'is_active'], 'place_detail_translations_locale_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('place_detail_translations');
        Schema::dropIfExists('place_details');
    }
};
