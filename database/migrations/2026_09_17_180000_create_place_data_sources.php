<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('place_data_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('place_id')->constrained('places')->cascadeOnDelete();
            $table->string('source_type', 32)->default('other');
            $table->string('source_name')->nullable();
            $table->string('source_url', 2048)->nullable();
            $table->string('source_reference')->nullable();
            $table->timestamp('observed_at')->nullable();
            $table->foreignId('provided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->text('internal_comment')->nullable();
            $table->timestamps();

            $table->index(['place_id', 'is_active'], 'pds_place_active_idx');
            $table->index(['source_type', 'is_active'], 'pds_type_active_idx');
            $table->index('observed_at', 'pds_observed_at_idx');
        });

        Schema::create('place_data_source_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('place_data_source_id')
                ->constrained('place_data_sources')
                ->cascadeOnDelete();
            $table->string('entity_type', 64);
            $table->unsignedBigInteger('entity_id');
            $table->string('field_name', 64)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('internal_comment')->nullable();
            $table->timestamps();

            $table->index(
                ['entity_type', 'entity_id', 'is_active'],
                'pdsl_entity_active_idx'
            );
            $table->index(
                ['place_data_source_id', 'is_active'],
                'pdsl_source_active_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('place_data_source_links');
        Schema::dropIfExists('place_data_sources');
    }
};
