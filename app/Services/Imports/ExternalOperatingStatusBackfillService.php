<?php

namespace App\Services\Imports;

use App\Services\PlaceHistoryService;
use Illuminate\Support\Facades\DB;

class ExternalOperatingStatusBackfillService
{
    private const ALLOWED_STATUSES = [
        'open',
        'temporarily_closed',
        'seasonally_closed',
        'permanently_closed',
    ];

    private const PRIORITY = [
        'open' => 10,
        'seasonally_closed' => 20,
        'temporarily_closed' => 30,
        'permanently_closed' => 40,
    ];

    public function __construct(
        private readonly PlaceHistoryService $history,
    ) {
    }

    public function fillUnknownForSource(int $sourceId, array $mappedByExternalId): int
    {
        $records = DB::table('external_records')
            ->where('external_source_id', $sourceId)
            ->where('status', 'active')
            ->whereNotNull('place_id')
            ->get(['external_id', 'place_id']);

        $statusByPlace = [];

        foreach ($records as $record) {
            $mapped = $mappedByExternalId[(string) $record->external_id] ?? null;
            $status = is_array($mapped)
                ? trim((string) ($mapped['place']['opening_status'] ?? ''))
                : '';

            if (! in_array($status, self::ALLOWED_STATUSES, true)) {
                continue;
            }

            $placeId = (int) $record->place_id;
            $current = $statusByPlace[$placeId] ?? null;

            if ($current === null || self::PRIORITY[$status] > self::PRIORITY[$current]) {
                $statusByPlace[$placeId] = $status;
            }
        }

        if ($statusByPlace === []) {
            return 0;
        }

        $places = DB::table('places')
            ->whereIn('id', array_keys($statusByPlace))
            ->where('opening_status', 'unclear')
            ->get(['id', 'opening_status']);

        $updated = 0;

        foreach ($places as $place) {
            $placeId = (int) $place->id;
            $newStatus = $statusByPlace[$placeId] ?? null;

            if ($newStatus === null) {
                continue;
            }

            $affected = DB::table('places')
                ->where('id', $placeId)
                ->where('opening_status', 'unclear')
                ->update([
                    'opening_status' => $newStatus,
                    'updated_at' => now(),
                ]);

            if ($affected !== 1) {
                continue;
            }

            $this->history->addExternalSource(
                $placeId,
                $sourceId,
                'external_operating_status_filled',
                __('place_profile.history.actions.external_operating_status_filled'),
                [
                    'field' => 'opening_status',
                    'before' => 'unclear',
                    'after' => $newStatus,
                ],
            );

            $updated++;
        }

        return $updated;
    }
}
