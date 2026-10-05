<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL DDL is not transactional. If a previous attempt failed halfway,
        // these new tables may already exist even though the migration itself
        // was not recorded as completed. Recreate them cleanly on retry.
        Schema::dropIfExists('opening_hour_exceptions');
        Schema::dropIfExists('opening_hours');

        Schema::create('opening_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('place_id')->constrained('places')->cascadeOnDelete();
            $table->foreignId('feature_id')->nullable()->constrained('features')->restrictOnDelete();

            // weekday = normal weekly schedule, holiday = general public-holiday schedule.
            $table->string('day_type', 32)->default('weekday');
            // ISO weekday: 1 = Monday, 7 = Sunday. Null for day_type = holiday.
            $table->unsignedTinyInteger('weekday')->nullable();

            $table->time('opens_at')->nullable();
            $table->time('closes_at')->nullable();
            $table->boolean('is_closed')->default(false);
            $table->boolean('is_24_hours')->default(false);
            $table->boolean('by_appointment_only')->default(false);

            // Allows different recurring schedules for seasons or other periods.
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();

            $table->boolean('is_active')->default(true);
            $table->text('internal_comment')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['place_id', 'feature_id', 'is_active'], 'oh_place_feature_active_idx');
            $table->index(['place_id', 'day_type', 'weekday', 'is_active'], 'oh_place_day_active_idx');
            $table->index(['valid_from', 'valid_until'], 'oh_validity_idx');
        });

        Schema::create('opening_hour_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('place_id')->constrained('places')->cascadeOnDelete();
            $table->foreignId('feature_id')->nullable()->constrained('features')->restrictOnDelete();

            // A concrete date overrides the regular weekday/holiday schedule.
            // Multiple rows per date are allowed for split opening times.
            $table->date('exception_date');
            $table->time('opens_at')->nullable();
            $table->time('closes_at')->nullable();
            $table->boolean('is_closed')->default(false);
            $table->boolean('is_24_hours')->default(false);
            $table->boolean('by_appointment_only')->default(false);

            $table->boolean('is_active')->default(true);
            $table->text('internal_comment')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['place_id', 'feature_id', 'exception_date', 'is_active'], 'ohe_place_feature_date_active_idx');
            $table->index(['exception_date', 'is_active'], 'ohe_date_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opening_hour_exceptions');
        Schema::dropIfExists('opening_hours');
    }
};
