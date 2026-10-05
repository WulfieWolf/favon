<?php

namespace App\Http\Middleware;

use App\Support\LocaleConfiguration;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supportedLocales = LocaleConfiguration::codes();
        $browserLocale = $request->headers->has('Accept-Language')
            ? $request->getPreferredLanguage($supportedLocales)
            : null;

        $locale = $request->session()->get('locale')
            ?? $request->cookie('locale')
            ?? $request->user()?->locale
            ?? $browserLocale
            ?? LocaleConfiguration::fallback();

        if (! LocaleConfiguration::supports($locale)) {
            $locale = LocaleConfiguration::fallback();
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
