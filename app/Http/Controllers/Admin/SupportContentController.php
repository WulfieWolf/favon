<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\LocaleConfiguration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupportContentController extends Controller
{
    public function index(): View
    {
        $articles = DB::table('support_articles')->orderBy('sort_order')->orderBy('title')->get();
        $entries = DB::table('public_support_entries')
            ->orderByRaw("CASE type WHEN 'known_bug' THEN 1 WHEN 'suggested_feature' THEN 2 WHEN 'planned_feature' THEN 3 ELSE 4 END")
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        $articleTranslations = DB::table('support_article_translations')
            ->whereIn('support_article_id', $articles->pluck('id'))
            ->get()
            ->groupBy('support_article_id');
        $entryTranslations = DB::table('public_support_entry_translations')
            ->whereIn('public_support_entry_id', $entries->pluck('id'))
            ->get()
            ->groupBy('public_support_entry_id');

        $articles->each(function (object $article) use ($articleTranslations): void {
            $article->translations = $articleTranslations->get($article->id, collect())->keyBy('locale');
        });
        $entries->each(function (object $entry) use ($entryTranslations): void {
            $entry->translations = $entryTranslations->get($entry->id, collect())->keyBy('locale');
        });

        $locales = LocaleConfiguration::enabled();
        $contentSourceLocale = LocaleConfiguration::contentSource();

        return view('admin.support.content', compact('articles', 'entries', 'locales', 'contentSourceLocale'));
    }

    public function storeArticle(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            ...$this->articleTranslationRules(),
            'context_key' => ['nullable', 'regex:/^[a-z0-9-]+$/', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
        ]);

        $sourceTranslation = $validated['translations'][LocaleConfiguration::contentSource()];
        $slug = $this->uniqueSlug('support_articles', $sourceTranslation['title']);

        $articleId = DB::table('support_articles')->insertGetId([
            'slug' => $slug,
            'title' => $sourceTranslation['title'],
            'summary' => $sourceTranslation['summary'] ?? null,
            'body' => $sourceTranslation['body'],
            'context_key' => $validated['context_key'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 100,
            'is_active' => true,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->saveArticleTranslations($articleId, $validated['translations']);

        return back()->with('ui_toast', __('admin.support.status_article_created'));
    }

    public function updateArticle(Request $request, int $article): RedirectResponse
    {
        abort_unless(DB::table('support_articles')->where('id', $article)->exists(), 404);

        $validated = $request->validate([
            ...$this->articleTranslationRules(),
            'context_key' => ['nullable', 'regex:/^[a-z0-9-]+$/', 'max:100'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $sourceTranslation = $validated['translations'][LocaleConfiguration::contentSource()];

        DB::table('support_articles')->where('id', $article)->update([
            'title' => $sourceTranslation['title'],
            'summary' => $sourceTranslation['summary'] ?? null,
            'body' => $sourceTranslation['body'],
            'context_key' => $validated['context_key'] ?? null,
            'sort_order' => $validated['sort_order'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'updated_by' => $request->user()->id,
            'updated_at' => now(),
        ]);

        $this->saveArticleTranslations($article, $validated['translations']);

        return back()->with('ui_toast', __('admin.support.status_article_updated'));
    }

    public function storeEntry(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->entryRules());
        $sourceTranslation = $validated['translations'][LocaleConfiguration::contentSource()];
        $slug = $this->uniqueSlug('public_support_entries', $sourceTranslation['title']);
        $isPublic = $request->boolean('is_public');

        $entryId = DB::table('public_support_entries')->insertGetId([
            'slug' => $slug,
            'type' => $validated['type'],
            'status' => $validated['status'],
            'title' => $sourceTranslation['title'],
            'description' => $sourceTranslation['description'] ?? null,
            'context_key' => $validated['context_key'] ?? null,
            'is_public' => $isPublic,
            'sort_order' => $validated['sort_order'] ?? 100,
            'published_at' => $isPublic ? now() : null,
            'resolved_at' => in_array($validated['status'], ['resolved', 'closed'], true) ? now() : null,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->saveEntryTranslations($entryId, $validated['translations']);

        return back()->with('ui_toast', __('admin.support.status_entry_created'));
    }

    public function updateEntry(Request $request, int $entry): RedirectResponse
    {
        $current = DB::table('public_support_entries')->where('id', $entry)->first();
        abort_unless($current, 404);

        $validated = $request->validate($this->entryRules());
        $isPublic = $request->boolean('is_public');
        $sourceTranslation = $validated['translations'][LocaleConfiguration::contentSource()];

        DB::table('public_support_entries')->where('id', $entry)->update([
            'type' => $validated['type'],
            'status' => $validated['status'],
            'title' => $sourceTranslation['title'],
            'description' => $sourceTranslation['description'] ?? null,
            'context_key' => $validated['context_key'] ?? null,
            'is_public' => $isPublic,
            'sort_order' => $validated['sort_order'] ?? 100,
            'published_at' => $isPublic ? ($current->published_at ?: now()) : null,
            'resolved_at' => in_array($validated['status'], ['resolved', 'closed'], true)
                ? ($current->resolved_at ?: now())
                : null,
            'updated_by' => $request->user()->id,
            'updated_at' => now(),
        ]);

        $this->saveEntryTranslations($entry, $validated['translations']);

        return back()->with('ui_toast', __('admin.support.status_entry_updated'));
    }

    private function entryRules(): array
    {
        return [
            ...$this->entryTranslationRules(),
            'type' => ['required', Rule::in(['known_bug', 'suggested_feature', 'planned_feature'])],
            'status' => ['required', Rule::in(['reported', 'confirmed', 'suggested', 'planned', 'in_progress', 'resolved', 'closed', 'not_planned'])],
            'context_key' => ['nullable', 'regex:/^[a-z0-9-]+$/', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
        ];
    }

    private function articleTranslationRules(): array
    {
        $rules = ['translations' => ['required', 'array']];

        foreach (LocaleConfiguration::codes() as $locale) {
            $required = $locale === LocaleConfiguration::contentSource() ? 'required' : 'nullable';
            $rules["translations.{$locale}.title"] = [$required, 'string', 'max:255'];
            $rules["translations.{$locale}.summary"] = ['nullable', 'string', 'max:500'];
            $rules["translations.{$locale}.body"] = [$required, 'string', 'max:60000'];
        }

        return $rules;
    }

    private function entryTranslationRules(): array
    {
        $rules = ['translations' => ['required', 'array']];

        foreach (LocaleConfiguration::codes() as $locale) {
            $required = $locale === LocaleConfiguration::contentSource() ? 'required' : 'nullable';
            $rules["translations.{$locale}.title"] = [$required, 'string', 'max:255'];
            $rules["translations.{$locale}.description"] = ['nullable', 'string', 'max:30000'];
        }

        return $rules;
    }

    private function saveArticleTranslations(int $articleId, array $translations): void
    {
        foreach (LocaleConfiguration::codes() as $locale) {
            $translation = $translations[$locale] ?? [];
            $title = trim((string) ($translation['title'] ?? ''));
            $body = trim((string) ($translation['body'] ?? ''));

            if ($title === '' || $body === '') {
                DB::table('support_article_translations')
                    ->where('support_article_id', $articleId)
                    ->where('locale', $locale)
                    ->delete();
                continue;
            }

            DB::table('support_article_translations')->updateOrInsert(
                ['support_article_id' => $articleId, 'locale' => $locale],
                [
                    'title' => $title,
                    'summary' => filled($translation['summary'] ?? null) ? trim($translation['summary']) : null,
                    'body' => $body,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }

    private function saveEntryTranslations(int $entryId, array $translations): void
    {
        foreach (LocaleConfiguration::codes() as $locale) {
            $translation = $translations[$locale] ?? [];
            $title = trim((string) ($translation['title'] ?? ''));

            if ($title === '') {
                DB::table('public_support_entry_translations')
                    ->where('public_support_entry_id', $entryId)
                    ->where('locale', $locale)
                    ->delete();
                continue;
            }

            DB::table('public_support_entry_translations')->updateOrInsert(
                ['public_support_entry_id' => $entryId, 'locale' => $locale],
                [
                    'title' => $title,
                    'description' => filled($translation['description'] ?? null) ? trim($translation['description']) : null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }

    private function uniqueSlug(string $table, string $title): string
    {
        $base = Str::slug($title) ?: 'entry';
        $slug = $base;
        $counter = 2;

        while (DB::table($table)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }
}
