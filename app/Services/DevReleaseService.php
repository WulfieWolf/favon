<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DevReleaseService
{
    public function current(): ?object
    {
        if (! Schema::hasTable('dev_releases')) {
            return null;
        }

        return DB::table('dev_releases')
            ->where('is_public', true)
            ->orderByDesc('milestone')
            ->orderByDesc('build')
            ->first();
    }

    public function label(?object $release = null): string
    {
        $release ??= $this->current();

        if (! $release) {
            return 'Pre-Alpha';
        }

        if ($release->stage === 'beta' && (int) $release->milestone === 1 && (int) $release->build === 0) {
            return 'v1 Beta';
        }

        if ($release->stage === 'stable') {
            return sprintf('v%d.%d', $release->milestone, $release->build);
        }

        return sprintf('%s 0.%02d.%03d', $this->stageLabel($release->stage), $release->milestone, $release->build);
    }

    private function stageLabel(string $stage): string
    {
        return match ($stage) {
            'alpha' => 'Alpha',
            'beta' => 'Beta',
            'rc' => 'RC',
            'stable' => 'Stable',
            default => 'Pre-Alpha',
        };
    }
}
