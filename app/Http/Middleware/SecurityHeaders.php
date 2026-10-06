<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(self), payment=()',
        );
        $response->headers->remove('X-Powered-By');

        if ($this->shouldNotIndex($request)) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
        }

        if (app()->isProduction() && $request->isSecure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains',
            );
        }

        return $response;
    }

    private function shouldNotIndex(Request $request): bool
    {
        return $request->is(
            '/',
            'auth/telegram/callback',
            'admin',
            'admin/*',
            'login',
            'register',
            'forgot-password',
            'reset-password/*',
            'settings',
            'settings/*',
            'notifications',
            'notifications/*',
            'support/report',
            'support/datenschutz-recht',
            'support/my',
            'support/my/*',
            'my/*',
            'role-preview',
        );
    }
}
