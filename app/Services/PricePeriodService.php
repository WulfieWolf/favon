<?php

namespace App\Services;

use App\Support\LocaleConfiguration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class PricePeriodService
{
    private const DAYS_IN_YEAR = 366;

    public function currentOffers(int $placeId, ?string $locale = null): Collection
    {
        $locale ??= app()->getLocale();

        $offers = DB::table('place_price_offers')
            ->where('place_id', $placeId)
            ->where('is_active', true)
            ->whereNull('version_valid_until')
            ->orderBy('id')
            ->get();

        if ($offers->isEmpty()) {
            return collect();
        }

        $productLabels = $this->translationLabels('price_product', $offers->pluck('price_product_id'), $locale);
        $variantLabels = $this->translationLabels('price_product_variant', $offers->pluck('price_product_variant_id'), $locale);
        $featureLabels = $this->translationLabels('feature', $offers->pluck('feature_id'), $locale);

        $productSlugs = DB::table('price_products')
            ->whereIn('id', $offers->pluck('price_product_id')->filter())
            ->pluck('slug', 'id');

        $variantSlugs = DB::table('price_product_variants')
            ->whereIn('id', $offers->pluck('price_product_variant_id')->filter())
            ->pluck('slug', 'id');

        $periods = DB::table('place_price_periods')
            ->whereIn('place_price_offer_id', $offers->pluck('id'))
            ->where('is_active', true)
            ->whereNull('version_valid_until')
            ->orderByDesc('is_year_round')
            ->orderBy('start_month')
            ->orderBy('start_day')
            ->orderBy('id')
            ->get();

        $lines = $periods->isEmpty()
            ? collect()
            : DB::table('place_price_lines as ppl')
                ->leftJoin('units as cu', 'cu.id', '=', 'ppl.currency_unit_id')
                ->leftJoin('price_billing_units as pbu', 'pbu.id', '=', 'ppl.price_billing_unit_id')
                ->whereIn('ppl.place_price_period_id', $periods->pluck('id'))
                ->where('ppl.is_active', true)
                ->whereNull('ppl.version_valid_until')
                ->orderBy('ppl.sort_order')
                ->orderBy('ppl.id')
                ->get([
                    'ppl.*',
                    'cu.symbol as currency_symbol',
                    'pbu.slug as billing_unit_slug',
                ]);

        $billingLabels = $this->translationLabels(
            'price_billing_unit',
            $lines->pluck('price_billing_unit_id'),
            $locale,
        );

        $lines = $lines->map(function ($line) use ($billingLabels) {
            $line->billing_unit_label = $line->price_billing_unit_id
                ? ($billingLabels[(int) $line->price_billing_unit_id] ?? $line->billing_unit_slug)
                : null;

            return $line;
        })->groupBy('place_price_period_id');

        $periods = $periods->map(function ($period) use ($lines) {
            $period->label = $this->periodLabel($period);
            $period->lines = $lines->get($period->id, collect())->values();

            return $period;
        })->groupBy('place_price_offer_id');

        return $offers->map(function ($offer) use (
            $productLabels,
            $variantLabels,
            $featureLabels,
            $productSlugs,
            $variantSlugs,
            $periods,
        ) {
            $offer->product_label = $offer->price_product_id
                ? ($productLabels[(int) $offer->price_product_id] ?? null)
                : null;
            $offer->product_slug = $offer->price_product_id
                ? ($productSlugs[(int) $offer->price_product_id] ?? null)
                : null;
            $offer->variant_label = $offer->price_product_variant_id
                ? ($variantLabels[(int) $offer->price_product_variant_id] ?? null)
                : null;
            $offer->variant_slug = $offer->price_product_variant_id
                ? ($variantSlugs[(int) $offer->price_product_variant_id] ?? null)
                : null;
            $offer->feature_label = $offer->feature_id
                ? ($featureLabels[(int) $offer->feature_id] ?? null)
                : null;
            $offer->periods = $periods->get($offer->id, collect())->values();
            $offer->label = $this->offerLabel($offer);
            $offer->descriptor = $this->offerDescriptor($offer);

            return $offer;
        });
    }

    public function offerForEdit(int $placeId, ?int $offerId): ?object
    {
        if (! $offerId) {
            return null;
        }

        return $this->currentOffers($placeId)->firstWhere('id', $offerId);
    }

    public function periodForEdit(?object $offer, ?int $periodId): ?object
    {
        if (! $offer || ! $periodId) {
            return null;
        }

        return $offer->periods->firstWhere('id', $periodId);
    }

    public function products(?string $locale = null): Collection
    {
        $locale ??= app()->getLocale();
        $fallback = LocaleConfiguration::fallback();

        return DB::table('price_products as pp')
            ->leftJoin('translations as tr', function ($join) use ($locale) {
                $join->on('tr.entity_id', '=', 'pp.id')
                    ->where('tr.entity_type', 'price_product')
                    ->where('tr.field', 'name')
                    ->where('tr.locale', $locale)
                    ->where('tr.is_active', true);
            })
            ->leftJoin('translations as en', function ($join) use ($fallback) {
                $join->on('en.entity_id', '=', 'pp.id')
                    ->where('en.entity_type', 'price_product')
                    ->where('en.field', 'name')
                    ->where('en.locale', $fallback)
                    ->where('en.is_active', true);
            })
            ->where('pp.is_active', true)
            ->orderBy('pp.sort_order')
            ->get([
                'pp.id',
                'pp.slug',
                'pp.supports_vehicle_length',
                'pp.supports_age_range',
                DB::raw('COALESCE(tr.value, en.value, pp.slug) as label'),
            ]);
    }

    public function variants(?string $locale = null): Collection
    {
        $locale ??= app()->getLocale();
        $fallback = LocaleConfiguration::fallback();

        return DB::table('price_product_variants as ppv')
            ->leftJoin('translations as tr', function ($join) use ($locale) {
                $join->on('tr.entity_id', '=', 'ppv.id')
                    ->where('tr.entity_type', 'price_product_variant')
                    ->where('tr.field', 'name')
                    ->where('tr.locale', $locale)
                    ->where('tr.is_active', true);
            })
            ->leftJoin('translations as en', function ($join) use ($fallback) {
                $join->on('en.entity_id', '=', 'ppv.id')
                    ->where('en.entity_type', 'price_product_variant')
                    ->where('en.field', 'name')
                    ->where('en.locale', $fallback)
                    ->where('en.is_active', true);
            })
            ->where('ppv.is_active', true)
            ->orderBy('ppv.price_product_id')
            ->orderBy('ppv.sort_order')
            ->get([
                'ppv.id',
                'ppv.price_product_id',
                'ppv.slug',
                DB::raw('COALESCE(tr.value, en.value, ppv.slug) as label'),
            ]);
    }

    public function billingUnits(?string $locale = null): Collection
    {
        $locale ??= app()->getLocale();
        $fallback = LocaleConfiguration::fallback();

        return DB::table('price_billing_units as pbu')
            ->leftJoin('translations as tr', function ($join) use ($locale) {
                $join->on('tr.entity_id', '=', 'pbu.id')
                    ->where('tr.entity_type', 'price_billing_unit')
                    ->where('tr.field', 'name')
                    ->where('tr.locale', $locale)
                    ->where('tr.is_active', true);
            })
            ->leftJoin('translations as en', function ($join) use ($fallback) {
                $join->on('en.entity_id', '=', 'pbu.id')
                    ->where('en.entity_type', 'price_billing_unit')
                    ->where('en.field', 'name')
                    ->where('en.locale', $fallback)
                    ->where('en.is_active', true);
            })
            ->where('pbu.is_active', true)
            ->orderBy('pbu.sort_order')
            ->get([
                'pbu.id',
                'pbu.slug',
                DB::raw('COALESCE(tr.value, en.value, pbu.slug) as label'),
            ]);
    }

    public function priceableFeatures(?string $locale = null): Collection
    {
        $locale ??= app()->getLocale();
        $fallback = LocaleConfiguration::fallback();

        return DB::table('features as f')
            ->join('feature_categories as fc', 'fc.id', '=', 'f.category_id')
            ->leftJoin('translations as tr', function ($join) use ($locale) {
                $join->on('tr.entity_id', '=', 'f.id')
                    ->where('tr.entity_type', 'feature')
                    ->where('tr.field', 'name')
                    ->where('tr.locale', $locale)
                    ->where('tr.is_active', true);
            })
            ->leftJoin('translations as en', function ($join) use ($fallback) {
                $join->on('en.entity_id', '=', 'f.id')
                    ->where('en.entity_type', 'feature')
                    ->where('en.field', 'name')
                    ->where('en.locale', $fallback)
                    ->where('en.is_active', true);
            })
            ->where('f.is_active', true)
            ->whereIn('fc.slug', ['utilities', 'sanitary', 'facilities', 'services', 'rental', 'fuel-rest-area'])
            ->orderBy('fc.sort_order')
            ->orderBy('f.sort_order')
            ->get([
                'f.id',
                'f.slug',
                'fc.slug as category_slug',
                DB::raw('COALESCE(tr.value, en.value, f.slug) as label'),
            ]);
    }

    public function normalizeRange(bool $yearRound, ?string $start, ?string $end): array
    {
        if ($yearRound) {
            return [
                'is_year_round' => true,
                'start_month' => null,
                'start_day' => null,
                'end_month' => null,
                'end_day' => null,
            ];
        }

        [$startDay, $startMonth] = $this->parseDayMonth($start);
        [$endDay, $endMonth] = $this->parseDayMonth($end);

        return [
            'is_year_round' => false,
            'start_month' => $startMonth,
            'start_day' => $startDay,
            'end_month' => $endMonth,
            'end_day' => $endDay,
        ];
    }

    public function rangeLabel(array $range): string
    {
        if ($range['is_year_round']) {
            return __('place_profile.year_round');
        }

        return sprintf(
            '%02d.%02d.–%02d.%02d.',
            $range['start_day'],
            $range['start_month'],
            $range['end_day'],
            $range['end_month'],
        );
    }

    public function overlapPreview(?object $offer, array $range, ?int $editingPeriodId = null): array
    {
        if (! $offer) {
            return [];
        }

        $incomingDays = $this->daysForRange($range);
        $affected = [];

        foreach ($offer->periods as $period) {
            $existingRange = $this->rangeFromPeriod($period);
            $existingDays = $this->daysForRange($existingRange);
            $isEditingPeriod = $editingPeriodId && (int) $period->id === $editingPeriodId;

            if (! $isEditingPeriod && count(array_intersect($incomingDays, $existingDays)) === 0) {
                continue;
            }

            $affected[] = [
                'id' => (int) $period->id,
                'label' => $period->label,
                'editing' => $isEditingPeriod,
                'remainders' => array_map(
                    fn ($remainder) => $this->rangeLabel($remainder),
                    $this->subtractRange($existingRange, $range),
                ),
                'signature' => (string) ($period->updated_at ?? $period->version_valid_from ?? ''),
            ];
        }

        return $affected;
    }

    public function linesFromPeriod(?object $period): array
    {
        if (! $period) {
            return [$this->blankLine()];
        }

        $lines = $period->lines->map(fn ($line) => [
            'price_status' => $line->price_status,
            'amount' => $line->amount,
            'currency_unit_id' => $line->currency_unit_id,
            'rate_quantity' => $line->rate_quantity,
            'price_billing_unit_id' => $line->price_billing_unit_id,
        ])->values()->all();

        return $lines === [] ? [$this->blankLine()] : $lines;
    }

    public function blankLine(): array
    {
        return [
            'price_status' => 'fixed',
            'amount' => null,
            'currency_unit_id' => null,
            'rate_quantity' => 1,
            'price_billing_unit_id' => null,
        ];
    }

    public function applyDirect(
        int $placeId,
        int $userId,
        array $offerData,
        array $range,
        array $lines,
        ?int $editingOfferId = null,
        ?int $editingPeriodId = null,
    ): array {
        return DB::transaction(function () use (
            $placeId,
            $userId,
            $offerData,
            $range,
            $lines,
            $editingOfferId,
            $editingPeriodId,
        ): array {
            $offer = $editingOfferId ? $this->offerForEdit($placeId, $editingOfferId) : null;

            if ($editingOfferId && ! $offer) {
                throw new RuntimeException(__('place_editing.price.errors.offer_missing'));
            }

            $offerId = $offer
                ? $this->updateOffer($offer, $offerData, $userId)
                : $this->createOffer($placeId, $offerData, $userId);

            $offer = $this->offerForEdit($placeId, $offerId);
            $affected = collect($this->overlapPreview($offer, $range, $editingPeriodId))
                ->map(fn ($item) => $offer->periods->firstWhere('id', $item['id']))
                ->filter()
                ->values();

            $result = $this->applyReplacement(
                $offerId,
                $userId,
                $range,
                $lines,
                $affected,
                'direct_price_edit',
            );

            DB::table('audit_logs')->insert([
                'user_id' => $userId,
                'entity_type' => 'place',
                'entity_id' => $placeId,
                'action' => 'price_period_replaced_directly',
                'source' => 'admin',
                'old_values' => json_encode([
                    'offer_id' => $offerId,
                    'affected_period_ids' => $affected->pluck('id')->map(fn ($id) => (int) $id)->all(),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'new_values' => json_encode([
                    'offer_id' => $offerId,
                    'created_period_ids' => $result['created_period_ids'],
                    'range' => $range,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'internal_comment' => null,
                'created_at' => now(),
            ]);

            return ['offer_id' => $offerId] + $result;
        });
    }

    public function createProposal(
        int $placeId,
        int $userId,
        array $offerData,
        array $range,
        array $lines,
        ?string $comment = null,
        ?int $editingOfferId = null,
        ?int $editingPeriodId = null,
    ): int {
        $definition = DB::table('suggestable_fields')
            ->where('target_table', 'place_price_offers')
            ->where('target_field', 'period_pricing')
            ->where('is_suggestable', true)
            ->where('is_active', true)
            ->first();

        if (! $definition || ! $definition->allow_create) {
            throw new RuntimeException(__('place_editing.price.errors.workflow_unavailable'));
        }

        $offer = $editingOfferId ? $this->offerForEdit($placeId, $editingOfferId) : null;
        if ($editingOfferId && ! $offer) {
            throw new RuntimeException(__('place_editing.price.errors.offer_missing'));
        }

        $affected = $this->overlapPreview($offer, $range, $editingPeriodId);

        $payload = [
            'offer' => $offerData,
            'offer_preview' => $this->offerPreview($offerData),
            'range' => $range,
            'lines' => $lines,
            'editing_offer_id' => $editingOfferId,
            'editing_period_id' => $editingPeriodId,
            'offer_signature' => $offer ? (string) ($offer->updated_at ?? '') : null,
            'affected_periods' => array_map(fn ($item) => [
                'id' => $item['id'],
                'signature' => $item['signature'],
            ], $affected),
        ];

        $now = now();

        return (int) DB::table('change_requests')->insertGetId([
            'group_uuid' => (string) Str::uuid(),
            'place_id' => $placeId,
            'suggestable_field_id' => $definition->id,
            'target_record_id' => null,
            'operation' => 'create',
            'original_value' => json_encode([
                'offer_id' => $editingOfferId,
                'offer_label' => $offer?->label,
                'affected_periods' => $affected,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'proposed_value' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'status' => 'pending',
            'submitted_by' => $userId,
            'submitted_at' => $now,
            'user_comment' => $comment ?: 'Preisangabe aus dem Platzprofil vorgeschlagen.',
            'reviewed_by' => null,
            'reviewed_at' => null,
            'moderator_comment' => null,
            'result_record_id' => null,
            'applied_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function applyProposal(object $request, int $reviewerId, $now): int
    {
        $payload = json_decode((string) $request->proposed_value, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($payload)
            || ! isset($payload['offer'], $payload['range'], $payload['lines'])) {
            throw new RuntimeException(__('place_editing.price.errors.invalid_proposal'));
        }

        $editingOfferId = isset($payload['editing_offer_id']) ? (int) $payload['editing_offer_id'] : null;
        $editingPeriodId = isset($payload['editing_period_id']) ? (int) $payload['editing_period_id'] : null;
        $offer = $editingOfferId ? $this->offerForEdit((int) $request->place_id, $editingOfferId) : null;

        if ($editingOfferId) {
            if (! $offer) {
                throw new RuntimeException(__('place_editing.price.errors.offer_changed'));
            }

            if ((string) ($payload['offer_signature'] ?? '') !== (string) ($offer->updated_at ?? '')) {
                throw new RuntimeException(__('place_editing.price.errors.offer_changed'));
            }
        }

        $expectedAffected = collect($payload['affected_periods'] ?? [])->keyBy('id');
        $currentAffected = collect($this->overlapPreview($offer, $payload['range'], $editingPeriodId));

        $currentIds = $currentAffected->pluck('id')->map(fn ($id) => (int) $id)->sort()->values();
        $expectedIds = $expectedAffected->keys()->map(fn ($id) => (int) $id)->sort()->values();

        if ($currentIds->all() !== $expectedIds->all()) {
            throw new RuntimeException(__('place_editing.price.errors.overlaps_changed'));
        }

        foreach ($currentAffected as $item) {
            $expected = $expectedAffected->get((int) $item['id']);
            if (! $expected || (string) ($expected['signature'] ?? '') !== (string) $item['signature']) {
                throw new RuntimeException(__('place_editing.price.errors.period_changed'));
            }
        }

        $offerId = $offer
            ? $this->updateOffer($offer, $payload['offer'], $reviewerId, $now)
            : $this->createOffer((int) $request->place_id, $payload['offer'], $reviewerId, $now);

        $offer = $this->offerForEdit((int) $request->place_id, $offerId);
        $affectedPeriods = $currentAffected
            ->map(fn ($item) => $offer->periods->firstWhere('id', $item['id']))
            ->filter()
            ->values();

        $this->applyReplacement(
            $offerId,
            $reviewerId,
            $payload['range'],
            $payload['lines'],
            $affectedPeriods,
            'approved_price_suggestion',
            $now,
        );

        return $offerId;
    }

    private function createOffer(int $placeId, array $data, int $actorId, $now = null): int
    {
        $now ??= now();

        return (int) DB::table('place_price_offers')->insertGetId([
            'place_id' => $placeId,
            'offer_uuid' => (string) Str::uuid(),
            'source_type' => $data['source_type'],
            'price_product_id' => $data['price_product_id'] ?? null,
            'feature_id' => $data['feature_id'] ?? null,
            'price_product_variant_id' => $data['price_product_variant_id'] ?? null,
            'custom_product_name' => $data['custom_product_name'] ?? null,
            'custom_variant_name' => $data['custom_variant_name'] ?? null,
            'display_name' => $data['display_name'] ?? null,
            'min_vehicle_length_m' => $data['min_vehicle_length_m'] ?? null,
            'max_vehicle_length_m' => $data['max_vehicle_length_m'] ?? null,
            'min_age' => $data['min_age'] ?? null,
            'max_age' => $data['max_age'] ?? null,
            'linked_offer_id' => $data['linked_offer_id'] ?? null,
            'is_refundable' => (bool) ($data['is_refundable'] ?? false),
            'condition_text' => $data['condition_text'] ?? null,
            'is_active' => true,
            'version_valid_from' => $now,
            'version_valid_until' => null,
            'internal_comment' => null,
            'created_by' => $actorId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function updateOffer(object $offer, array $data, int $actorId, $now = null): int
    {
        $now ??= now();

        DB::table('place_price_offers')->where('id', $offer->id)->update([
            'source_type' => $data['source_type'],
            'price_product_id' => $data['price_product_id'] ?? null,
            'feature_id' => $data['feature_id'] ?? null,
            'price_product_variant_id' => $data['price_product_variant_id'] ?? null,
            'custom_product_name' => $data['custom_product_name'] ?? null,
            'custom_variant_name' => $data['custom_variant_name'] ?? null,
            'display_name' => $data['display_name'] ?? null,
            'min_vehicle_length_m' => $data['min_vehicle_length_m'] ?? null,
            'max_vehicle_length_m' => $data['max_vehicle_length_m'] ?? null,
            'min_age' => $data['min_age'] ?? null,
            'max_age' => $data['max_age'] ?? null,
            'linked_offer_id' => $data['linked_offer_id'] ?? null,
            'is_refundable' => (bool) ($data['is_refundable'] ?? false),
            'condition_text' => $data['condition_text'] ?? null,
            'updated_at' => $now,
        ]);

        return (int) $offer->id;
    }

    private function applyReplacement(
        int $offerId,
        int $actorId,
        array $range,
        array $lines,
        Collection $affected,
        string $source,
        $now = null,
    ): array {
        $now ??= now();
        $createdPeriodIds = [];

        foreach ($affected as $period) {
            $existingRange = $this->rangeFromPeriod($period);
            $remainders = $this->subtractRange($existingRange, $range);
            $existingLines = $this->rawLines($period->lines);

            $this->deactivatePeriod($period, $now);

            foreach ($remainders as $remainder) {
                $createdPeriodIds[] = $this->createPeriod(
                    $offerId,
                    $actorId,
                    $remainder,
                    $existingLines,
                    $source.' split remainder',
                    $now,
                );
            }
        }

        $newPeriodId = $this->createPeriod($offerId, $actorId, $range, $lines, $source, $now);
        $createdPeriodIds[] = $newPeriodId;

        return [
            'new_period_id' => $newPeriodId,
            'created_period_ids' => $createdPeriodIds,
        ];
    }

    private function createPeriod(
        int $offerId,
        int $actorId,
        array $range,
        array $lines,
        string $comment,
        $now,
    ): int {
        $periodId = (int) DB::table('place_price_periods')->insertGetId([
            'place_price_offer_id' => $offerId,
            'period_uuid' => (string) Str::uuid(),
            'is_year_round' => (bool) $range['is_year_round'],
            'start_month' => $range['start_month'],
            'start_day' => $range['start_day'],
            'end_month' => $range['end_month'],
            'end_day' => $range['end_day'],
            'is_active' => true,
            'version_valid_from' => $now,
            'version_valid_until' => null,
            'internal_comment' => $comment,
            'created_by' => $actorId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        foreach (array_values($lines) as $index => $line) {
            $status = $line['price_status'] ?? 'unknown';
            $paid = in_array($status, ['fixed', 'from'], true);

            DB::table('place_price_lines')->insert([
                'place_price_period_id' => $periodId,
                'price_status' => $status,
                'amount' => $paid ? ($line['amount'] ?? null) : null,
                'currency_unit_id' => $paid ? ($line['currency_unit_id'] ?? null) : null,
                'rate_quantity' => $paid ? ($line['rate_quantity'] ?? null) : null,
                'price_billing_unit_id' => $paid ? ($line['price_billing_unit_id'] ?? null) : null,
                'sort_order' => ($index + 1) * 10,
                'is_active' => true,
                'version_valid_from' => $now,
                'version_valid_until' => null,
                'internal_comment' => $comment,
                'created_by' => $actorId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return $periodId;
    }

    private function deactivatePeriod(object $period, $now): void
    {
        DB::table('place_price_periods')
            ->where('id', $period->id)
            ->update([
                'is_active' => false,
                'version_valid_until' => $now,
                'updated_at' => $now,
            ]);

        DB::table('place_price_lines')
            ->where('place_price_period_id', $period->id)
            ->where('is_active', true)
            ->whereNull('version_valid_until')
            ->update([
                'is_active' => false,
                'version_valid_until' => $now,
                'updated_at' => $now,
            ]);
    }

    private function rawLines(Collection $lines): array
    {
        return $lines->map(fn ($line) => [
            'price_status' => $line->price_status,
            'amount' => $line->amount,
            'currency_unit_id' => $line->currency_unit_id,
            'rate_quantity' => $line->rate_quantity,
            'price_billing_unit_id' => $line->price_billing_unit_id,
        ])->values()->all();
    }

    private function periodLabel(object $period): string
    {
        return $this->rangeLabel($this->rangeFromPeriod($period));
    }

    private function offerPreview(array $data): array
    {
        $locale = app()->getLocale();

        if (($data['source_type'] ?? 'product') === 'feature') {
            $featureId = (int) ($data['feature_id'] ?? 0);
            $label = $this->translationLabels('feature', collect([$featureId]), $locale)[$featureId] ?? __('place_profile.feature_fallback');

            return [
                'label' => filled($data['display_name'] ?? null) ? $data['display_name'] : $label,
                'descriptor' => $label,
            ];
        }

        $productId = (int) ($data['price_product_id'] ?? 0);
        $variantId = (int) ($data['price_product_variant_id'] ?? 0);
        $productSlug = $productId ? DB::table('price_products')->where('id', $productId)->value('slug') : null;
        $variantSlug = $variantId ? DB::table('price_product_variants')->where('id', $variantId)->value('slug') : null;
        $productLabel = $productId
            ? ($this->translationLabels('price_product', collect([$productId]), $locale)[$productId] ?? __('place_profile.product_fallback'))
            : __('place_profile.product_fallback');
        $variantLabel = $variantId
            ? ($this->translationLabels('price_product_variant', collect([$variantId]), $locale)[$variantId] ?? null)
            : null;

        if ($productSlug === 'other' && filled($data['custom_product_name'] ?? null)) {
            $productLabel = $data['custom_product_name'];
        }

        if ($variantSlug === 'other' && filled($data['custom_variant_name'] ?? null)) {
            $variantLabel = $data['custom_variant_name'];
        }

        $parts = array_values(array_filter([$productLabel, $variantLabel]));

        if (($data['max_vehicle_length_m'] ?? null) !== null) {
            $parts[] = __('place_profile.up_to_metres', ['value' => $this->number($data['max_vehicle_length_m'])]);
        }

        if (($data['max_age'] ?? null) !== null) {
            $parts[] = __('place_profile.up_to_years', ['value' => (int) $data['max_age']]);
        }

        return [
            'label' => filled($data['display_name'] ?? null) ? $data['display_name'] : $productLabel,
            'descriptor' => implode(' · ', $parts),
        ];
    }

    private function offerLabel(object $offer): string
    {
        if (filled($offer->display_name)) {
            return $offer->display_name;
        }

        if ($offer->source_type === 'feature') {
            return $offer->feature_label ?: __('place_profile.feature_fallback');
        }

        if ($offer->product_slug === 'other' && filled($offer->custom_product_name)) {
            return $offer->custom_product_name;
        }

        return $offer->product_label ?: __('place_profile.offer_fallback');
    }

    private function offerDescriptor(object $offer): string
    {
        $parts = [];

        if ($offer->source_type === 'feature') {
            $parts[] = $offer->feature_label ?: __('place_profile.feature_fallback');
        } else {
            $parts[] = $offer->product_slug === 'other' && filled($offer->custom_product_name)
                ? $offer->custom_product_name
                : ($offer->product_label ?: __('place_profile.product_fallback'));

            if ($offer->variant_slug === 'other' && filled($offer->custom_variant_name)) {
                $parts[] = $offer->custom_variant_name;
            } elseif (filled($offer->variant_label)) {
                $parts[] = $offer->variant_label;
            }
        }

        if ($offer->min_vehicle_length_m !== null || $offer->max_vehicle_length_m !== null) {
            if ($offer->min_vehicle_length_m !== null && $offer->max_vehicle_length_m !== null) {
                $parts[] = __('place_profile.metres_range', ['min' => $this->number($offer->min_vehicle_length_m), 'max' => $this->number($offer->max_vehicle_length_m)]);
            } elseif ($offer->max_vehicle_length_m !== null) {
                $parts[] = __('place_profile.up_to_metres', ['value' => $this->number($offer->max_vehicle_length_m)]);
            } else {
                $parts[] = __('place_profile.from_metres', ['value' => $this->number($offer->min_vehicle_length_m)]);
            }
        }

        if ($offer->min_age !== null || $offer->max_age !== null) {
            if ($offer->min_age !== null && $offer->max_age !== null) {
                $parts[] = __('place_profile.years_range', ['min' => $offer->min_age, 'max' => $offer->max_age]);
            } elseif ($offer->max_age !== null) {
                $parts[] = __('place_profile.up_to_years', ['value' => $offer->max_age]);
            } else {
                $parts[] = __('place_profile.from_years', ['value' => $offer->min_age]);
            }
        }

        if ($offer->is_refundable) {
            $parts[] = __('place_profile.refundable');
        }

        return implode(' · ', array_filter($parts));
    }

    private function translationLabels(string $entityType, $ids, string $locale): array
    {
        $ids = collect($ids)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $rows = DB::table('translations')
            ->where('entity_type', $entityType)
            ->whereIn('entity_id', $ids)
            ->where('field', 'name')
            ->where('is_active', true)
            ->whereIn('locale', array_values(array_unique([$locale, LocaleConfiguration::fallback()])))
            ->orderByRaw('CASE WHEN locale = ? THEN 0 ELSE 1 END', [$locale])
            ->get(['entity_id', 'value']);

        $labels = [];
        foreach ($rows as $row) {
            $id = (int) $row->entity_id;
            if (! array_key_exists($id, $labels)) {
                $labels[$id] = $row->value;
            }
        }

        return $labels;
    }

    private function rangeFromPeriod(object $period): array
    {
        return [
            'is_year_round' => (bool) $period->is_year_round,
            'start_month' => $period->start_month !== null ? (int) $period->start_month : null,
            'start_day' => $period->start_day !== null ? (int) $period->start_day : null,
            'end_month' => $period->end_month !== null ? (int) $period->end_month : null,
            'end_day' => $period->end_day !== null ? (int) $period->end_day : null,
        ];
    }

    private function subtractRange(array $existing, array $incoming): array
    {
        $remaining = array_values(array_diff(
            $this->daysForRange($existing),
            $this->daysForRange($incoming),
        ));

        if ($remaining === []) {
            return [];
        }

        sort($remaining);
        $segments = [];
        $current = [$remaining[0]];

        for ($i = 1, $count = count($remaining); $i < $count; $i++) {
            if ($remaining[$i] === $remaining[$i - 1] + 1) {
                $current[] = $remaining[$i];
                continue;
            }

            $segments[] = $current;
            $current = [$remaining[$i]];
        }

        $segments[] = $current;

        return array_map(function (array $segment): array {
            [$startMonth, $startDay] = $this->monthDayFromOrdinal($segment[0]);
            [$endMonth, $endDay] = $this->monthDayFromOrdinal(end($segment));

            return [
                'is_year_round' => count($segment) === self::DAYS_IN_YEAR,
                'start_month' => $startMonth,
                'start_day' => $startDay,
                'end_month' => $endMonth,
                'end_day' => $endDay,
            ];
        }, $segments);
    }

    private function daysForRange(array $range): array
    {
        if ($range['is_year_round']) {
            return range(1, self::DAYS_IN_YEAR);
        }

        $start = $this->ordinal((int) $range['start_month'], (int) $range['start_day']);
        $end = $this->ordinal((int) $range['end_month'], (int) $range['end_day']);

        return $start <= $end
            ? range($start, $end)
            : array_merge(range($start, self::DAYS_IN_YEAR), range(1, $end));
    }

    private function ordinal(int $month, int $day): int
    {
        return (int) (new \DateTimeImmutable(sprintf('2000-%02d-%02d', $month, $day)))->format('z') + 1;
    }

    private function monthDayFromOrdinal(int $ordinal): array
    {
        $date = (new \DateTimeImmutable('2000-01-01'))->modify('+'.($ordinal - 1).' days');

        return [(int) $date->format('n'), (int) $date->format('j')];
    }

    private function parseDayMonth(?string $value): array
    {
        $value = trim((string) $value);

        if (! preg_match('/^(\d{1,2})\.(\d{1,2})\.?$/', $value, $matches)) {
            throw new RuntimeException(__('place_editing.price.validation.invalid_format'));
        }

        $day = (int) $matches[1];
        $month = (int) $matches[2];

        if (! checkdate($month, $day, 2000)) {
            throw new RuntimeException(__('place_editing.price.validation.invalid_date'));
        }

        return [$day, $month];
    }

    private function number(mixed $value): string
    {
        $number = (float) $value;

        return \Illuminate\Support\Number::format($number, 0, 2, app()->getLocale());
    }
}
