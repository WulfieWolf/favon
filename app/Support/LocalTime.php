<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DateTimeInterface;

class LocalTime
{
    public static function timezone(): string
    {
        return (string) config('app.display_timezone', 'Europe/Berlin');
    }

    public static function parse(mixed $value): ?CarbonImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return CarbonImmutable::instance($value)->setTimezone(self::timezone());
        }

        if ($value instanceof DateTimeInterface) {
            return CarbonImmutable::instance($value)->setTimezone(self::timezone());
        }

        return CarbonImmutable::parse((string) $value, 'UTC')
            ->setTimezone(self::timezone());
    }

    public static function format(mixed $value, string $format): ?string
    {
        return self::parse($value)?->format($format);
    }

    public static function translatedFormat(mixed $value, string $format): ?string
    {
        return self::parse($value)?->translatedFormat($format);
    }

    public static function diffForHumans(mixed $value): ?string
    {
        return self::parse($value)?->diffForHumans(now()->setTimezone(self::timezone()));
    }
}
