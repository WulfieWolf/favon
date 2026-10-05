<?php

namespace App\Services\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ExternalDuplicateGroupService
{
    public const MAX_DISTANCE_METERS = 750;

    public function detectForSource(int $triggerSourceId, int $runId): array
    {
        $records = DB::table('external_records as er')
            ->join('external_sources as es', 'es.id', '=', 'er.external_source_id')
            ->where('er.status', 'active')
            ->whereNull('er.place_id')
            ->where(function ($query) {
                $query->whereNull('er.classification')
                    ->orWhere('er.classification', '!=', 'ignored');
            })
            ->orderBy('er.id')
            ->get([
                'er.id',
                'er.external_source_id',
                'er.external_id',
                'er.normalized_data',
                'es.name as source_name',
                'es.slug as source_slug',
            ]);

        $prepared = $records
            ->map(function ($record) {
                $mapped = json_decode((string) $record->normalized_data, true);
                if (! is_array($mapped)) {
                    return null;
                }

                $place = is_array($mapped['place'] ?? null) ? $mapped['place'] : [];
                $name = trim((string) ($place['name'] ?? ''));
                $type = trim((string) ($place['suggested_place_type'] ?? ''));
                $lat = $place['latitude'] ?? null;
                $lon = $place['longitude'] ?? null;

                if ($name === '' || $type === '' || ! is_numeric($lat) || ! is_numeric($lon)) {
                    return null;
                }

                return [
                    'record_id' => (int) $record->id,
                    'source_id' => (int) $record->external_source_id,
                    'source_name' => (string) $record->source_name,
                    'source_slug' => (string) $record->source_slug,
                    'external_id' => (string) $record->external_id,
                    'name' => $name,
                    'normalized_name' => $this->normalizeName($name),
                    'type' => $type,
                    'latitude' => (float) $lat,
                    'longitude' => (float) $lon,
                ];
            })
            ->filter()
            ->groupBy(fn (array $row) => $row['normalized_name'].'|'.$row['type']);

        $groups = [];

        foreach ($prepared as $bucket) {
            foreach ($this->connectedComponents($bucket->values()->all()) as $component) {
                if (count($component) < 2) {
                    continue;
                }

                if (collect($component)->pluck('source_id')->unique()->count() < 2) {
                    continue;
                }

                if (! collect($component)->contains(fn (array $member) => $member['source_id'] === $triggerSourceId)) {
                    continue;
                }

                usort($component, fn (array $a, array $b) => $a['record_id'] <=> $b['record_id']);
                $first = $component[0];
                $groupKey = hash(
                    'sha256',
                    $first['normalized_name'].'|'.$first['type'].'|'.implode(',', array_column($component, 'record_id')),
                );

                $groups[] = [
                    'group_key' => $groupKey,
                    'name' => $first['name'],
                    'place_type' => $first['type'],
                    'members' => $component,
                ];
            }
        }

        $reviewItems = 0;
        $groupedRecords = 0;
        $runIdsBySource = collect($groups)
            ->flatMap(fn (array $group) => collect($group['members'])->pluck('source_id'))
            ->unique()
            ->mapWithKeys(fn (int $sourceId) => [
                $sourceId => $this->reviewRunIdForSource($sourceId, $triggerSourceId, $runId),
            ])
            ->all();

        DB::transaction(function () use ($groups, $runIdsBySource, &$reviewItems, &$groupedRecords): void {
            foreach ($groups as $group) {
                $memberSummary = collect($group['members'])->map(fn (array $member) => [
                    'record_id' => $member['record_id'],
                    'source_id' => $member['source_id'],
                    'source_name' => $member['source_name'],
                    'source_slug' => $member['source_slug'],
                    'external_id' => $member['external_id'],
                    'latitude' => $member['latitude'],
                    'longitude' => $member['longitude'],
                ])->values()->all();

                $candidatePlaces = $this->nearbyPlacesForGroup($group['members'], $group['name']);

                foreach ($group['members'] as $member) {
                    DB::table('external_import_review_items')
                        ->where('external_record_id', $member['record_id'])
                        ->where('status', 'pending')
                        ->update([
                            'status' => 'superseded',
                            'resolved_at' => now(),
                            'resolution_note' => 'Durch externe Dubletten-Gruppe ersetzt.',
                            'updated_at' => now(),
                        ]);

                    DB::table('external_records')->where('id', $member['record_id'])->update([
                        'classification' => 'possible_duplicate',
                        'classified_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('external_import_review_items')->insert([
                        'external_import_run_id' => $runIdsBySource[$member['source_id']],
                        'external_source_id' => $member['source_id'],
                        'external_record_id' => $member['record_id'],
                        'place_id' => $candidatePlaces[0]['place_id'] ?? null,
                        'type' => 'external_duplicate_group',
                        'severity' => 'warning',
                        'status' => 'pending',
                        'details' => json_encode([
                            'group_key' => $group['group_key'],
                            'group_name' => $group['name'],
                            'group_place_type' => $group['place_type'],
                            'group_size' => count($group['members']),
                            'max_distance_m' => self::MAX_DISTANCE_METERS,
                            'members' => $memberSummary,
                            'candidates' => $candidatePlaces,
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $groupedRecords++;
                    $reviewItems++;
                }
            }
        });

        return [
            'groups' => count($groups),
            'grouped_records' => $groupedRecords,
            'review_items' => $reviewItems,
        ];
    }

    private function reviewRunIdForSource(int $sourceId, int $triggerSourceId, int $triggerRunId): int
    {
        if ($sourceId === $triggerSourceId) {
            return $triggerRunId;
        }

        $existing = DB::table('external_import_runs')
            ->where('external_source_id', $sourceId)
            ->orderByDesc('id')
            ->value('id');

        if ($existing) {
            return (int) $existing;
        }

        return (int) DB::table('external_import_runs')->insertGetId([
            'external_source_id' => $sourceId,
            'mode' => 'duplicate_group',
            'status' => 'completed',
            'started_at' => now(),
            'completed_at' => now(),
            'source_record_count' => 0,
            'mapped_record_count' => 0,
            'created_count' => 0,
            'updated_count' => 0,
            'skipped_count' => 0,
            'conflict_count' => 0,
            'review_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function connectedComponents(array $rows): array
    {
        $remaining = array_values($rows);
        $components = [];

        while ($remaining !== []) {
            $component = [array_shift($remaining)];
            $changed = true;

            while ($changed) {
                $changed = false;

                foreach ($remaining as $index => $candidate) {
                    $near = collect($component)->contains(
                        fn (array $member) => $this->distanceMeters(
                            $member['latitude'],
                            $member['longitude'],
                            $candidate['latitude'],
                            $candidate['longitude'],
                        ) <= self::MAX_DISTANCE_METERS
                    );

                    if ($near) {
                        $component[] = $candidate;
                        unset($remaining[$index]);
                        $remaining = array_values($remaining);
                        $changed = true;
                        break;
                    }
                }
            }

            $components[] = $component;
        }

        return $components;
    }

    private function nearbyPlacesForGroup(array $members, string $name): array
    {
        $candidates = collect();

        foreach ($members as $member) {
            foreach ($this->nearbyPlaces($member['latitude'], $member['longitude'], $name) as $candidate) {
                $candidates->push($candidate);
            }
        }

        return $candidates
            ->sortBy([
                ['distance_m', 'asc'],
                ['name_similarity', 'desc'],
            ])
            ->unique('place_id')
            ->take(15)
            ->values()
            ->all();
    }

    private function nearbyPlaces(float $lat, float $lon, string $name): array
    {
        $latDelta = 0.010;
        $lonDelta = 0.015;
        $normalizedName = $this->normalizeName($name);

        return DB::table('places')
            ->where('is_active', true)
            ->whereBetween('latitude', [$lat - $latDelta, $lat + $latDelta])
            ->whereBetween('longitude', [$lon - $lonDelta, $lon + $lonDelta])
            ->get(['id', 'name', 'slug', 'latitude', 'longitude'])
            ->map(function ($place) use ($lat, $lon, $normalizedName) {
                $distance = $this->distanceMeters($lat, $lon, (float) $place->latitude, (float) $place->longitude);
                similar_text($normalizedName, $this->normalizeName((string) $place->name), $similarity);

                return [
                    'place_id' => (int) $place->id,
                    'name' => (string) $place->name,
                    'slug' => (string) $place->slug,
                    'latitude' => (float) $place->latitude,
                    'longitude' => (float) $place->longitude,
                    'distance_m' => (int) round($distance),
                    'name_similarity' => round($similarity, 1),
                ];
            })
            ->filter(fn (array $candidate) => $candidate['distance_m'] <= 1000
                && ($candidate['name_similarity'] >= 70.0 || $candidate['distance_m'] <= 200))
            ->values()
            ->all();
    }

    private function normalizeName(string $name): string
    {
        $name = mb_strtolower(trim($name));
        $name = preg_replace('/[^\pL\pN]+/u', ' ', $name) ?? $name;

        return trim(preg_replace('/\s+/u', ' ', $name) ?? $name);
    }

    private function distanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earth = 6371000.0;
        $phi1 = deg2rad($lat1);
        $phi2 = deg2rad($lat2);
        $deltaPhi = deg2rad($lat2 - $lat1);
        $deltaLambda = deg2rad($lon2 - $lon1);

        $a = sin($deltaPhi / 2) ** 2
            + cos($phi1) * cos($phi2) * sin($deltaLambda / 2) ** 2;

        return $earth * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
