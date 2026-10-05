<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feature_id')->unique()->constrained('features')->cascadeOnDelete();
            $table->json('config');
            $table->unsignedInteger('sort_order')->default(10);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::table('place_features', function (Blueprint $table) {
            $table->string('status', 32)->default('available')->after('feature_option_id');
            $table->json('metadata')->nullable()->after('unit_key');
            $table->index(['feature_id', 'status', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('place_features', function (Blueprint $table) {
            $table->dropIndex(['feature_id', 'status', 'is_active']);
            $table->dropColumn(['status', 'metadata']);
        });

        Schema::dropIfExists('feature_workflows');
    }
};