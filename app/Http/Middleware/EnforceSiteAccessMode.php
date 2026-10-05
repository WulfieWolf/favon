<?php

namespace App\Http\Middleware;

use App\Services\SiteAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceSiteAccessMode
{
    public function handle(Request $request, Closure $next): Response
    {
        $access = app(SiteAccessService::class);
        $mode = $access->mode();

        if ($mode === SiteAccessService::NORMAL) {
            return $next($request);
        }

        if ($this->isRegistrationRequest($request)) {
            if ($request->isMethod('GET')) {
                return response()->view('system.registration-closed', [
                    'message' => $access->message(),
                ]);
            }

            return redirect()->route('register');
        }

        if ($mode !== SiteAccessService::LOCKDOWN) {
            return $next($request);
        }

        if ($request->user()?->isSystemOwner() || $request->user()?->hasRole('admin')) {
            return $next($request);
        }

        if ($this->isLockdownAccessRoute($request)) {
            return $next($request);
        }

        return response()->view('system.lockdown', [
            'message' => $access->message(),
        ], 503);
    }

    private function isRegistrationRequest(Request $request): bool
    {
        return in_array($request->route()?->getName(), ['register', 'register.store'], true);
    }

    private function isLockdownAccessRoute(Request $request): bool
    {
        return in_array($request->route()?->getName(), [
            'login',
            'login.store',
            'logout',
            'password.request',
            'password.email',
            'password.reset',
            'password.update',
            'two-factor.login',
            'two-factor.login.store',
            'passkey.login',
            'passkey.login-options',
            'locale.update',
            'up',
        ], true);
    }
}
