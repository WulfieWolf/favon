<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $heavyHaulageId = DB::table('vehicle_types')
            ->where('slug', 'heavy-haulage')
            ->value('id');

        if (! $heavyHaulageId) {
            return;
        }

        DB::table('place_vehicle_types')
            ->where('vehicle_type_id', $heavyHaulageId)
            ->whereIn('internal_comment', [
                'Initial aus externer Quelle übernommen.',
                'Backfill aus verknüpfter externer Quelle.',
            ])
            ->delete();
    }

    public function down(): void
    {
        // No automatic restore: the removed values came from a DATEX field
        // whose capacity semantics are intentionally no longer trusted.
    }
};
