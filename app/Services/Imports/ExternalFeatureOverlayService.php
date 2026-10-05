<?php

namespace App\Services\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ExternalFeatureOverlayService
{
    public function apply(int $placeId, Collection $groups): Collection
    {
        $sourceFeatures = $this->sourceFeatures($placeId);

        if ($sourceFeatures === []) {
            return $groups;
        }

        return $groups->map(function ($group) use ($sourceFeatures) {
            $group->features = $group->features->map(function ($feature) use ($sourceFeatures) {
                $entries = $sourceFeatures[$feature->feature_slug] ?? [];

                if ($entries === []) {
                    return $feature;
                }

                $internalStatus = (string) ($feature->status ?? 'unknown');
                $definite = array_values(array_filter(
                    $entries,
                    fn (array $entry) => in_array($entry['status'], ['available', 'unavailable', 'known'], true)
                        && ! ($entry['source_conflict'] ?? false),
                ));

                $feature->external_feature_sources = $definite;
                $feature->external_feature_conflicts = [];

                $effective = $definite[0] ?? null;

                if ($internalStatus === 'unknown' && $effective !== null) {
                    $feature->display_status = $effective['status'];
                    $feature->display_source = $effective;
                    if (isset($effective['value_number']) && is_numeric($effective['value_number'])) {
                        $feature->metadata = array_merge($feature->metadata ?? [], [
                            'value' => (float) $effective['value_number'],
                        ]);
                    }

                    return $feature;
                }

                if ($internalStatus === 'unknown' || $effective === null) {
                    return $feature;
                }

                if ($effective['status'] !== $internalStatus) {
                    $feature->external_feature_conflicts = [$effective];
                } elseif ($internalStatus === 'known'
                    && isset($effective['value_number'])
                    && isset(($feature->metadata ?? [])['value'])
                    && (float) $effective['value_number'] !== (float) $feature->metadata['value']) {
                    $feature->external_feature_conflicts = [$effective];
                }

                return $feature;
            })->values();

            return $group;
        });
    }

    private function sourceFeatures(int $placeId): array
    {
        $records = DB::table('external_records as er')
            ->join('external_sources as es', 'es.id', '=', 'er.external_source_id')
            ->where('er.place_id', $placeId)
            ->where('er.status', 'active')
            ->where('es.is_active', true)
            ->orderByRaw('CASE WHEN er.source_updated_at IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('er.source_updated_at')
            ->orderByDesc('er.last_seen_at')
            ->orderByDesc('er.id')
            ->get([
                'er.id',
                'er.external_id',
                'er.normalized_data',
                'er.source_updated_at',
                'er.last_seen_at',
                'es.id as source_id',
                'es.name as source_name',
                'es.slug as source_slug',
            ]);

        $byFeature = [];

        foreach ($records as $record) {
            $normalized = json_decode((string) $record->normalized_data, true);
            $features = is_array($normalized['features'] ?? null) ? $normalized['features'] : [];

            foreach ($features as $slug => $value) {
                if (! is_array($value)) {
                    continue;
                }

                $status = $value['status'] ?? 'unknown';
                if (! in_array($status, ['available', 'unavailable', 'unknown', 'known'], true)) {
                    continue;
                }

                $byFeature[$slug][] = [
                    'status' => $status,
                    'source_conflict' => (bool) ($value['conflict'] ?? false),
                    'value_number' => isset($value['value_number']) && is_numeric($value['value_number']) ? (float) $value['value_number'] : null,
                    'unit_key' => isset($value['unit_key']) ? (string) $value['unit_key'] : null,
                    'source_id' => (int) $record->source_id,
                    'source_name' => (string) $record->source_name,
                    'source_slug' => (string) $record->source_slug,
                    'external_record_id' => (int) $record->id,
                    'external_id' => (string) $record->external_id,
                    'source_updated_at' => $record->source_updated_at,
                    'last_seen_at' => $record->last_seen_at,
                ];
            }
        }

        return $byFeature;
    }
}
