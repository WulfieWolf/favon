<?php

namespace App\Http\Middleware;

use App\Services\SecurityEventService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class LimitFilteredBrowse
{
    public function __construct(private SecurityEventService $events)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (trim((string) $request->server('QUERY_STRING', '')) === '') {
            return $next($request);
        }

        $maxAttempts = max(
            1,
            (int) config('favon.security.filtered_browse_per_minute', 60),
        );
        $key = 'filtered-browse:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = max(1, RateLimiter::availableIn($key));

            $this->events->record($request, 'rate_limited', [
                'method' => $request->method(),
                'path' => mb_substr($request->path(), 0, 255),
                'limiter' => 'filtered-browse',
            ]);

            return response('Too Many Requests', 429, [
                'Retry-After' => (string) $retryAfter,
            ]);
        }

        RateLimiter::hit($key, 60);

        return $next($request);
    }
}
