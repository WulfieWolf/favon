<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class RestrictSuspendedAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user()?->fresh();

        if (! $user || ($user->account_status ?? 'active') !== 'suspended') {
            return $next($request);
        }

        if ($user->suspended_until && now()->greaterThanOrEqualTo($user->suspended_until)) {
            DB::table('users')->where('id', $user->id)->update([
                'account_status' => 'active',
                'suspension_reason' => null,
                'suspended_until' => null,
                'updated_at' => now(),
            ]);
            $user->account_status = 'active';

            return $next($request);
        }

        if ($request->routeIs('logout') || $request->routeIs('locale.update')) {
            return $next($request);
        }

        return response()->view('system.account-suspended', [
            'until' => $user->suspended_until,
        ], 403);
    }
}
