<?php

namespace App\Services;

class LevelService
{
    public function thresholdForLevel(int $level): int
    {
        $level = max(1, $level);

        if ($level === 1) {
            return 0;
        }

        if ($level <= 50) {
            $n = $level - 1;

            return (int) round((5 * $n) + (1.35 * ($n ** 2)));
        }

        $xpAt50 = $this->thresholdForLevel(50);
        $d = $level - 50;

        return (int) round($xpAt50 + (95 * $d) + (4.2 * ($d ** 2)) + (0.065 * ($d ** 3)));
    }

    public function levelForXp(int $xp): int
    {
        $xp = max(0, $xp);
        $level = 1;

        while ($this->thresholdForLevel($level + 1) <= $xp && $level < 1000) {
            $level++;
        }

        return $level;
    }

    public function summary(int $xp): array
    {
        $xp = max(0, $xp);
        $level = $this->levelForXp($xp);
        $currentThreshold = $this->thresholdForLevel($level);
        $nextThreshold = $this->thresholdForLevel($level + 1);
        $progressXp = max(0, $xp - $currentThreshold);
        $neededXp = max(1, $nextThreshold - $currentThreshold);

        return [
            'xp' => $xp,
            'level' => $level,
            'current_threshold' => $currentThreshold,
            'next_threshold' => $nextThreshold,
            'progress_xp' => $progressXp,
            'needed_xp' => $neededXp,
            'progress_percent' => min(100, (int) floor(($progressXp / $neededXp) * 100)),
        ];
    }
}
