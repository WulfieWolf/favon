<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_sources', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->string('name');
            $table->string('provider')->nullable();
            $table->string('source_type', 32)->default('api');
            $table->string('adapter', 64)->nullable();
            $table->text('base_url')->nullable();
            $table->char('country_code', 2)->nullable();
            $table->string('license_code', 100)->nullable();
            $table->string('license_name')->nullable();
            $table->text('license_url')->nullable();
            $table->text('attribution_text')->nullable();
            $table->unsignedInteger('sync_interval_minutes')->nullable();
            $table->boolean('is_active')->default(false);
            $table->json('config')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'last_checked_at']);
            $table->index(['country_code', 'is_active']);
        });

        Schema::create('external_import_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('external_source_id')->constrained('external_sources')->cascadeOnDelete();
            $table->string('mode', 16)->default('dry_run');
            $table->string('status', 32)->default('queued');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('fetched_at')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('source_record_count')->nullable();
            $table->unsignedBigInteger('mapped_record_count')->default(0);
            $table->unsignedBigInteger('created_count')->default(0);
            $table->unsignedBigInteger('updated_count')->default(0);
            $table->unsignedBigInteger('skipped_count')->default(0);
            $table->unsignedBigInteger('conflict_count')->default(0);
            $table->unsignedBigInteger('review_count')->default(0);
            $table->string('payload_sha256', 64)->nullable();
            $table->string('error_code', 100)->nullable();
            $table->text('error_message')->nullable();
            $table->json('validation_report')->nullable();
            $table->json('stats')->nullable();
            $table->timestamps();

            $table->index(['external_source_id', 'status', 'started_at'], 'eir_source_status_started_idx');
            $table->index(['status', 'created_at']);
        });

        Schema::create('external_raw_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('external_source_id')->constrained('external_sources')->cascadeOnDelete();
            $table->foreignId('external_import_run_id')->nullable()->constrained('external_import_runs')->nullOnDelete();
            $table->string('storage_path');
            $table->string('sha256', 64);
            $table->string('content_type', 150)->nullable();
            $table->unsignedBigInteger('byte_size')->nullable();
            $table->timestamp('fetched_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['external_source_id', 'fetched_at']);
            $table->index('expires_at');
        });

        Schema::create('external_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('external_source_id')->constrained('external_sources')->cascadeOnDelete();
            $table->string('external_id', 255);
            $table->foreignId('place_id')->nullable()->constrained('places')->nullOnDelete();
            $table->string('status', 32)->default('active');
            $table->string('payload_hash', 64)->nullable();
            $table->string('normalized_hash', 64)->nullable();
            $table->json('normalized_data')->nullable();
            $table->timestamp('source_updated_at')->nullable();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('missing_since')->nullable();
            $table->timestamps();

            $table->unique(['external_source_id', 'external_id'], 'er_source_external_unique');
            $table->index(['external_source_id', 'status']);
            $table->index(['place_id', 'status']);
        });

        Schema::create('external_record_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('external_record_id')->constrained('external_records')->cascadeOnDelete();
            $table->string('field_key', 150);
            $table->json('value')->nullable();
            $table->string('value_hash', 64)->nullable();
            $table->timestamp('source_updated_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['external_record_id', 'field_key']);
            $table->index(['field_key', 'last_seen_at']);
        });

        Schema::create('external_import_review_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('external_import_run_id')->constrained('external_import_runs')->cascadeOnDelete();
            $table->foreignId('external_source_id')->constrained('external_sources')->cascadeOnDelete();
            $table->foreignId('external_record_id')->nullable()->constrained('external_records')->nullOnDelete();
            $table->foreignId('place_id')->nullable()->constrained('places')->nullOnDelete();
            $table->string('type', 64);
            $table->string('severity', 16)->default('warning');
            $table->string('status', 24)->default('pending');
            $table->json('details')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_note')->nullable();
            $table->timestamps();

            $table->index(['status', 'severity', 'created_at'], 'eiri_queue_idx');
            $table->index(['external_source_id', 'status']);
            $table->index(['place_id', 'status']);
        });

        Schema::create('place_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('place_id')->constrained('places')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('external_source_id')->nullable()->constrained('external_sources')->nullOnDelete();
            $table->string('actor_type', 32);
            $table->string('action', 100);
            $table->text('summary');
            $table->json('metadata')->nullable();
            $table->boolean('is_public')->default(true);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['place_id', 'created_at']);
            $table->index(['external_source_id', 'created_at']);
            $table->index(['actor_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('place_history');
        Schema::dropIfExists('external_import_review_items');
        Schema::dropIfExists('external_record_fields');
        Schema::dropIfExists('external_records');
        Schema::dropIfExists('external_raw_snapshots');
        Schema::dropIfExists('external_import_runs');
        Schema::dropIfExists('external_sources');
    }
};
