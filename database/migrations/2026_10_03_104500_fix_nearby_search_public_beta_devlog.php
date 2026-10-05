<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $releaseId = DB::table('dev_releases')
            ->where('stage', 'beta')
            ->where('is_public', true)
            ->orderByDesc('milestone')
            ->orderByDesc('build')
            ->value('id');

        if (! $releaseId) {
            return;
        }

        $oldGerman = 'Nutze deinen Standort, um die drei nächsten passenden Plätze unter Berücksichtigung der aktuell gesetzten Filter auf der Karte zu finden.';
        $german = 'Nutze deinen Standort, um deine gefilterten Plätze in deiner Nähe zu finden.';
        $english = 'Use your location to find your filtered places nearby.';

        $itemId = DB::table('dev_release_items as items')
            ->join('dev_release_item_translations as translations', 'translations.dev_release_item_id', '=', 'items.id')
            ->where('items.dev_release_id', $releaseId)
            ->where('translations.locale', 'de')
            ->whereIn('translations.text', [$oldGerman, $german])
            ->value('items.id');

        $now = now();

        if (! $itemId) {
            $itemId = DB::table('dev_release_items')->insertGetId([
                'dev_release_id' => $releaseId,
                'type' => 'feature',
                'section' => 'search',
                'sort_order' => 35,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            DB::table('dev_release_items')
                ->where('id', $itemId)
                ->update([
                    'type' => 'feature',
                    'section' => 'search',
                    'sort_order' => 35,
                    'updated_at' => $now,
                ]);
        }

        foreach (['de' => $german, 'en' => $english] as $locale => $text) {
            DB::table('dev_release_item_translations')->updateOrInsert(
                [
                    'dev_release_item_id' => $itemId,
                    'locale' => $locale,
                ],
                [
                    'text' => $text,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }
    }

    public function down(): void
    {
        $releaseId = DB::table('dev_releases')
            ->where('stage', 'beta')
            ->where('is_public', true)
            ->orderByDesc('milestone')
            ->orderByDesc('build')
            ->value('id');

        if (! $releaseId) {
            return;
        }

        $itemIds = DB::table('dev_release_items as items')
            ->join('dev_release_item_translations as translations', 'translations.dev_release_item_id', '=', 'items.id')
            ->where('items.dev_release_id', $releaseId)
            ->where('translations.locale', 'de')
            ->where('translations.text', 'Nutze deinen Standort, um deine gefilterten Plätze in deiner Nähe zu finden.')
            ->pluck('items.id');

        if ($itemIds->isNotEmpty()) {
            DB::table('dev_release_items')->whereIn('id', $itemIds)->delete();
        }
    }
};
