<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $releaseId = DB::table('dev_releases')
            ->where('stage', 'beta')
            ->where('milestone', 1)
            ->where('build', 0)
            ->value('id');

        if (! $releaseId) {
            return;
        }

        $now = now();

        $itemId = DB::table('dev_release_items')->insertGetId([
            'dev_release_id' => $releaseId,
            'type' => 'feature',
            'section' => 'search',
            'sort_order' => 35,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('dev_release_item_translations')->insert([
            [
                'dev_release_item_id' => $itemId,
                'locale' => 'de',
                'text' => 'Nutze deinen Standort, um die drei nächsten passenden Plätze unter Berücksichtigung der aktuell gesetzten Filter auf der Karte zu finden.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'dev_release_item_id' => $itemId,
                'locale' => 'en',
                'text' => 'Use your location to find the three nearest matching places on the map while keeping the currently selected filters.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        $releaseId = DB::table('dev_releases')
            ->where('stage', 'beta')
            ->where('milestone', 1)
            ->where('build', 0)
            ->value('id');

        if (! $releaseId) {
            return;
        }

        $itemIds = DB::table('dev_release_items as items')
            ->join('dev_release_item_translations as translations', 'translations.dev_release_item_id', '=', 'items.id')
            ->where('items.dev_release_id', $releaseId)
            ->where('items.section', 'search')
            ->where('translations.locale', 'de')
            ->where('translations.text', 'Nutze deinen Standort, um die drei nächsten passenden Plätze unter Berücksichtigung der aktuell gesetzten Filter auf der Karte zu finden.')
            ->pluck('items.id');

        if ($itemIds->isEmpty()) {
            return;
        }

        DB::table('dev_release_items')->whereIn('id', $itemIds)->delete();
    }
};
