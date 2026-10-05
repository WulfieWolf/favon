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

        [$contentType, $contentId] = $this->contentReference($request, $routeName);

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

        $startEvent = match ($routeName) {
            'places.suggest.create' => ['place_suggestion_started', 'place_suggestions'],
            'places.info-suggest.edit' => ['change_suggestion_started', 'place_information'],
            'places.opening-hours.edit' => ['opening_hours_started', 'opening_hours'],
            'places.prices.edit' => ['price_edit_started', 'prices'],
            'support.report', 'support.privacy-legal' => ['support_started', 'support'],
            default => null,
        };

        if ($startEvent) {
            $this->analytics->track(
                $request,
                $startEvent[0],
                $startEvent[1],
                $contentType,
                $contentId,
            );
        }

        return $response;
    }

    private function shouldTrack(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET') || ! $response->isSuccessful()) {
            return false;
        }

        $contentType = strtolower((string) $response->headers->get('Content-Type'));
        if (! str_contains($contentType, 'text/html')) {
            return false;
        }

        $routeName = $request->route()?->getName();

        if (! is_string($routeName) || $routeName === '') {
            return false;
        }

        return ! in_array($routeName, [
            'reviews.feed',
            'photos.show',
            'photos.owner',
            'admin.photos.asset',
        ], true);
    }

    private function contentReference(Request $request, string $routeName): array
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
