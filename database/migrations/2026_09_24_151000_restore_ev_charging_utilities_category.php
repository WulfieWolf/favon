<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $utilitiesId = DB::table('feature_categories')
            ->where('slug', 'utilities')
            ->value('id');

        if ($utilitiesId) {
            DB::table('features')
                ->where('slug', 'ev-charging')
                ->update([
                    'category_id' => $utilitiesId,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        // Deliberately no-op: utilities is the canonical V1 category for
        // ev-charging and should not be moved back to fuel-rest-area.
    }
};
