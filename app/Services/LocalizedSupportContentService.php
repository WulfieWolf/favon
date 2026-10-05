<?php

namespace App\Services;

use App\Support\LocaleConfiguration;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class LocalizedSupportContentService
{
    public function articles(?string $locale = null): Builder
    {
        $locale ??= app()->getLocale();
        $fallback = LocaleConfiguration::fallback();

        return DB::table('support_articles as sa')
            ->leftJoin('support_article_translations as current_translation', function ($join) use ($locale): void {
                $join->on('current_translation.support_article_id', '=', 'sa.id')
                    ->where('current_translation.locale', $locale);
            })
            ->leftJoin('support_article_translations as fallback_translation', function ($join) use ($fallback): void {
                $join->on('fallback_translation.support_article_id', '=', 'sa.id')
                    ->where('fallback_translation.locale', $fallback);
            })
            ->select([
                'sa.*',
                DB::raw('COALESCE(current_translation.title, fallback_translation.title, sa.title) as title'),
                DB::raw('CASE WHEN current_translation.id IS NOT NULL THEN current_translation.summary WHEN fallback_translation.id IS NOT NULL THEN fallback_translation.summary ELSE sa.summary END as summary'),
                DB::raw('COALESCE(current_translation.body, fallback_translation.body, sa.body) as body'),
            ]);
    }

    public function entries(?string $locale = null): Builder
    {
        $locale ??= app()->getLocale();
        $fallback = LocaleConfiguration::fallback();

        return DB::table('public_support_entries as pse')
            ->leftJoin('public_support_entry_translations as current_translation', function ($join) use ($locale): void {
                $join->on('current_translation.public_support_entry_id', '=', 'pse.id')
                    ->where('current_translation.locale', $locale);
            })
            ->leftJoin('public_support_entry_translations as fallback_translation', function ($join) use ($fallback): void {
                $join->on('fallback_translation.public_support_entry_id', '=', 'pse.id')
                    ->where('fallback_translation.locale', $fallback);
            })
            ->select([
                'pse.*',
                DB::raw('COALESCE(current_translation.title, fallback_translation.title, pse.title) as title'),
                DB::raw('CASE WHEN current_translation.id IS NOT NULL THEN current_translation.description WHEN fallback_translation.id IS NOT NULL THEN fallback_translation.description ELSE pse.description END as description'),
            ]);
    }
}
