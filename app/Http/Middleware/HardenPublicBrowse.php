<?php

namespace App\Http\Middleware;

use App\Services\SecurityEventService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HardenPublicBrowse
{
    public function __construct(private SecurityEventService $events)
    {
    }

    private const MAX_QUERY_STRING_BYTES = 8192;
    private const MAX_SEARCH_LENGTH = 120;
    private const MAX_QUERY_LEAVES = 120;
    private const MAX_NESTING_DEPTH = 5;

    public function handle(Request $request, Closure $next): Response
    {
        $queryString = (string) $request->server('QUERY_STRING', '');

        if (strlen($queryString) > self::MAX_QUERY_STRING_BYTES
            || $this->leafCount($request->query()) > self::MAX_QUERY_LEAVES
            || $this->depth($request->query()) > self::MAX_NESTING_DEPTH) {
            $this->events->record($request, 'browse_query_rejected', [
                'reason' => 'complexity',
                'query_bytes' => strlen($queryString),
            ]);

            abort(414);
        }

        $search = $request->query('q');
        if (is_array($search) || (is_string($search) && mb_strlen($search) > self::MAX_SEARCH_LENGTH)) {
            $this->events->record($request, 'browse_query_rejected', [
                'reason' => 'search_length',
                'search_length' => is_string($search) ? mb_strlen($search) : null,
            ]);

            abort(422);
        }

        return $next($request);
    }

    private function leafCount(array $values): int
    {
        $count = 0;

        array_walk_recursive($values, static function () use (&$count): void {
            $count++;
        });

        return $count;
    }

    private function depth(mixed $value, int $level = 0): int
    {
        if (! is_array($value) || $value === []) {
            return $level;
        }

        $max = $level;

        foreach ($value as $child) {
            $max = max($max, $this->depth($child, $level + 1));
        }

        return $max;
    }
}
