<?php

namespace Tests\Unit;

use App\Services\LevelService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LevelServiceTest extends TestCase
{
    public static function thresholds(): array
    {
        return [
            [1, 0],
            [2, 6],
            [5, 42],
            [10, 154],
            [20, 582],
            [30, 1280],
            [40, 2248],
            [50, 3486],
            [60, 4921],
            [70, 7586],
            [80, 11871],
            [90, 18166],
            [100, 26861],
        ];
    }

    #[DataProvider('thresholds')]
    public function test_level_thresholds_follow_the_camperwolf_curve(int $level, int $expectedXp): void
    {
        $this->assertSame($expectedXp, app(LevelService::class)->thresholdForLevel($level));
    }

    public function test_summary_reports_progress_to_next_level(): void
    {
        $service = app(LevelService::class);
        $summary = $service->summary(42);

        $this->assertSame(5, $summary['level']);
        $this->assertSame(42, $summary['xp']);
        $this->assertSame(0, $summary['progress_xp']);
        $this->assertGreaterThan(0, $summary['needed_xp']);
        $this->assertSame(0, $summary['progress_percent']);
    }
}
