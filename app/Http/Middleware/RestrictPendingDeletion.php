<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictPendingDeletion
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ($user->account_status ?? 'active') !== 'pending_deletion') {
            return $next($request);
        }

        if ($request->routeIs('account-deletion.*') || $request->routeIs('data-export.*') || $request->routeIs('logout')) {
            return $next($request);
        }

        return redirect()->route('account-deletion.show');
    }
}
