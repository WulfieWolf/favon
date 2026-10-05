<?php

namespace App\Http\Middleware;

use App\Services\UsageAnalyticsService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class TrackUsagePageViews
{
    public function __construct(
        private readonly UsageAnalyticsService $analytics,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldTrack($request, $response)) {
            return $response;
        }

        $routeName = $request->route()?->getName();

        if (! is_string($routeName) || $routeName === '') {
            return $response;
        }

        [$contentType, $contentId] = $this->contentReference($request);

        $this->analytics->track(
            $request,
            'page_view',
            $routeName,
            $contentType,
            $contentId,
        );

        if (in_array($routeName, ['home', 'dashboard'], true) && $this->isSearchRequest($request)) {
            $this->analytics->track($request, 'search', 'dashboard');
        }

        if (in_array($routeName, ['support.report', 'support.privacy-legal'], true)) {
            $this->analytics->track($request, 'support_started', 'support');
        }

        return $response;
    }

    private function shouldTrack(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET') || ! $response->isSuccessful()) {
            return false;
        }

        $contentType = strtolower((string) $response->headers->get('Content-Type'));

        return str_contains($contentType, 'text/html')
            && is_string($request->route()?->getName())
            && $request->route()?->getName() !== '';
    }

    private function contentReference(Request $request): array
    {
        $slug = $request->route('slug');
        if (! is_string($slug) || $slug === '') {
            return [null, null];
        }

        $placeId = DB::table('places')->where('slug', $slug)->value('id');

        return $placeId ? ['place', (int) $placeId] : [null, null];
    }

    private function isSearchRequest(Request $request): bool
    {
        return collect($request->query())
            ->except(['page'])
            ->filter(fn ($value) => $value !== null && $value !== '' && $value !== [] && $value !== false)
            ->isNotEmpty();
    }
}
