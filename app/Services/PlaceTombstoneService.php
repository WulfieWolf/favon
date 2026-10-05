<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class PlaceTombstoneService
{
    public const BLOCKED_SAME_TYPE = 'blocked_same_type';
    public const WARNING_SAME_TYPE = 'warning_same_type';
    public const WARNING_OTHER_TYPE = 'warning_other_type';
    public const NONE = 'none';

    public function check(float $latitude, float $longitude, int $placeTypeId): array
    {
        $warningRadius = max(1, (int) config('place_tombstones.warning_radius_m', 100));
        $blockRadius = max(1, min($warningRadius, (int) config('place_tombstones.block_radius_m', 20)));

        $latitudeDelta = $warningRadius / 111320;
        $longitudeDelta = $warningRadius / max(1000, 111320 * cos(deg2rad($latitude)));

        $candidates = DB::table('places as p')
            ->join('place_types as pt', 'pt.id', '=', 'p.place_type_id')
            ->whereNotNull('p.deleted_at')
            ->whereBetween('p.latitude', [$latitude - $latitudeDelta, $latitude + $latitudeDelta])
            ->whereBetween('p.longitude', [$longitude - $longitudeDelta, $longitude + $longitudeDelta])
            ->get([
                'p.id',
                'p.place_type_id',
                'p.latitude',
                'p.longitude',
                'p.deleted_at',
                'p.deletion_reason',
                'p.deletion_note',
                'pt.slug as place_type_slug',
            ])
            ->map(function ($place) use ($latitude, $longitude): array {
                return [
                    'place_id' => (int) $place->id,
                    'place_type_id' => (int) $place->place_type_id,
                    'place_type_slug' => (string) $place->place_type_slug,
                    'place_type_label' => $this->placeTypeLabel((int) $place->place_type_id, (string) $place->place_type_slug),
                    'distance_m' => (int) round($this->distanceMeters(
                        $latitude,
                        $longitude,
                        (float) $place->latitude,
                        (float) $place->longitude,
                    )),
                    'deleted_at' => $place->deleted_at,
                    'deletion_reason' => $place->deletion_reason,
                    'deletion_note' => $place->deletion_note,
                ];
            })
            ->filter(fn (array $item): bool => $item['distance_m'] <= $warningRadius)
            ->sortBy('distance_m')
            ->values();

        $blocked = $candidates->first(fn (array $item): bool =>
            $item['place_type_id'] === $placeTypeId && $item['distance_m'] <= $blockRadius
        );

        if ($blocked) {
            return ['status' => self::BLOCKED_SAME_TYPE, 'match' => $blocked, 'matches' => $candidates->all()];
        }

        $sameType = $candidates->first(fn (array $item): bool => $item['place_type_id'] === $placeTypeId);
        if ($sameType) {
            return ['status' => self::WARNING_SAME_TYPE, 'match' => $sameType, 'matches' => $candidates->all()];
        }

        $otherType = $candidates->first();
        if ($otherType) {
            return ['status' => self::WARNING_OTHER_TYPE, 'match' => $otherType, 'matches' => $candidates->all()];
        }

        return ['status' => self::NONE, 'match' => null, 'matches' => []];
    }

    public function placeTypeIdForSlug(?string $slug): ?int
    {
        $slug = trim((string) $slug);
        if ($slug === '') {
            return null;
        }

        $id = DB::table('place_types')->where('slug', $slug)->where('is_active', true)->value('id');

        return $id ? (int) $id : null;
    }

    private function placeTypeLabel(int $placeTypeId, string $fallback): string
    {
        $locale = app()->getLocale();
        $fallbackLocale = (string) config('app.fallback_locale', 'de');

        $value = DB::table('translations')
            ->whereIn('entity_type', ['place_type', 'place_types'])
            ->where('entity_id', $placeTypeId)
            ->where('field', 'name')
            ->where('is_active', true)
            ->whereIn('locale', array_values(array_unique([$locale, $fallbackLocale])))
            ->orderByRaw('CASE WHEN locale = ? THEN 0 ELSE 1 END', [$locale])
            ->value('value');

        return trim((string) $value) !== '' ? (string) $value : $fallback;
    }

    private function distanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000.0;
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);
        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lonDelta / 2) ** 2;

        return 2 * $earthRadius * asin(min(1, sqrt($a)));
    }
}
