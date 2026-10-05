<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('place_merges', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('source_place_id')->constrained('places')->restrictOnDelete();
            $table->foreignId('target_place_id')->constrained('places')->restrictOnDelete();
            $table->foreignId('merged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('completed');
            $table->json('decisions');
            $table->json('snapshot');
            $table->timestamp('merged_at');
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->timestamps();

            $table->index('source_place_id');
            $table->index(['target_place_id', 'status']);
        });

        Schema::create('photo_merge_conflicts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('place_merge_id')->constrained('place_merges')->cascadeOnDelete();
            $table->foreignId('place_id')->constrained('places')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 30)->default('selection_required');
            $table->unsignedSmallInteger('photo_limit')->default(5);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->unique(['place_merge_id', 'place_id', 'user_id'], 'photo_merge_conflicts_scope_unique');
            $table->index(['user_id', 'status']);
        });

        Schema::create('photo_merge_conflict_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('photo_merge_conflict_id')->constrained('photo_merge_conflicts')->cascadeOnDelete();
            $table->foreignId('photo_id')->constrained('photos')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['photo_merge_conflict_id', 'photo_id'], 'photo_merge_conflict_items_unique');
            $table->index('photo_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photo_merge_conflict_items');
        Schema::dropIfExists('photo_merge_conflicts');
        Schema::dropIfExists('place_merges');
    }
};
