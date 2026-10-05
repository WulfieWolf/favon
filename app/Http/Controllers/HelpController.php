<?php

namespace App\Http\Controllers;

use App\Services\LocalizedSupportContentService;
use App\Services\SupportContextService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class HelpController extends Controller
{
    public function index(Request $request, LocalizedSupportContentService $content): View
    {
        $q = trim((string) $request->query('q', ''));

        $articles = $content->articles()
            ->where('sa.is_active', true)
            ->when($q !== '', function ($query) use ($q): void {
                $like = '%'.$q.'%';
                $query->where(function ($inner) use ($like): void {
                    $inner->whereRaw('COALESCE(current_translation.title, fallback_translation.title, sa.title) LIKE ?', [$like])
                        ->orWhereRaw('CASE WHEN current_translation.id IS NOT NULL THEN current_translation.summary WHEN fallback_translation.id IS NOT NULL THEN fallback_translation.summary ELSE sa.summary END LIKE ?', [$like])
                        ->orWhereRaw('COALESCE(current_translation.body, fallback_translation.body, sa.body) LIKE ?', [$like]);
                });
            })
            ->orderBy('sa.sort_order')
            ->orderByRaw('COALESCE(current_translation.title, fallback_translation.title, sa.title)')
            ->get();

        return view('help.index', compact('articles', 'q'));
    }

    public function show(string $slug, LocalizedSupportContentService $content): View
    {
        $article = $content->articles()
            ->where('sa.slug', $slug)
            ->where('sa.is_active', true)
            ->first();

        abort_unless($article, 404);

        $linkedArticles = $content->articles()
            ->where('sa.is_active', true)
            ->get()
            ->keyBy('slug');

        $article->body = preg_replace_callback(
            '/\\[\\[([a-z0-9-]+)(?:\\|([^\\]\\r\\n]+))?\\]\\]/',
            function (array $matches) use ($linkedArticles): string {
                $target = $linkedArticles->get($matches[1]);

                if (! $target) {
                    return $matches[0];
                }

                $label = trim($matches[2] ?? '') ?: $target->title;
                $label = str_replace(['\\\\', '[', ']'], ['\\\\\\\\', '\\[', '\\]'], $label);

                return '['.$label.']('.route('help.show', $target->slug).')';
            },
            $article->body,
        );

        return view('help.show', compact('article'));
    }

    public function context(Request $request, SupportContextService $contexts): Response
    {
        $contextKey = $contexts->sanitizeContext($request->query('context'));
        $article = $contexts->helpArticleForContext($contextKey);

        if ($article) {
            return redirect()->route('help.show', $article->slug);
        }

        return redirect()->route('help.index');
    }

    public function roadmap(Request $request, LocalizedSupportContentService $content): View
    {
        $type = (string) $request->query('type', '');
        $allowedTypes = ['known_bug', 'suggested_feature', 'planned_feature'];

        if (! in_array($type, $allowedTypes, true)) {
            $type = '';
        }

        $entries = $content->entries()
            ->where('pse.is_public', true)
            ->when($type !== '', fn ($query) => $query->where('pse.type', $type))
            ->orderByRaw("CASE pse.status WHEN 'in_progress' THEN 1 WHEN 'confirmed' THEN 2 WHEN 'planned' THEN 3 WHEN 'suggested' THEN 4 WHEN 'resolved' THEN 5 WHEN 'closed' THEN 6 WHEN 'not_planned' THEN 7 ELSE 8 END")
            ->orderBy('pse.sort_order')
            ->orderByDesc('pse.updated_at')
            ->get();

        return view('help.roadmap', compact('entries', 'type'));
    }
}
