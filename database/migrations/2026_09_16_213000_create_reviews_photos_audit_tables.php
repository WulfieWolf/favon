<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_questions', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->foreignId('icon_id')->nullable()->constrained('icons')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_required')->default(false);
            $table->text('internal_comment')->nullable();
            $table->timestamps();
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('place_id')->constrained('places')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('status', 50)->default('pending');
            $table->boolean('is_verified_visit')->default(false);
            $table->date('visit_date')->nullable();
            $table->text('review_text')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('internal_comment')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['place_id', 'status']);
            $table->index(['user_id', 'place_id']);
            $table->index(['locale', 'status']);
        });

        Schema::create('review_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained('reviews')->cascadeOnDelete();
            $table->foreignId('review_question_id')->constrained('review_questions')->restrictOnDelete();
            $table->unsignedTinyInteger('value')->nullable();
            $table->boolean('is_not_applicable')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('internal_comment')->nullable();
            $table->timestamps();

            $table->unique(['review_id', 'review_question_id']);
        });

        Schema::create('review_helpful_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained('reviews')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['review_id', 'user_id']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('entity_type', 100);
            $table->unsignedBigInteger('entity_id');
            $table->string('action', 100);
            $table->string('source', 50)->default('system');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('internal_comment')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['entity_type', 'entity_id']);
            $table->index(['user_id', 'created_at']);
            $table->index(['source', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('review_helpful_votes');
        Schema::dropIfExists('review_answers');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('review_questions');
    }
};
