<?php

namespace App\Services\Imports;

use Illuminate\Support\Facades\DB;

class ExternalReviewSignalService
{
    private const CLOSED_STATUSES = [
        'temporarily_closed',
        'seasonally_closed',
        'permanently_closed',
    ];

    public function queueDeletedPlaceChanges(int $sourceId, int $runId): int
    {
        $records = DB::table('external_records as er')
            ->join('places as p', 'p.id', '=', 'er.place_id')
            ->where('er.external_source_id', $sourceId)
            ->where('er.status', 'active')
            ->whereNotNull('p.deleted_at')
            ->whereNotNull('er.normalized_hash')
            ->where(function ($query): void {
                $query->whereNull('er.tombstone_review_hash')
                    ->orWhereColumn('er.normalized_hash', '!=', 'er.tombstone_review_hash');
            })
            ->get([
                'er.id',
                'er.external_id',
                'er.place_id',
                'er.normalized_hash',
                'er.normalized_data',
                'p.place_type_id',
                'p.deleted_at',
                'p.deletion_reason',
                'p.deletion_note',
            ]);

        $queued = 0;

        foreach ($records as $record) {
            $pending = DB::table('external_import_review_items')
                ->where('external_record_id', $record->id)
                ->where('type', 'deleted_place_changed')
                ->where('status', 'pending')
                ->exists();

            if ($pending) {
                continue;
            }

            $mapped = json_decode((string) $record->normalized_data, true);
            $place = is_array($mapped['place'] ?? null) ? $mapped['place'] : [];

            DB::table('external_import_review_items')->insert([
                'external_import_run_id' => $runId,
                'external_source_id' => $sourceId,
                'external_record_id' => $record->id,
                'place_id' => $record->place_id,
                'type' => 'deleted_place_changed',
                'severity' => 'warning',
                'status' => 'pending',
                'details' => json_encode([
                    'external_id' => $record->external_id,
                    'external_name' => trim((string) ($place['name'] ?? '')) ?: null,
                    'suggested_place_type' => $place['suggested_place_type'] ?? null,
                    'deleted_place_type_id' => (int) $record->place_type_id,
                    'deleted_at' => $record->deleted_at,
                    'deletion_reason' => $record->deletion_reason,
                    'deletion_note' => $record->deletion_note,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('external_records')->where('id', $record->id)->update([
                'tombstone_review_hash' => $record->normalized_hash,
                'updated_at' => now(),
            ]);

            $queued++;
        }

        return $queued;
    }

    public function queuePossibleReopens(int $sourceId, int $runId): int
    {
        $records = DB::table('external_records as er')
            ->join('places as p', 'p.id', '=', 'er.place_id')
            ->where('er.external_source_id', $sourceId)
            ->where('er.status', 'active')
            ->whereNotNull('er.place_id')
            ->whereIn('p.opening_status', self::CLOSED_STATUSES)
            ->where('p.is_active', true)
            ->get([
                'er.id',
                'er.external_id',
                'er.place_id',
                'er.normalized_data',
                'p.name as place_name',
                'p.opening_status',
                'p.updated_at as place_updated_at',
            ]);

        $queued = 0;

        foreach ($records as $record) {
            $mapped = json_decode((string) $record->normalized_data, true);
            $sourceStatus = is_array($mapped)
                ? trim((string) ($mapped['place']['opening_status'] ?? ''))
                : '';

            // A possible re-open signal is only meaningful when the source itself
            // currently presents the linked record as operating.
            if ($sourceStatus !== 'open') {
                continue;
            }

            $pending = DB::table('external_import_review_items')
                ->where('external_record_id', $record->id)
                ->where('type', 'possible_reopen')
                ->where('status', 'pending')
                ->exists();

            if ($pending) {
                continue;
            }

            $lastReopen = DB::table('external_import_review_items')
                ->where('external_record_id', $record->id)
                ->where('type', 'possible_reopen')
                ->orderByDesc('id')
                ->first(['id', 'created_at']);

            $lastMissing = DB::table('external_import_review_items')
                ->where('external_record_id', $record->id)
                ->where('type', 'source_missing')
                ->orderByDesc('id')
                ->first(['id', 'created_at']);

            if ($lastReopen) {
                $reopenAt = strtotime((string) $lastReopen->created_at) ?: 0;
                $placeUpdatedAt = strtotime((string) $record->place_updated_at) ?: 0;
                $missingAt = $lastMissing ? (strtotime((string) $lastMissing->created_at) ?: 0) : 0;

                // Do not create the same warning on every scheduled sync. A new
                // review is justified only after the place changed again or after
                // the source record disappeared and subsequently returned.
                if ($placeUpdatedAt <= $reopenAt && $missingAt <= $reopenAt) {
                    continue;
                }
            }

            $name = is_array($mapped)
                ? trim((string) ($mapped['place']['name'] ?? ''))
                : '';

            DB::table('external_import_review_items')->insert([
                'external_import_run_id' => $runId,
                'external_source_id' => $sourceId,
                'external_record_id' => $record->id,
                'place_id' => $record->place_id,
                'type' => 'possible_reopen',
                'severity' => 'warning',
                'status' => 'pending',
                'details' => json_encode([
                    'external_id' => $record->external_id,
                    'external_name' => $name !== '' ? $name : null,
                    'camperwolf_name' => $record->place_name,
                    'current_opening_status' => $record->opening_status,
                    'source_opening_status' => 'open',
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $queued++;
        }

        return $queued;
    }
}
