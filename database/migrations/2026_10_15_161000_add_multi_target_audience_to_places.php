<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('places', function (Blueprint $table): void {
            $table->boolean('target_mm')->nullable()->after('target_audience');
            $table->boolean('target_mf')->nullable()->after('target_mm');
        });

        DB::table('places')
            ->where('target_audience', 'mm')
            ->update(['target_mm' => true, 'target_mf' => false]);

        DB::table('places')
            ->where('target_audience', 'mf')
            ->update(['target_mm' => false, 'target_mf' => true]);
    }

    public function down(): void
    {
        Schema::table('places', function (Blueprint $table): void {
            $table->dropColumn(['target_mm', 'target_mf']);
        });
    }
};
