<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('place_reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('place_id')->constrained('places')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->string('status', 30)->default('active');
            $table->timestamp('current_published_at')->nullable();
            $table->timestamp('current_expires_at')->nullable();
            $table->boolean('verified_visit')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->unique(['place_id', 'user_id']);
            $table->index(['place_id', 'status', 'current_expires_at'], 'place_reviews_current_idx');
            $table->index(['user_id', 'status']);
        });

        Schema::create('place_review_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('review_id')->constrained('place_reviews')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->unsignedTinyInteger('rating_cleanliness');
            $table->unsignedTinyInteger('rating_functionality');
            $table->unsignedTinyInteger('rating_condition');
            $table->unsignedTinyInteger('rating_safety');
            $table->unsignedTinyInteger('rating_usability');
            $table->decimal('overall_score', 2, 1);
            $table->text('review_text')->nullable();
            $table->boolean('is_public')->default(true);
            $table->timestamp('valid_from');
            $table->timestamp('valid_until')->nullable();
            $table->timestamps();

            $table->unique(['review_id', 'version_number']);
            $table->index(['review_id', 'valid_from', 'valid_until'], 'review_versions_period_idx');
            $table->index(['valid_from', 'valid_until'], 'review_versions_history_idx');
        });

        Schema::table('place_reviews', function (Blueprint $table): void {
            $table->index('current_version_id');
        });

        Schema::create('place_review_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('review_id')->constrained('place_reviews')->cascadeOnDelete();
            $table->foreignId('review_version_id')->constrained('place_review_versions')->cascadeOnDelete();
            $table->foreignId('reported_by')->constrained('users')->cascadeOnDelete();
            $table->string('reason', 40);
            $table->string('comment', 1000)->nullable();
            $table->string('status', 30)->default('pending');
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('moderator_comment', 2000)->nullable();
            $table->timestamp('moderated_at')->nullable();
            $table->timestamps();

            $table->unique(['review_version_id', 'reported_by'], 'review_report_version_user_unique');
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('place_review_reports');
        Schema::dropIfExists('place_review_versions');
        Schema::dropIfExists('place_reviews');
    }
};
