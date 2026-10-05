<?php

use App\Http\Middleware\EnforceSiteAccessMode;
use App\Http\Middleware\HardenPublicBrowse;
use App\Http\Middleware\LimitFilteredBrowse;
use App\Http\Middleware\ProtectAuthAbuse;
use App\Http\Middleware\RecordSecurityEvents;
use App\Http\Middleware\RestrictPendingDeletion;
use App\Http\Middleware\RestrictSuspendedAccount;
use App\Http\Middleware\RequirePermission;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\TouchLastSeen;
use App\Http\Middleware\TrackUsagePageViews;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetLocale::class,
            EnforceSiteAccessMode::class,
            TouchLastSeen::class,
            RecordSecurityEvents::class,
            RestrictPendingDeletion::class,
            RestrictSuspendedAccount::class,
            ProtectAuthAbuse::class,
            SecurityHeaders::class,
            TrackUsagePageViews::class,
        ]);

        $middleware->redirectGuestsTo(function (Request $request): string {
            if ($request->is('admin') || $request->is('admin/*')) {
                abort(404);
            }

            return route('login');
        });

        $middleware->alias([
            'permission' => RequirePermission::class,
            'harden-browse' => HardenPublicBrowse::class,
            'limit-filtered-browse' => LimitFilteredBrowse::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
