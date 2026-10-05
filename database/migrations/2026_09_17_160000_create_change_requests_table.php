<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('place_id')->constrained('places')->cascadeOnDelete();
            $table->foreignId('suggestable_field_id')->constrained('suggestable_fields')->restrictOnDelete();
            $table->unsignedBigInteger('target_record_id')->nullable();
            $table->string('operation', 16);
            $table->json('original_value')->nullable();
            $table->json('proposed_value')->nullable();
            $table->string('status', 24)->default('pending');
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->useCurrent();
            $table->text('user_comment')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('moderator_comment')->nullable();
            $table->unsignedBigInteger('result_record_id')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->index(['place_id', 'status'], 'cr_place_status_idx');
            $table->index(['suggestable_field_id', 'status'], 'cr_field_status_idx');
            $table->index(['target_record_id', 'status'], 'cr_target_status_idx');
            $table->index(['submitted_by', 'status'], 'cr_submitter_status_idx');
            $table->index(['status', 'submitted_at'], 'cr_queue_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('change_requests');
    }
};
