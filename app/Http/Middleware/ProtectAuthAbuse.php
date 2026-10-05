<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ProtectAuthAbuse
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('POST') && $request->is('forgot-password')) {
            $key = 'password-reset-ip:'.$request->ip();

            if (RateLimiter::tooManyAttempts($key, 20)) {
                abort(429);
            }

            RateLimiter::hit($key, 3600);
        }

        return $next($request);
    }
}
