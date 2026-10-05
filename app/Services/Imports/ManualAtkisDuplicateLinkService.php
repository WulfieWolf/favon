<?php

namespace App\Services\Imports;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ManualAtkisDuplicateLinkService
{
    public const SOURCE_SLUG = 'bayern-atkis-basis-dlm';
    public const MAX_DISTANCE_METERS = 100;
    public const REQUIRED_NAME_SIMILARITY = 100.0;

    public function __construct(
        private readonly ExternalRecordReviewService $reviews,
    ) {
    }

    public function preview(): array
    {
        $items = DB::table('external_import_review_items as ri')
            ->join('external_records as er', 'er.id', '=', 'ri.external_record_id')
            ->join('external_sources as es', 'es.id', '=', 'ri.external_source_id')
            ->where('ri.status', 'pending')
            ->where('ri.type', 'external_duplicate_group')
            ->whereNull('er.place_id')
            ->orderBy('ri.id')
            ->get([
                'ri.id as review_id',
                'ri.details',
                'es.slug as source_slug',
            ]);

        $groups = $items
            ->map(function ($item) {
                $details = json_decode((string) $item->details, true) ?: [];

                return [
                    'review_id' => (int) $item->review_id,
                    'source_slug' => (string) $item->source_slug,
                    'group_key' => trim((string) ($details['group_key'] ?? '')),
                    'group_name' => trim((string) ($details['group_name'] ?? '')),
                    'members' => is_array($details['members'] ?? null) ? $details['members'] : [],
                ];
            })
            ->filter(fn (array $item) => $item['group_key'] !== '')
            ->groupBy('group_key')
            ->map(function ($members) {
                $members = $members->values();

                if ($members->count() < 2) {
                    return null;
                }

                if ($members->contains(fn (array $member) => $member['source_slug'] !== self::SOURCE_SLUG)) {
                    return null;
                }

                $groupMembers = collect($members->first()['members'] ?? [])
                    ->filter(fn ($member) => is_array($member)
                        && is_numeric($member['latitude'] ?? null)
                        && is_numeric($member['longitude'] ?? null))
                    ->values()
                    ->all();

                if (count($groupMembers) < 2) {
                    return null;
                }

                $qualifyingPlaceIds = $this->qualifyingPlaceIds(
                    (string) $members->first()['group_name'],
                    $groupMembers,
                );

                if (count($qualifyingPlaceIds) !== 1) {
                    return null;
                }

                $placeId = $qualifyingPlaceIds[0];

                return [
                    'group_key' => $members->first()['group_key'],
                    'group_name' => $members->first()['group_name'],
                    'review_ids' => $members->pluck('review_id')->map(fn ($id) => (int) $id)->all(),
                    'place_id' => $placeId,
                ];
            })
            ->filter()
            ->values()
            ->all();

        return [
            'groups' => count($groups),
            'records' => array_sum(array_map(fn (array $group) => count($group['review_ids']), $groups)),
            'matches' => $groups,
            'max_distance_m' => self::MAX_DISTANCE_METERS,
            'required_name_similarity' => self::REQUIRED_NAME_SIMILARITY,
        ];
    }

    private function qualifyingPlaceIds(string $groupName, array $members): array
    {
        $normalizedName = $this->normalizeName($groupName);
        if ($normalizedName === '') {
            return [];
        }

        $places = collect();

        foreach ($members as $member) {
            $lat = (float) $member['latitude'];
            $lon = (float) $member['longitude'];
            $latDelta = 0.002;
            $lonDelta = 0.003;

            DB::table('places')
                ->where('is_active', true)
                ->whereBetween('latitude', [$lat - $latDelta, $lat + $latDelta])
                ->whereBetween('longitude', [$lon - $lonDelta, $lon + $lonDelta])
                ->get(['id', 'name', 'latitude', 'longitude'])
                ->each(function ($place) use ($places, $normalizedName, $lat, $lon): void {
                    if ($this->normalizeName((string) $place->name) !== $normalizedName) {
                        return;
                    }

                    $distance = $this->distanceMeters(
                        $lat,
                        $lon,
                        (float) $place->latitude,
                        (float) $place->longitude,
                    );

                    if ($distance <= self::MAX_DISTANCE_METERS) {
                        $places->put((int) $place->id, true);
                    }
                });
        }

        return $places->keys()->map(fn ($id) => (int) $id)->values()->all();
    }

    private function normalizeName(string $name): string
    {
        $name = mb_strtolower(trim($name));
        $name = preg_replace('/[^\\pL\\pN]+/u', ' ', $name) ?? $name;

        return trim(preg_replace('/\\s+/u', ' ', $name) ?? $name);
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

    public function execute(User $actor): array
    {
        $preview = $this->preview();
        $linkedGroups = 0;
        $linkedRecords = 0;
        $skipped = [];

        foreach ($preview['matches'] as $group) {
            try {
                $result = $this->reviews->linkDuplicateGroup(
                    $group['review_ids'],
                    $group['place_id'],
                    $actor,
                );
                $linkedGroups++;
                $linkedRecords += (int) $result['linked'];
            } catch (RuntimeException $e) {
                $skipped[] = [
                    'group_key' => $group['group_key'],
                    'group_name' => $group['group_name'],
                    'message' => $e->getMessage(),
                ];
            }
        }

        return [
            'eligible_groups' => $preview['groups'],
            'eligible_records' => $preview['records'],
            'linked_groups' => $linkedGroups,
            'linked_records' => $linkedRecords,
            'skipped' => $skipped,
        ];
    }
}
