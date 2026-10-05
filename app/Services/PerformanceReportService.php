<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PerformanceReportService
{
    public function report(int $minutes = 60, int $limit = 10, bool $full = false): array
    {
        $minutes = max(1, min($minutes, 60 * 24 * 30));
        $limit = max(1, min($limit, 100));
        $since = now()->subMinutes($minutes)->timestamp;

        return [
            'minutes' => $minutes,
            'generated_at' => now(),
            'slow_requests' => $this->entrySummary('slow_request', $since, $limit, $full),
            'slow_queries' => $this->entrySummary('slow_query', $since, $limit, $full),
            'exceptions' => $this->entrySummary('exception', $since, $limit, $full, includeDuration: false),
            'servers' => $this->serverSummary($since),
            'entry_types' => $this->entryTypes($since),
        ];
    }

    private function entrySummary(
        string $type,
        int $since,
        int $limit,
        bool $full,
        bool $includeDuration = true,
    ): Collection {
        $query = DB::table('pulse_entries')
            ->where('type', $type)
            ->where('timestamp', '>=', $since)
            ->select('key')
            ->selectRaw('COUNT(*) as occurrences, MAX(timestamp) as latest_timestamp');

        if ($includeDuration) {
            $query->selectRaw('ROUND(AVG(value), 2) as avg_value, MAX(value) as max_value');
        }

        return $query
            ->groupBy('key')
            ->orderByDesc($includeDuration ? 'max_value' : 'occurrences')
            ->limit($limit)
            ->get()
            ->map(function ($row) use ($full, $includeDuration): array {
                return [
                    'key' => $this->displayKey((string) $row->key, $full),
                    'occurrences' => (int) $row->occurrences,
                    'avg_ms' => $includeDuration ? (float) $row->avg_value : null,
                    'max_ms' => $includeDuration ? (int) $row->max_value : null,
                    'latest' => now()->setTimestamp((int) $row->latest_timestamp),
                ];
            });
    }

    private function serverSummary(int $since): Collection
    {
        return DB::table('pulse_aggregates')
            ->whereIn('type', ['cpu', 'memory'])
            ->where('aggregate', 'avg')
            ->where('bucket', '>=', $since)
            ->select(['type', 'key'])
            ->selectRaw('ROUND(AVG(value), 2) as avg_value, ROUND(MAX(value), 2) as max_value, COUNT(*) as samples')
            ->groupBy('type', 'key')
            ->orderBy('type')
            ->orderBy('key')
            ->get()
            ->map(fn ($row): array => [
                'type' => (string) $row->type,
                'key' => (string) $row->key,
                'avg' => (float) $row->avg_value,
                'max' => (float) $row->max_value,
                'samples' => (int) $row->samples,
            ]);
    }

    private function entryTypes(int $since): Collection
    {
        return DB::table('pulse_entries')
            ->where('timestamp', '>=', $since)
            ->select('type')
            ->selectRaw('COUNT(*) as entries')
            ->groupBy('type')
            ->orderByDesc('entries')
            ->get()
            ->map(fn ($row): array => [
                'type' => (string) $row->type,
                'entries' => (int) $row->entries,
            ]);
    }

    private function displayKey(string $key, bool $full): string
    {
        $decoded = json_decode($key, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $parts = [];
            foreach ($decoded as $name => $value) {
                if (is_scalar($value) || $value === null) {
                    $parts[] = $name.'='.($value === null ? 'null' : (string) $value);
                } else {
                    $parts[] = $name.'='.json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            }
            $key = implode(' | ', $parts);
        }

        if ($full || mb_strlen($key) <= 220) {
            return $key;
        }

        return mb_substr($key, 0, 217).'...';
    }
}
