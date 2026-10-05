<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $slugs = ['light-truck', 'heavy-truck', 'truck-combination', 'heavy-haulage'];
        $ids = DB::table('vehicle_types')->whereIn('slug', $slugs)->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        DB::table('place_vehicle_types')->whereIn('vehicle_type_id', $ids)->delete();
        DB::table('translations')
            ->whereIn('entity_type', ['vehicle_type', 'vehicle_types'])
            ->whereIn('entity_id', $ids)
            ->delete();
        DB::table('vehicle_types')->whereIn('id', $ids)->delete();
    }

    public function down(): void
    {
        // Deliberately not recreated: these redundant vehicle categories were
        // removed from the Camperwolf catalogue rather than migrated.
    }
};
