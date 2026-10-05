<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class TouchLastSeen
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            $lastTouch = (int) $request->session()->get('last_seen_touch', 0);

            if ($lastTouch < now()->subHour()->timestamp) {
                DB::table('users')
                    ->where('id', $request->user()->id)
                    ->update(['last_seen_at' => now()]);

                $request->session()->put('last_seen_touch', now()->timestamp);
            }
        }

        return $next($request);
    }
}
