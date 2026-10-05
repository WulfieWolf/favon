<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('features', function (Blueprint $table) {
            $table->string('value_type', 32)
                ->default('boolean')
                ->after('slug');

            $table->string('unit_type', 32)
                ->nullable()
                ->after('value_type');

            $table->index(['value_type', 'is_active']);
        });

        Schema::table('place_features', function (Blueprint $table) {
            $table->decimal('value_number', 14, 4)
                ->nullable()
                ->after('feature_option_id');

            $table->text('value_text')
                ->nullable()
                ->after('value_number');

            $table->string('unit_key', 16)
                ->nullable()
                ->after('value_text');

            $table->index(['feature_id', 'value_number']);
        });
    }

    public function down(): void
    {
        Schema::table('place_features', function (Blueprint $table) {
            $table->dropIndex(['feature_id', 'value_number']);
            $table->dropColumn([
                'value_number',
                'value_text',
                'unit_key',
            ]);
        });

        Schema::table('features', function (Blueprint $table) {
            $table->dropIndex(['value_type', 'is_active']);
            $table->dropColumn([
                'value_type',
                'unit_type',
            ]);
        });
    }
};
