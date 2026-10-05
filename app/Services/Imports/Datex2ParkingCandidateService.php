<?php

namespace App\Services\Imports;

use Illuminate\Support\Facades\DB;

class Datex2ParkingCandidateService
{
    public function candidates(array $mapped, int $limit = 5): array
    {
        $lat = $mapped['place']['latitude'] ?? null;
        $lon = $mapped['place']['longitude'] ?? null;

        if (! is_numeric($lat) || ! is_numeric($lon)) {
            return [];
        }

        $lat = (float) $lat;
        $lon = (float) $lon;

        // Rough bounding box around 1 km. Exact distance is calculated below.
        $latDelta = 0.010;
        $lonDelta = 0.015;

        $places = DB::table('places')
            ->whereBetween('latitude', [$lat - $latDelta, $lat + $latDelta])
            ->whereBetween('longitude', [$lon - $lonDelta, $lon + $lonDelta])
            ->where('is_active', true)
            ->get(['id', 'name', 'slug', 'latitude', 'longitude']);

        $name = $this->normalizeName((string) ($mapped['place']['name'] ?? ''));

        return $places
            ->map(function ($place) use ($lat, $lon, $name) {
                $distance = $this->distanceMeters(
                    $lat,
                    $lon,
                    (float) $place->latitude,
                    (float) $place->longitude,
                );

                $candidateName = $this->normalizeName((string) $place->name);
                similar_text($name, $candidateName, $similarity);

                return [
                    'place_id' => (int) $place->id,
                    'name' => $place->name,
                    'slug' => $place->slug,
                    'distance_m' => (int) round($distance),
                    'name_similarity' => round($similarity, 1),
                ];
            })
            ->filter(fn (array $candidate) => $this->shouldReviewCandidate($candidate))
            ->sortBy([
                ['distance_m', 'asc'],
                ['name_similarity', 'desc'],
            ])
            ->take($limit)
            ->values()
            ->all();
    }

    private function shouldReviewCandidate(array $candidate): bool
    {
        $distance = (int) ($candidate['distance_m'] ?? PHP_INT_MAX);
        $similarity = (float) ($candidate['name_similarity'] ?? 0.0);

        if ($distance > 1000) {
            return false;
        }

        return $distance <= 200
            || $similarity >= 70.0
            || ($distance <= 500 && $similarity >= 50.0);
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
