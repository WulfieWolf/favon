<?php

namespace App\Services\Imports;

use App\Services\PerformanceDataService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ImportTestResetService
{
    public function __construct(
        private PerformanceDataService $performance,
    ) {
    }

    public function reset(): array
    {
        $this->guardEnvironment();

        $performance = $this->performance->hasData()
            ? $this->performance->clear()
            : ['places' => 0, 'users' => 0, 'photos' => 0];

        $externalRecordIds = DB::table('external_records')->pluck('id');
        $importedPlaceIds = DB::table('external_records')
            ->where('classification', 'created')
            ->whereNotNull('place_id')
            ->pluck('place_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $snapshotRows = DB::table('external_raw_snapshots')
            ->get(['id', 'storage_path']);

        $counts = [
            'performance_places' => (int) ($performance['places'] ?? 0),
            'performance_users' => (int) ($performance['users'] ?? 0),
            'performance_photos' => (int) ($performance['photos'] ?? 0),
            'imported_places' => $importedPlaceIds->count(),
            'external_records' => $externalRecordIds->count(),
            'import_runs' => DB::table('external_import_runs')->count(),
            'review_items' => DB::table('external_import_review_items')->count(),
            'raw_snapshots' => $snapshotRows->count(),
        ];

        DB::transaction(function () use ($externalRecordIds, $importedPlaceIds): void {
            if ($importedPlaceIds->isNotEmpty()) {
                DB::table('audit_logs')
                    ->where('entity_type', 'place')
                    ->whereIn('entity_id', $importedPlaceIds)
                    ->delete();

                DB::table('places')
                    ->whereIn('id', $importedPlaceIds)
                    ->delete();
            }

            if ($externalRecordIds->isNotEmpty()) {
                DB::table('audit_logs')
                    ->where('entity_type', 'external_record')
                    ->whereIn('entity_id', $externalRecordIds)
                    ->delete();
            }

            // Snapshots are deleted explicitly before runs so no orphaned
            // metadata remains after the reset.
            DB::table('external_raw_snapshots')->delete();

            // Review items cascade with their runs.
            DB::table('external_import_runs')->delete();

            // Record fields cascade with their external record.
            DB::table('external_records')->delete();

            DB::table('external_sources')->update([
                'last_checked_at' => null,
                'last_success_at' => null,
                'updated_at' => now(),
            ]);
        });

        foreach ($snapshotRows as $snapshot) {
            $path = trim((string) $snapshot->storage_path);
            if ($path !== '') {
                Storage::disk('local')->delete($path);
            }
        }

        return $counts;
    }

    public function resetSource(string $sourceSlug): array
    {
        $this->guardEnvironment();

        $source = DB::table('external_sources')
            ->where('slug', $sourceSlug)
            ->first(['id', 'slug', 'name']);

        if (! $source) {
            throw new RuntimeException('Die angegebene externe Quelle existiert nicht.');
        }

        $sourceId = (int) $source->id;
        $recordIds = DB::table('external_records')
            ->where('external_source_id', $sourceId)
            ->pluck('id');

        $createdPlaceIds = DB::table('external_records')
            ->where('external_source_id', $sourceId)
            ->where('classification', 'created')
            ->whereNotNull('place_id')
            ->pluck('place_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $deletablePlaceIds = $createdPlaceIds
            ->filter(fn (int $placeId) => ! DB::table('external_records')
                ->where('place_id', $placeId)
                ->where('external_source_id', '!=', $sourceId)
                ->exists())
            ->values();

        $snapshots = DB::table('external_raw_snapshots')
            ->where('external_source_id', $sourceId)
            ->get(['storage_path']);

        $counts = [
            'source' => (string) $source->slug,
            'records' => $recordIds->count(),
            'runs' => DB::table('external_import_runs')->where('external_source_id', $sourceId)->count(),
            'review_items' => DB::table('external_import_review_items')->where('external_source_id', $sourceId)->count(),
            'created_places' => $createdPlaceIds->count(),
            'deleted_places' => $deletablePlaceIds->count(),
            'preserved_shared_places' => $createdPlaceIds->count() - $deletablePlaceIds->count(),
            'snapshots' => $snapshots->count(),
        ];

        DB::transaction(function () use ($sourceId, $recordIds, $deletablePlaceIds): void {
            if ($recordIds->isNotEmpty()) {
                DB::table('audit_logs')
                    ->where('entity_type', 'external_record')
                    ->whereIn('entity_id', $recordIds)
                    ->delete();
            }

            if ($deletablePlaceIds->isNotEmpty()) {
                DB::table('audit_logs')
                    ->where('entity_type', 'place')
                    ->whereIn('entity_id', $deletablePlaceIds)
                    ->delete();
            }

            DB::table('external_sources')->where('id', $sourceId)->delete();

            if ($deletablePlaceIds->isNotEmpty()) {
                DB::table('places')->whereIn('id', $deletablePlaceIds)->delete();
            }
        });

        foreach ($snapshots as $snapshot) {
            $path = trim((string) $snapshot->storage_path);
            if ($path !== '') {
                Storage::disk('local')->delete($path);
            }
        }

        return $counts;
    }

    private function guardEnvironment(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Der Import-Testreset ist ausschließlich in local/testing erlaubt.');
        }
    }
}
