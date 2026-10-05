<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dev_release_items', function (Blueprint $table) {
            $table->string('section', 64)->nullable()->after('type');
            $table->index(['dev_release_id', 'section', 'sort_order'], 'dev_release_items_section_index');
        });

        DB::table('dev_releases')->update(['is_public' => false]);
    }

    public function down(): void
    {
        DB::table('dev_releases')
            ->where('stage', 'pre-alpha')
            ->update(['is_public' => true]);

        Schema::table('dev_release_items', function (Blueprint $table) {
            $table->dropIndex('dev_release_items_section_index');
            $table->dropColumn('section');
        });
    }
};
