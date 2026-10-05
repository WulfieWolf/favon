<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class ExternalImportRunService
{
    public function start(int $sourceId, string $mode = 'dry_run'): int
    {
        if (! in_array($mode, ['dry_run', 'apply'], true)) {
            throw new RuntimeException('Unsupported import mode.');
        }

        return (int) DB::table('external_import_runs')->insertGetId([
            'external_source_id' => $sourceId,
            'mode' => $mode,
            'status' => 'running',
            'started_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function markFetched(int $runId, int $recordCount, ?string $sha256 = null): void
    {
        DB::table('external_import_runs')->where('id', $runId)->update([
            'source_record_count' => $recordCount,
            'payload_sha256' => $sha256,
            'fetched_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function markValidated(int $runId, array $report): void
    {
        $valid = (bool) ($report['valid'] ?? false);

        DB::table('external_import_runs')->where('id', $runId)->update([
            'status' => $valid ? 'validated' : 'failed',
            'validated_at' => now(),
            'completed_at' => $valid ? null : now(),
            'error_code' => $valid ? null : 'validation_failed',
            'error_message' => $valid ? null : 'Import stopped because source validation failed.',
            'validation_report' => json_encode($report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'updated_at' => now(),
        ]);
    }

    public function complete(int $runId, array $stats = []): void
    {
        $run = DB::table('external_import_runs')->where('id', $runId)->first();

        if (! $run || ! in_array($run->status, ['validated', 'needs_review'], true)) {
            throw new RuntimeException('Only validated import runs can be completed.');
        }

        DB::table('external_import_runs')->where('id', $runId)->update([
            'status' => 'completed',
            'mapped_record_count' => (int) ($stats['mapped'] ?? 0),
            'created_count' => (int) ($stats['created'] ?? 0),
            'updated_count' => (int) ($stats['updated'] ?? 0),
            'skipped_count' => (int) ($stats['skipped'] ?? 0),
            'conflict_count' => (int) ($stats['conflicts'] ?? 0),
            'review_count' => (int) ($stats['review'] ?? 0),
            'stats' => $stats === [] ? null : json_encode($stats, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'completed_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('external_sources')->where('id', $run->external_source_id)->update([
            'last_checked_at' => now(),
            'last_success_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function fail(int $runId, string $code, string $message, array $report = []): void
    {
        DB::table('external_import_runs')->where('id', $runId)->update([
            'status' => 'failed',
            'error_code' => $code,
            'error_message' => $message,
            'validation_report' => $report === [] ? null : json_encode($report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'completed_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
