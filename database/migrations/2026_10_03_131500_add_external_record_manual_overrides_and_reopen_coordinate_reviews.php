<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_records', function (Blueprint $table): void {
            $table->json('manual_overrides')->nullable()->after('normalized_data');
        });

        $recordIds = DB::table('external_import_review_items as ri')
            ->join('external_records as er', 'er.id', '=', 'ri.external_record_id')
            ->where('ri.type', 'missing_coordinates')
            ->where('ri.status', 'resolved')
            ->where('er.classification', 'ignored')
            ->where('er.status', 'active')
            ->whereNull('er.place_id')
            ->distinct()
            ->pluck('er.id');

        foreach ($recordIds as $recordId) {
            $reviewId = DB::table('external_import_review_items')
                ->where('external_record_id', $recordId)
                ->where('type', 'missing_coordinates')
                ->orderByDesc('id')
                ->value('id');

            if (! $reviewId) {
                continue;
            }

            DB::table('external_import_review_items')->where('id', $reviewId)->update([
                'status' => 'pending',
                'resolved_by' => null,
                'resolved_at' => null,
                'resolution_note' => null,
                'updated_at' => now(),
            ]);

            DB::table('external_records')->where('id', $recordId)->update([
                'classification' => 'needs_review',
                'classified_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('external_records', function (Blueprint $table): void {
            $table->dropColumn('manual_overrides');
        });
    }
};
