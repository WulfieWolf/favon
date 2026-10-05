<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_media_states', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('external_source_id')->constrained('external_sources')->cascadeOnDelete();
            $table->foreignId('external_record_id')->nullable()->constrained('external_records')->nullOnDelete();
            $table->string('external_media_id', 64);
            $table->string('status', 32);
            $table->string('reason', 255)->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();

            $table->unique(['external_source_id', 'external_media_id'], 'external_media_states_source_media_unique');
            $table->index(['status', 'external_source_id'], 'external_media_states_status_source_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_media_states');
    }
};
