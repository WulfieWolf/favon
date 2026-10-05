<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feature_categories', function (Blueprint $table): void {
            $table->integer('filter_sort_order')->nullable()->after('sort_order');
        });

        Schema::table('features', function (Blueprint $table): void {
            $table->integer('filter_priority')->nullable()->after('sort_order');
        });

        Schema::table('feature_place_types', function (Blueprint $table): void {
            $table->integer('filter_priority')->nullable()->after('visibility');
        });

        DB::table('features')
            ->where('slug', 'dogs-allowed')
            ->update(['filter_priority' => 30]);
    }

    public function down(): void
    {
        Schema::table('feature_place_types', function (Blueprint $table): void {
            $table->dropColumn('filter_priority');
        });

        Schema::table('features', function (Blueprint $table): void {
            $table->dropColumn('filter_priority');
        });

        Schema::table('feature_categories', function (Blueprint $table): void {
            $table->dropColumn('filter_sort_order');
        });
    }
};
