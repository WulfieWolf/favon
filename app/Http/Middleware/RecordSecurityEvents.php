<?php

namespace App\Http\Middleware;

use App\Services\SecurityEventService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class RecordSecurityEvents
{
    public function __construct(private SecurityEventService $events)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        try {
            $response = $next($request);
        } catch (Throwable $exception) {
            if ($exception instanceof HttpExceptionInterface && $exception->getStatusCode() === 429) {
                $this->events->record($request, 'rate_limited', [
                    'method' => $request->method(),
                    'path' => mb_substr($request->path(), 0, 255),
                ]);
            }

            throw $exception;
        }

        if ($response->getStatusCode() === 429) {
            $this->events->record($request, 'rate_limited', [
                'method' => $request->method(),
                'path' => mb_substr($request->path(), 0, 255),
            ]);
        }

        return $response;
    }
}
