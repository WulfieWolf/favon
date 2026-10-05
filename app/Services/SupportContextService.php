<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupportContextService
{
    public function fromRequest(Request $request): array
    {
        $routeName = $request->route()?->getName();
        $routeContexts = config('camperwolf_support.route_contexts', []);
        $definition = $routeName && is_array($routeContexts) ? ($routeContexts[$routeName] ?? null) : null;

        return [
            'context_key' => is_array($definition) ? ($definition['context'] ?? 'general') : 'general',
            'module' => is_array($definition) ? ($definition['module'] ?? 'general') : 'general',
            'route_name' => $routeName,
            'source_url' => $request->fullUrl(),
        ];
    }

    public function helpArticleForContext(?string $contextKey): ?object
    {
        if (! $contextKey) {
            return null;
        }

        return DB::table('support_articles')
            ->where('context_key', $contextKey)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();
    }

    public function sanitizeContext(mixed $contextKey): ?string
    {
        if (! is_string($contextKey) || ! preg_match('/^[a-z0-9-]{1,100}$/', $contextKey)) {
            return null;
        }

        return $contextKey;
    }

    public function sanitizeModule(mixed $module): ?string
    {
        if (! is_string($module) || ! preg_match('/^[a-z0-9-]{1,100}$/', $module)) {
            return null;
        }

        return $module;
    }
}
