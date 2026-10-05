<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PlaceDataScoreService
{
    public const BASIS_WEIGHT = 0.75;

    public const FEATURE_WEIGHT = 0.25;

    public const BASIS_TOTAL = 6;

    /**
     * The six basis components are:
     * operator, usable postal address, phone, email, website and operating status.
     *
     * Name, coordinates and place type are mandatory place fields and intentionally
     * do not influence completeness. Prices, opening hours and free text are also
     * intentionally excluded.
     */
    public function recalculate(int $placeId): ?array
    {
        $result = $this->recalculateMany([$placeId]);

        return $result->get($placeId);
    }

    public function scoreForPlace(int $placeId): ?array
    {
        $stored = DB::table('places')
            ->where('id', $placeId)
            ->first([
                'data_score_basis_known',
                'data_score_basis_total',
                'data_score_feature_known',
                'data_score_feature_total',
                'data_score_basis',
                'data_score_features',
                'data_score',
                'data_score_dirty',
                'data_score_calculated_at',
            ]);

        if (! $stored) {
            return null;
        }

        if ($stored->data_score === null || (bool) $stored->data_score_dirty) {
            return $this->recalculate($placeId);
        }

        return $this->present((array) $stored);
    }

    public function markDirty(int $placeId): void
    {
        DB::table('places')
            ->where('id', $placeId)
            ->update(['data_score_dirty' => true]);
    }

    public function markAllDirty(): int
    {
        return DB::table('places')->update(['data_score_dirty' => true]);
    }

    public function recalculateAll(bool $dirtyOnly = false): int
    {
        $processed = 0;

        DB::table('places')
            ->when($dirtyOnly, fn ($query) => $query->where('data_score_dirty', true))
            ->orderBy('id')
            ->select('id')
            ->chunkById(500, function ($places) use (&$processed): void {
                $ids = $places->pluck('id')->map(fn ($id) => (int) $id)->all();
                $this->recalculateMany($ids);
                $processed += count($ids);
            });

        return $processed;
    }

    /**
     * Recalculates a batch from the current canonical Camperwolf data.
     *
     * Nothing is incremented/decremented from cached counters. A rebuild always
     * derives known/total values again from the current tables and feature catalog.
     */
    public function recalculateMany(array $placeIds): Collection
    {
        $placeIds = collect($placeIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($placeIds->isEmpty()) {
            return collect();
        }

        $places = DB::table('places')
            ->whereIn('id', $placeIds)
            ->get(['id', 'place_type_id', 'opening_status'])
            ->keyBy('id');

        if ($places->isEmpty()) {
            return collect();
        }

        $ids = $places->keys()->map(fn ($id) => (int) $id)->all();

        $details = DB::table('place_details')
            ->whereIn('place_id', $ids)
            ->where('is_active', true)
            ->whereNull('version_valid_until')
            ->orderByDesc('id')
            ->get(['place_id', 'operator_name'])
            ->unique('place_id')
            ->keyBy('place_id');

        $addresses = DB::table('place_addresses')
            ->whereIn('place_id', $ids)
            ->where('is_active', true)
            ->whereNull('version_valid_until')
            ->orderByDesc('id')
            ->get(['place_id', 'postal_code', 'city', 'street', 'house_number'])
            ->unique('place_id')
            ->keyBy('place_id');

        $contacts = DB::table('place_contacts')
            ->whereIn('place_id', $ids)
            ->where('is_active', true)
            ->whereNull('version_valid_until')
            ->whereIn('contact_type', ['website', 'url', 'phone', 'telephone', 'email'])
            ->get(['place_id', 'contact_type', 'value'])
            ->groupBy('place_id');

        $placeTypeIds = $places->pluck('place_type_id')->map(fn ($id) => (int) $id)->unique()->values()->all();

        $featureTotals = DB::table('feature_place_types as fpt')
            ->join('features as f', 'f.id', '=', 'fpt.feature_id')
            ->join('feature_categories as fc', 'fc.id', '=', 'f.category_id')
            ->join('feature_workflows as fw', 'fw.feature_id', '=', 'f.id')
            ->whereIn('fpt.place_type_id', $placeTypeIds)
            ->whereIn('fpt.visibility', ['standard', 'extended'])
            ->where('f.is_active', true)
            ->where('fc.is_active', true)
            ->where('fw.is_active', true)
            ->groupBy('fpt.place_type_id')
            ->selectRaw('fpt.place_type_id, COUNT(DISTINCT f.id) as feature_total')
            ->pluck('feature_total', 'place_type_id');

        $knownFeatureCounts = DB::table('place_features as pf')
            ->join('places as p', 'p.id', '=', 'pf.place_id')
            ->join('features as f', 'f.id', '=', 'pf.feature_id')
            ->join('feature_categories as fc', 'fc.id', '=', 'f.category_id')
            ->join('feature_workflows as fw', 'fw.feature_id', '=', 'f.id')
            ->join('feature_place_types as fpt', function ($join): void {
                $join->on('fpt.feature_id', '=', 'f.id')
                    ->on('fpt.place_type_id', '=', 'p.place_type_id');
            })
            ->whereIn('pf.place_id', $ids)
            ->where('pf.is_active', true)
            ->whereNull('pf.valid_until')
            ->whereNotNull('pf.status')
            ->where('pf.status', '!=', 'unknown')
            ->whereIn('fpt.visibility', ['standard', 'extended'])
            ->where('f.is_active', true)
            ->where('fc.is_active', true)
            ->where('fw.is_active', true)
            ->groupBy('pf.place_id')
            ->selectRaw('pf.place_id, COUNT(DISTINCT pf.feature_id) as feature_known')
            ->pluck('feature_known', 'place_id');

        $now = now();
        $results = collect();

        foreach ($places as $place) {
            $placeId = (int) $place->id;
            $detail = $details->get($placeId);
            $address = $addresses->get($placeId);
            $placeContacts = collect($contacts->get($placeId, []));

            $hasPhone = $placeContacts->contains(
                fn ($contact) => in_array($contact->contact_type, ['phone', 'telephone'], true) && filled($contact->value)
            );
            $hasEmail = $placeContacts->contains(
                fn ($contact) => $contact->contact_type === 'email' && filled($contact->value)
            );
            $hasWebsite = $placeContacts->contains(
                fn ($contact) => in_array($contact->contact_type, ['website', 'url'], true) && filled($contact->value)
            );

            // House numbers are deliberately optional: many rural camping places
            // have a usable postal address without a house number.
            $hasAddress = $address
                && filled($address->street)
                && filled($address->postal_code)
                && filled($address->city);

            $basisKnown = collect([
                filled($detail?->operator_name),
                $hasAddress,
                $hasPhone,
                $hasEmail,
                $hasWebsite,
                filled($place->opening_status) && $place->opening_status !== 'unclear',
            ])->filter()->count();

            $featureTotal = (int) ($featureTotals[(int) $place->place_type_id] ?? 0);
            $featureKnown = min(
                $featureTotal,
                (int) ($knownFeatureCounts[$placeId] ?? 0),
            );

            $basisScore = ($basisKnown / self::BASIS_TOTAL) * 10;
            $featureScore = $featureTotal > 0 ? ($featureKnown / $featureTotal) * 10 : 10.0;
            $dataScore = ($basisScore * self::BASIS_WEIGHT) + ($featureScore * self::FEATURE_WEIGHT);

            $values = [
                'data_score_basis_known' => $basisKnown,
                'data_score_basis_total' => self::BASIS_TOTAL,
                'data_score_feature_known' => $featureKnown,
                'data_score_feature_total' => $featureTotal,
                'data_score_basis' => $basisScore,
                'data_score_features' => $featureScore,
                'data_score' => $dataScore,
                'data_score_dirty' => false,
                'data_score_calculated_at' => $now,
            ];

            DB::table('places')->where('id', $placeId)->update($values);

            $results->put($placeId, $this->present($values));
        }

        return $results;
    }

    public function level(float $score): string
    {
        return match (true) {
            $score >= 7.0 => 'green',
            $score >= 5.0 => 'yellow',
            default => 'red',
        };
    }

    private function present(array $values): array
    {
        $score = (float) ($values['data_score'] ?? 0);
        $basis = (float) ($values['data_score_basis'] ?? 0);
        $features = (float) ($values['data_score_features'] ?? 0);

        return [
            'score' => $score,
            'basis_score' => $basis,
            'feature_score' => $features,
            'basis_known' => (int) ($values['data_score_basis_known'] ?? 0),
            'basis_total' => (int) ($values['data_score_basis_total'] ?? self::BASIS_TOTAL),
            'feature_known' => (int) ($values['data_score_feature_known'] ?? 0),
            'feature_total' => (int) ($values['data_score_feature_total'] ?? 0),
            'level' => $this->level($score),
            'basis_level' => $this->level($basis),
            'feature_level' => $this->level($features),
            'calculated_at' => $values['data_score_calculated_at'] ?? null,
        ];
    }
}
