<?php

namespace App\Http\Controllers;

use App\Services\DevReleaseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DevLogController extends Controller
{
    public function __invoke(DevReleaseService $versions): View
    {
        $locale = app()->getLocale();
        $fallback = config('app.fallback_locale', 'en');
        $releases = collect();

        if (Schema::hasTable('dev_releases')) {
            $releases = DB::table('dev_releases as r')
                ->leftJoin('dev_release_translations as t', function ($join) use ($locale) {
                    $join->on('t.dev_release_id', '=', 'r.id')->where('t.locale', $locale);
                })
                ->leftJoin('dev_release_translations as tf', function ($join) use ($fallback) {
                    $join->on('tf.dev_release_id', '=', 'r.id')->where('tf.locale', $fallback);
                })
                ->where('r.is_public', true)
                ->orderByDesc('r.milestone')
                ->orderByDesc('r.build')
                ->get([
                    'r.id', 'r.stage', 'r.milestone', 'r.build', 'r.released_at',
                    DB::raw('COALESCE(t.title, tf.title) as title'),
                    DB::raw('COALESCE(t.summary, tf.summary) as summary'),
                ]);

            foreach ($releases as $release) {
                $release->version_label = $versions->label($release);
                $release->items = DB::table('dev_release_items as i')
                    ->leftJoin('dev_release_item_translations as t', function ($join) use ($locale) {
                        $join->on('t.dev_release_item_id', '=', 'i.id')->where('t.locale', $locale);
                    })
                    ->leftJoin('dev_release_item_translations as tf', function ($join) use ($fallback) {
                        $join->on('tf.dev_release_item_id', '=', 'i.id')->where('tf.locale', $fallback);
                    })
                    ->where('i.dev_release_id', $release->id)
                    ->orderBy('i.sort_order')
                    ->orderBy('i.id')
                    ->get([
                        'i.type', 'i.section',
                        DB::raw('COALESCE(t.text, tf.text) as text'),
                    ]);
            }
        }

        return view('devlog.index', compact('releases'));
    }
}
