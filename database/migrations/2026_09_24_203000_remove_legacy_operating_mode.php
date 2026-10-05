<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('places')
            ->whereIn('id', function ($query) {
                $query->select('er.place_id')
                    ->from('external_records as er')
                    ->join('external_sources as es', 'es.id', '=', 'er.external_source_id')
                    ->where('es.source_type', 'datex2')
                    ->whereNotNull('er.place_id');
            })
            ->where('opening_status', 'unclear')
            ->update([
                'opening_status' => 'open',
                'updated_at' => now(),
            ]);

        DB::table('suggestable_fields')
            ->where('target_table', 'place_details')
            ->where('target_field', 'operating_mode')
            ->update([
                'is_suggestable' => false,
                'is_active' => false,
                'updated_at' => now(),
            ]);

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

        DB::table('suggestable_fields')
            ->where('target_table', 'place_details')
            ->where('target_field', 'operating_mode')
            ->update([
                'is_suggestable' => true,
                'is_active' => true,
                'updated_at' => now(),
            ]);
    }
};
