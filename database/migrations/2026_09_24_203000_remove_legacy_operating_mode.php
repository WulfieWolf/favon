<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('place_details', function (Blueprint $table) {
            $table->dropIndex('place_details_operating_mode_idx');
            $table->dropColumn('operating_mode');
        });
    }

    public function down(): void
    {
        Schema::table('place_details', function (Blueprint $table) {
            $table->string('operating_mode', 32)->default('unclear')->after('pitch_count');
            $table->index(['operating_mode', 'is_active'], 'place_details_operating_mode_idx');
        });
    }
};
