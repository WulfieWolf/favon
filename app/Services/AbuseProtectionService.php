<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AbuseProtectionService
{
    public function inspectNewPlace(Request $request, int $placeId): array
    {
        $user = $request->user();

        if (! $user || $this->isPrivileged($user)) {
            return [];
        }

        $place = DB::table('places')
            ->where('id', $placeId)
            ->first(['id', 'name', 'latitude', 'longitude', 'publication_status', 'created_at']);

        if (! $place || $place->publication_status !== 'pending') {
            return [];
        }

        $flags = [];

        if ($this->isStrongNearbyDuplicate($place)) {
            $flags[] = $this->flag(
                'place',
                $placeId,
                $user,
                'strong_nearby_duplicate',
                'high',
                [
                    'name' => $place->name,
                    'latitude' => (float) $place->latitude,
                    'longitude' => (float) $place->longitude,
                ],
                $request,
            );
        }

        $pendingLastHour = DB::table('places')
            ->where('created_by', $user->id)
            ->where('publication_status', 'pending')
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($user->created_at?->gt(now()->subDay()) && $pendingLastHour >= 3) {
            $flags[] = $this->flag(
                'place',
                $placeId,
                $user,
                'new_account_place_burst',
                'warning',
                ['pending_places_last_hour' => $pendingLastHour],
                $request,
            );
        }

        $pendingLastDay = DB::table('places')
            ->where('created_by', $user->id)
            ->where('publication_status', 'pending')
            ->where('created_at', '>=', now()->subDay())
            ->count();

        if ($pendingLastDay >= 8) {
            $flags[] = $this->flag(
                'place',
                $placeId,
                $user,
                'high_pending_place_volume',
                'warning',
                ['pending_places_last_day' => $pendingLastDay],
                $request,
            );
        }

        return array_values(array_filter($flags));
    }

    public function hasOpenFlag(string $entityType, int $entityId): bool
    {
        return DB::table('abuse_flags')
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('status', 'open')
            ->exists();
    }

    public function resolveForEntity(User $actor, string $entityType, int $entityId): void
    {
        DB::table('abuse_flags')
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('status', 'open')
            ->update([
                'status' => 'resolved',
                'resolved_by' => $actor->id,
                'resolved_at' => now(),
                'updated_at' => now(),
            ]);
    }

    private function isStrongNearbyDuplicate(object $place): bool
    {
        $latitude = (float) $place->latitude;
        $longitude = (float) $place->longitude;
        $latitudeDelta = 0.001;
        $longitudeDelta = 0.001 / max(0.2, cos(deg2rad($latitude)));
        $normalizedName = $this->normalizeName((string) $place->name);

        if ($normalizedName === '') {
            return false;
        }

        $candidates = DB::table('places')
            ->where('id', '!=', $place->id)
            ->where('is_active', true)
            ->whereIn('publication_status', ['published', 'pending'])
            ->whereBetween('latitude', [$latitude - $latitudeDelta, $latitude + $latitudeDelta])
            ->whereBetween('longitude', [$longitude - $longitudeDelta, $longitude + $longitudeDelta])
            ->limit(30)
            ->get(['name', 'latitude', 'longitude']);

        foreach ($candidates as $candidate) {
            if ($this->normalizeName((string) $candidate->name) !== $normalizedName) {
                continue;
            }

            if ($this->distanceMeters(
                $latitude,
                $longitude,
                (float) $candidate->latitude,
                (float) $candidate->longitude,
            ) <= 75) {
                return true;
            }
        }

        return false;
    }

    private function flag(
        string $entityType,
        int $entityId,
        User $user,
        string $ruleCode,
        string $severity,
        array $context,
        Request $request,
    ): ?int {
        $ip = trim((string) $request->ip());
        $ipHash = $ip !== '' ? hash('sha256', $ip.'|'.config('app.key')) : null;
        $now = now();

        DB::table('abuse_flags')->insertOrIgnore([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'user_id' => $user->id,
            'rule_code' => $ruleCode,
            'severity' => $severity,
            'status' => 'open',
            'source_ip_hash' => $ipHash,
            'context' => json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'resolved_by' => null,
            'resolved_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return DB::table('abuse_flags')
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('rule_code', $ruleCode)
            ->value('id');
    }

    private function isPrivileged(User $user): bool
    {
        return app(PermissionService::class)->can($user, 'admin.access');
    }

    private function normalizeName(string $name): string
    {
        $name = Str::lower(trim($name));
        $name = preg_replace('/[^\\pL\\pN]+/u', ' ', $name) ?? $name;

        return trim(preg_replace('/\\s+/u', ' ', $name) ?? $name);
    }

    private function distanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);
        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lonDelta / 2) ** 2;

        return 2 * $earthRadius * asin(min(1, sqrt($a)));
    }
}
