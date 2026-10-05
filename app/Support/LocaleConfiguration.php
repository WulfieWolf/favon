<?php

namespace App\Support;

final class LocaleConfiguration
{
    /**
     * @return array<string, array{enabled?: bool, native_name?: string, flag?: string}>
     */
    public static function available(): array
    {
        return config('locales.available', []);
    }

    /**
     * @return array<string, array{enabled?: bool, native_name?: string, flag?: string}>
     */
    public static function enabled(): array
    {
        return array_filter(
            self::available(),
            static fn (array $locale): bool => (bool) ($locale['enabled'] ?? false),
        );
    }

    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_keys(self::enabled());
    }

    public static function fallback(): string
    {
        $fallback = (string) config('locales.fallback', config('app.fallback_locale', 'en'));

        return in_array($fallback, self::codes(), true)
            ? $fallback
            : (self::codes()[0] ?? 'en');
    }

    public static function contentSource(): string
    {
        $contentSource = (string) config('locales.content_source', self::fallback());

        return in_array($contentSource, self::codes(), true)
            ? $contentSource
            : self::fallback();
    }

    public static function supports(string $locale): bool
    {
        return in_array($locale, self::codes(), true);
    }
}
