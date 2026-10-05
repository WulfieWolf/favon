<?php

namespace App\Services\Imports;

use App\Services\PlaceTombstoneService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ExternalRecordClassificationService
{
    public function __construct(
        private readonly Datex2ParkingCandidateService $candidates,
    ) {
    }

    public function classifySource(int $sourceId, bool $requireName = false): array
    {
        $source = DB::table('external_sources')->where('id', $sourceId)->first();

        if (! $source) {
            throw new RuntimeException('External source not found.');
        }

        $runId = (int) DB::table('external_import_runs')->insertGetId([
            'external_source_id' => $sourceId,
            'mode' => 'classify',
            'status' => 'running',
            'started_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $stats = [
            'records' => 0,
            'new_candidate' => 0,
            'possible_duplicate' => 0,
            'needs_review' => 0,
            'source_only' => 0,
            'review_items' => 0,
        ];

        try {
            DB::transaction(function () use ($sourceId, $runId, $requireName, &$stats): void {
                DB::table('external_records')
                    ->where('external_source_id', $sourceId)
                    ->where('status', 'active')
                    ->whereNull('place_id')
                    ->where(function ($query) {
                        $query->whereNull('classification')
                            ->orWhere('classification', '!=', 'ignored');
                    })
                    ->orderBy('id')
                    ->chunkById(100, function ($records) use ($sourceId, $runId, $requireName, &$stats): void {
                        foreach ($records as $record) {
                            $mapped = json_decode((string) $record->normalized_data, true);

                            if (! is_array($mapped)) {
                                $this->setClassification((int) $record->id, 'needs_review');
                                $this->replaceReviewItem(
                                    $runId,
                                    $sourceId,
                                    (int) $record->id,
                                    null,
                                    'invalid_normalized_record',
                                    'error',
                                    ['reason' => 'normalized_data is invalid or missing'],
                                );
                                $stats['records']++;
                                $stats['needs_review']++;
                                $stats['review_items']++;
                                continue;
                            }

                            if ($requireName) {
                                $name = trim((string) ($mapped['place']['name'] ?? ''));

                                if ($name === '') {
                                    $this->clearClassification((int) $record->id);
                                    $this->resolveOpenReviewItems((int) $record->id, 'Record remains source-only because no public candidate name is available.');
                                    $stats['records']++;
                                    $stats['source_only']++;
                                    continue;
                                }
                            }

                            $lat = $mapped['place']['latitude'] ?? null;
                            $lon = $mapped['place']['longitude'] ?? null;

                            if (! is_numeric($lat) || ! is_numeric($lon)) {
                                $this->setClassification((int) $record->id, 'needs_review');
                                $this->replaceReviewItem(
                                    $runId,
                                    $sourceId,
                                    (int) $record->id,
                                    null,
                                    'missing_coordinates',
                                    'warning',
                                    [
                                        'external_id' => $record->external_id,
                                        'name' => $mapped['place']['name'] ?? null,
                                        'coordinate_source' => $mapped['place']['coordinate_source'] ?? null,
                                    ],
                                );
                                $stats['records']++;
                                $stats['needs_review']++;
                                $stats['review_items']++;
                                continue;
                            }

                            $typeSlug = trim((string) ($mapped['place']['suggested_place_type'] ?? ''));
                            $placeTypeId = app(PlaceTombstoneService::class)->placeTypeIdForSlug($typeSlug);
                            if ($placeTypeId) {
                                $tombstone = app(PlaceTombstoneService::class)->check(
                                    (float) $lat,
                                    (float) $lon,
                                    $placeTypeId,
                                );

                                if ($tombstone['status'] !== PlaceTombstoneService::NONE) {
                                    $match = $tombstone['match'];
                                    $this->setClassification((int) $record->id, 'needs_review');
                                    $this->replaceReviewItem(
                                        $runId,
                                        $sourceId,
                                        (int) $record->id,
                                        (int) $match['place_id'],
                                        'nearby_deleted_place',
                                        'warning',
                                        [
                                            'external_id' => $record->external_id,
                                            'external_name' => $mapped['place']['name'] ?? null,
                                            'suggested_place_type' => $typeSlug,
                                            'tombstone_status' => $tombstone['status'],
                                            'distance_m' => $match['distance_m'],
                                            'deleted_place_type' => $match['place_type_slug'],
                                            'deletion_reason' => $match['deletion_reason'],
                                            'deletion_note' => $match['deletion_note'],
                                        ],
                                    );
                                    $stats['records']++;
                                    $stats['needs_review']++;
                                    $stats['review_items']++;
                                    continue;
                                }
                            }

                            $matches = $this->candidates->candidates($mapped);

                            if ($matches !== []) {
                                $this->setClassification((int) $record->id, 'possible_duplicate');
                                $this->replaceReviewItem(
                                    $runId,
                                    $sourceId,
                                    (int) $record->id,
                                    (int) $matches[0]['place_id'],
                                    'possible_duplicate',
                                    'warning',
                                    [
                                        'external_id' => $record->external_id,
                                        'external_name' => $mapped['place']['name'] ?? null,
                                        'candidates' => $matches,
                                    ],
                                );
                                $stats['records']++;
                                $stats['possible_duplicate']++;
                                $stats['review_items']++;
                                continue;
                            }

                            $this->setClassification((int) $record->id, 'new_candidate');
                            $this->resolveOpenReviewItems((int) $record->id, 'Automatically reclassified as new_candidate.');

                            $stats['records']++;
                            $stats['new_candidate']++;
                        }
                    });

                DB::table('external_import_runs')->where('id', $runId)->update([
                    'status' => 'completed',
                    'source_record_count' => $stats['records'],
                    'mapped_record_count' => $stats['records'],
                    'review_count' => $stats['review_items'],
                    'stats' => json_encode($stats, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'completed_at' => now(),
                    'updated_at' => now(),
                ]);
            });
        } catch (\Throwable $e) {
            DB::table('external_import_runs')->where('id', $runId)->update([
                'status' => 'failed',
                'error_code' => 'classification_failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
                'updated_at' => now(),
            ]);

            throw $e;
        }

        try {
            $duplicateGroups = app(ExternalDuplicateGroupService::class)->detectForSource($sourceId, $runId);
        } catch (\Throwable $e) {
            DB::table('external_import_runs')->where('id', $runId)->update([
                'status' => 'failed',
                'error_code' => 'duplicate_group_failed',
                'error_message' => mb_substr($e->getMessage(), 0, 4000),
                'completed_at' => now(),
                'updated_at' => now(),
            ]);

            throw $e;
        }

        $stats['deleted_place_changes'] = app(ExternalReviewSignalService::class)
            ->queueDeletedPlaceChanges($sourceId, $runId);

        $stats['new_candidate'] = DB::table('external_records')
            ->where('external_source_id', $sourceId)
            ->where('status', 'active')
            ->whereNull('place_id')
            ->where('classification', 'new_candidate')
            ->count();
        $stats['possible_duplicate'] = DB::table('external_records')
            ->where('external_source_id', $sourceId)
            ->where('status', 'active')
            ->whereNull('place_id')
            ->where('classification', 'possible_duplicate')
            ->count();
        $stats['needs_review'] = DB::table('external_records')
            ->where('external_source_id', $sourceId)
            ->where('status', 'active')
            ->whereNull('place_id')
            ->where('classification', 'needs_review')
            ->count();
        $stats['review_items'] = DB::table('external_import_review_items')
            ->where('external_source_id', $sourceId)
            ->where('status', 'pending')
            ->count();
        $stats['duplicate_groups'] = $duplicateGroups;
        $stats['run_id'] = $runId;

        DB::table('external_import_runs')->where('id', $runId)->update([
            'status' => 'completed',
            'source_record_count' => $stats['records'],
            'mapped_record_count' => $stats['records'],
            'review_count' => $stats['review_items'],
            'stats' => json_encode($stats, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'updated_at' => now(),
        ]);

        return $stats;
    }

    private function clearClassification(int $recordId): void
    {
        DB::table('external_records')->where('id', $recordId)->update([
            'classification' => null,
            'classified_at' => null,
            'updated_at' => now(),
        ]);
    }

    private function setClassification(int $recordId, string $classification): void
    {
        DB::table('external_records')->where('id', $recordId)->update([
            'classification' => $classification,
            'classified_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function replaceReviewItem(
        int $runId,
        int $sourceId,
        int $recordId,
        ?int $placeId,
        string $type,
        string $severity,
        array $details,
    ): void {
        $this->resolveOpenReviewItems($recordId, 'Superseded by a newer classification.');

        DB::table('external_import_review_items')->insert([
            'external_import_run_id' => $runId,
            'external_source_id' => $sourceId,
            'external_record_id' => $recordId,
            'place_id' => $placeId,
            'type' => $type,
            'severity' => $severity,
            'status' => 'pending',
            'details' => json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function resolveOpenReviewItems(int $recordId, string $note): void
    {
        DB::table('external_import_review_items')
            ->where('external_record_id', $recordId)
            ->where('status', 'pending')
            ->update([
                'status' => 'superseded',
                'resolved_at' => now(),
                'resolution_note' => $note,
                'updated_at' => now(),
            ]);
    }
}
