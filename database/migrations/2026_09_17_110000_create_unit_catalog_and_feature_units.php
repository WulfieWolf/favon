<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('unit_key', 16)->unique();
            $table->string('symbol', 16);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('unit_type_units', function (Blueprint $table) {
            $table->id();
            $table->string('unit_type', 32);
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['unit_type', 'unit_id']);
            $table->index(['unit_type', 'sort_order']);
        });

        Schema::create('feature_allowed_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feature_id')->constrained('features')->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->string('role', 16)->default('value');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['feature_id', 'unit_id', 'role']);
            $table->index(['feature_id', 'role', 'sort_order']);
        });

        Schema::table('place_features', function (Blueprint $table) {
            $table->foreignId('unit_id')
                ->nullable()
                ->after('unit_key')
                ->constrained('units')
                ->nullOnDelete();

            $table->decimal('rate_quantity', 14, 4)
                ->nullable()
                ->after('unit_id');

            $table->foreignId('rate_unit_id')
                ->nullable()
                ->after('rate_quantity')
                ->constrained('units')
                ->nullOnDelete();

            $table->index(['feature_id', 'unit_id']);
            $table->index(['feature_id', 'rate_unit_id']);
        });
    }

    public function down(): void
    {
        Schema::table('place_features', function (Blueprint $table) {
            $table->dropIndex(['feature_id', 'rate_unit_id']);
            $table->dropIndex(['feature_id', 'unit_id']);
            $table->dropConstrainedForeignId('rate_unit_id');
            $table->dropColumn('rate_quantity');
            $table->dropConstrainedForeignId('unit_id');
        });

        Schema::dropIfExists('feature_allowed_units');
        Schema::dropIfExists('unit_type_units');
        Schema::dropIfExists('units');
    }
};
