<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suggestable_fields', function (Blueprint $table) {
            $table->id();
            $table->string('target_table', 64);
            $table->string('target_field', 64);
            $table->boolean('is_suggestable')->default(true);
            $table->boolean('allow_create')->default(false);
            $table->boolean('allow_update')->default(true);
            $table->boolean('allow_deactivate')->default(false);
            $table->unsignedInteger('sort_order')->default(10);
            $table->boolean('is_active')->default(true);
            $table->text('internal_comment')->nullable();
            $table->timestamps();

            $table->unique(['target_table', 'target_field'], 'suggestable_fields_target_unique');
            $table->index(['target_table', 'is_suggestable', 'is_active'], 'suggestable_fields_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suggestable_fields');
    }
};
