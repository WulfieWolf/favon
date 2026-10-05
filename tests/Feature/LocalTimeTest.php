<?php

namespace Tests\Feature;

use App\Support\LocalTime;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class LocalTimeTest extends TestCase
{
    public function test_it_converts_utc_timestamp_to_berlin_summer_time(): void
    {
        config(['app.display_timezone' => 'Europe/Berlin']);

        self::assertSame(
            '01.10.2026 15:59',
            LocalTime::format('2026-10-01 13:59:00', 'd.m.Y H:i'),
        );
    }

    public function test_it_converts_utc_timestamp_to_berlin_winter_time(): void
    {
        config(['app.display_timezone' => 'Europe/Berlin']);

        self::assertSame(
            '15.01.2026 14:59',
            LocalTime::format('2026-01-15 13:59:00', 'd.m.Y H:i'),
        );
    }

    public function test_it_converts_carbon_instances_without_mutating_application_timezone(): void
    {
        config([
            'app.timezone' => 'UTC',
            'app.display_timezone' => 'Europe/Berlin',
        ]);

        $utc = CarbonImmutable::parse('2026-10-01 13:59:00', 'UTC');

        self::assertSame('15:59', LocalTime::format($utc, 'H:i'));
        self::assertSame('UTC', config('app.timezone'));
        self::assertSame('13:59', $utc->format('H:i'));
    }
}
