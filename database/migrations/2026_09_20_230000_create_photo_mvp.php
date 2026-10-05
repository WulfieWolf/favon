<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('photos', function (Blueprint $table): void {
            $table->uuid('uuid')->nullable()->unique()->after('id');
            $table->string('source_path')->nullable()->after('storage_path');
            $table->string('preview_path')->nullable()->after('source_path');
            $table->unsignedBigInteger('preview_file_size')->nullable()->after('file_size');
            $table->foreignId('moderated_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable()->after('moderated_by');
            $table->string('moderation_reason', 1000)->nullable()->after('moderated_at');
            $table->text('processing_error')->nullable()->after('moderation_reason');
        });

        Schema::table('place_photos', function (Blueprint $table): void {
            $table->foreignId('place_review_id')->nullable()->after('place_id')->constrained('place_reviews')->nullOnDelete();
            $table->boolean('is_thumbnail_eligible')->default(true)->after('sort_order');
            $table->foreignId('thumbnail_excluded_by')->nullable()->after('is_thumbnail_eligible')->constrained('users')->nullOnDelete();
            $table->timestamp('thumbnail_excluded_at')->nullable()->after('thumbnail_excluded_by');
            $table->string('thumbnail_exclusion_reason', 1000)->nullable()->after('thumbnail_excluded_at');

            $table->index(['place_review_id', 'is_active'], 'place_photos_review_active_idx');
            $table->index(['place_id', 'is_thumbnail_eligible', 'is_active'], 'place_photos_thumbnail_idx');
        });

        Schema::create('photo_helpful_votes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('photo_id')->constrained('photos')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['photo_id', 'user_id'], 'photo_helpful_votes_unique');
            $table->index(['photo_id', 'created_at'], 'photo_helpful_votes_photo_idx');
        });

        Schema::create('photo_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('photo_id')->constrained('photos')->cascadeOnDelete();
            $table->foreignId('reported_by')->constrained('users')->cascadeOnDelete();
            $table->string('reason', 40);
            $table->string('comment', 1000)->nullable();
            $table->string('status', 30)->default('pending');
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('moderator_comment', 2000)->nullable();
            $table->timestamp('moderated_at')->nullable();
            $table->timestamps();

            $table->unique(['photo_id', 'reported_by'], 'photo_reports_photo_user_unique');
            $table->index(['status', 'created_at'], 'photo_reports_status_idx');
        });

        Schema::create('place_photo_settings', function (Blueprint $table): void {
            $table->foreignId('place_id')->primary()->constrained('places')->cascadeOnDelete();
            $table->foreignId('fallback_photo_id')->nullable()->constrained('photos')->nullOnDelete();
            $table->foreignId('vote_photo_id')->nullable()->constrained('photos')->nullOnDelete();
            $table->foreignId('admin_photo_id')->nullable()->constrained('photos')->nullOnDelete();
            $table->foreignId('admin_selected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('admin_selected_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('place_photo_settings');
        Schema::dropIfExists('photo_reports');
        Schema::dropIfExists('photo_helpful_votes');

        Schema::table('place_photos', function (Blueprint $table): void {
            $table->dropIndex('place_photos_review_active_idx');
            $table->dropIndex('place_photos_thumbnail_idx');
            $table->dropConstrainedForeignId('place_review_id');
            $table->dropConstrainedForeignId('thumbnail_excluded_by');
            $table->dropColumn([
                'is_thumbnail_eligible',
                'thumbnail_excluded_at',
                'thumbnail_exclusion_reason',
            ]);
        });

        Schema::table('photos', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('moderated_by');
            $table->dropUnique(['uuid']);
            $table->dropColumn([
                'uuid',
                'source_path',
                'preview_path',
                'preview_file_size',
                'moderated_at',
                'moderation_reason',
                'processing_error',
            ]);
        });
    }
};
