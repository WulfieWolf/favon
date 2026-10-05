<?php

namespace App\Http\Controllers;

use App\Services\PermissionService;
use App\Services\PricePeriodService;
use App\Services\UsageAnalyticsService;
use App\Services\UserNotificationService;
use App\Services\XpService;
use App\Services\BadgeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class PlacePriceController extends Controller
{
    private const PRICE_STATUSES = ['fixed', 'from', 'included', 'free', 'on_request', 'unknown'];

    public function edit(
        Request $request,
        string $slug,
        PermissionService $permissions,
        PricePeriodService $prices,
    ): View {
        $place = $this->place($slug);

        abort_unless(
            $permissions->can($request->user(), 'places.edit')
                || $permissions->can($request->user(), 'places.suggest'),
            404,
        );

        $offers = $prices->currentOffers((int) $place->id);
        $editingOffer = $prices->offerForEdit(
            (int) $place->id,
            $request->integer('offer') ?: null,
        );
        $editingPeriod = $prices->periodForEdit(
            $editingOffer,
            $request->integer('period') ?: null,
        );

        $products = $prices->products();
        $variants = $prices->variants();
        $billingUnits = $prices->billingUnits();
        $features = $prices->priceableFeatures();

        $eurId = DB::table('units')->where('unit_key', 'EUR')->value('id');
        $defaultBillingId = $billingUnits->firstWhere('slug', 'per-night')?->id;

        $lines = $prices->linesFromPeriod($editingPeriod);
        foreach ($lines as &$line) {
            $line['currency_unit_id'] ??= $eurId;
            $line['price_billing_unit_id'] ??= $defaultBillingId;
            $line['rate_quantity'] ??= 1;
        }
        unset($line);

        return view('places.prices-edit', [
            'place' => $place,
            'offers' => $offers,
            'editingOffer' => $editingOffer,
            'editingPeriod' => $editingPeriod,
            'products' => $products,
            'variants' => $variants,
            'billingUnits' => $billingUnits,
            'features' => $features,
            'lines' => $lines,
            'periodMode' => $editingPeriod
                ? ($editingPeriod->is_year_round ? 'year_round' : 'seasonal')
                : ($editingOffer ? 'seasonal' : 'year_round'),
            'startMd' => $editingPeriod && ! $editingPeriod->is_year_round
                ? sprintf('%02d.%02d.', $editingPeriod->start_day, $editingPeriod->start_month)
                : null,
            'endMd' => $editingPeriod && ! $editingPeriod->is_year_round
                ? sprintf('%02d.%02d.', $editingPeriod->end_day, $editingPeriod->end_month)
                : null,
            'canDirectEdit' => $permissions->can($request->user(), 'places.edit'),
            'overlapPreview' => session('price_overlap_preview'),
            'eurId' => $eurId,
        ]);
    }

    public function update(
        Request $request,
        string $slug,
        PermissionService $permissions,
        UserNotificationService $notifications,
        PricePeriodService $prices,
    ): RedirectResponse {
        $place = $this->place($slug);
        $canDirectEdit = $permissions->can($request->user(), 'places.edit');

        abort_unless(
            $canDirectEdit || $permissions->can($request->user(), 'places.suggest'),
            403,
        );

        $data = $request->validate([
            'offer_id' => ['nullable', 'integer'],
            'period_id' => ['nullable', 'integer'],
            'source_type' => ['required', Rule::in(['product', 'feature'])],
            'price_product_id' => ['nullable', 'integer', 'exists:price_products,id'],
            'feature_id' => ['nullable', 'integer', 'exists:features,id'],
            'price_product_variant_id' => ['nullable', 'integer', 'exists:price_product_variants,id'],
            'custom_product_name' => ['nullable', 'string', 'max:160'],
            'custom_variant_name' => ['nullable', 'string', 'max:160'],
            'display_name' => ['nullable', 'string', 'max:160'],
            'min_vehicle_length_m' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'max_vehicle_length_m' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'min_age' => ['nullable', 'integer', 'min:0', 'max:120'],
            'max_age' => ['nullable', 'integer', 'min:0', 'max:120'],
            'linked_offer_id' => ['nullable', 'integer'],
            'is_refundable' => ['nullable', 'boolean'],
            'condition_text' => ['nullable', 'string', 'max:1000'],
            'period_mode' => ['required', Rule::in(['year_round', 'seasonal'])],
            'start_md' => ['nullable', 'string', 'max:6'],
            'end_md' => ['nullable', 'string', 'max:6'],
            'confirm_overlap' => ['nullable', 'boolean'],
            'lines' => ['required', 'array', 'min:1', 'max:10'],
            'lines.*.price_status' => ['required', Rule::in(self::PRICE_STATUSES)],
            'lines.*.amount' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'lines.*.currency_unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'lines.*.rate_quantity' => ['nullable', 'numeric', 'min:0.001', 'max:10000000'],
            'lines.*.price_billing_unit_id' => ['nullable', 'integer', 'exists:price_billing_units,id'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $editingOfferId = isset($data['offer_id']) ? (int) $data['offer_id'] : null;
        $editingPeriodId = isset($data['period_id']) ? (int) $data['period_id'] : null;
        $editingOffer = $editingOfferId
            ? $prices->offerForEdit((int) $place->id, $editingOfferId)
            : null;

        if ($editingOfferId && ! $editingOffer) {
            abort(404);
        }

        if ($editingPeriodId && ! $prices->periodForEdit($editingOffer, $editingPeriodId)) {
            abort(404);
        }

        $offerData = $this->normalizeOfferData($data, (int) $place->id, $editingOfferId);

        foreach ($data['lines'] as $index => $line) {
            if (in_array($line['price_status'], ['fixed', 'from'], true)) {
                if (! array_key_exists('amount', $line) || $line['amount'] === null || $line['amount'] === '') {
                    return back()->withInput()->withErrors([
                        "lines.{$index}.amount" => __('place_editing.price.validation.amount_required'),
                    ]);
                }

                if (empty($line['currency_unit_id'])) {
                    return back()->withInput()->withErrors([
                        "lines.{$index}.currency_unit_id" => __('place_editing.price.validation.currency_required'),
                    ]);
                }

                $isCurrencyUnit = DB::table('unit_type_units')
                    ->where('unit_type', 'currency')
                    ->where('unit_id', (int) $line['currency_unit_id'])
                    ->exists();

                if (! $isCurrencyUnit) {
                    return back()->withInput()->withErrors([
                        "lines.{$index}.currency_unit_id" => __('place_editing.price.validation.currency_invalid'),
                    ]);
                }

                if (empty($line['price_billing_unit_id'])) {
                    return back()->withInput()->withErrors([
                        "lines.{$index}.price_billing_unit_id" => __('place_editing.price.validation.billing_required'),
                    ]);
                }
            }
        }

        if (($offerData['min_vehicle_length_m'] ?? null) !== null
            && ($offerData['max_vehicle_length_m'] ?? null) !== null
            && (float) $offerData['min_vehicle_length_m'] > (float) $offerData['max_vehicle_length_m']) {
            return back()->withInput()->withErrors([
                'min_vehicle_length_m' => __('place_editing.price.validation.vehicle_length'),
            ]);
        }

        if (($offerData['min_age'] ?? null) !== null
            && ($offerData['max_age'] ?? null) !== null
            && (int) $offerData['min_age'] > (int) $offerData['max_age']) {
            return back()->withInput()->withErrors([
                'min_age' => __('place_editing.price.validation.age'),
            ]);
        }

        try {
            $range = $prices->normalizeRange(
                $data['period_mode'] === 'year_round',
                $data['start_md'] ?? null,
                $data['end_md'] ?? null,
            );
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['start_md' => $e->getMessage()]);
        }

        $overlapPreview = $prices->overlapPreview($editingOffer, $range, $editingPeriodId);
        $requiresConfirmation = collect($overlapPreview)
            ->contains(fn ($affected) => ! ($affected['editing'] ?? false));

        if ($requiresConfirmation && ! ($data['confirm_overlap'] ?? false)) {
            return back()
                ->withInput()
                ->with('price_overlap_preview', [
                    'range' => $prices->rangeLabel($range),
                    'affected' => $overlapPreview,
                ]);
        }

        $lines = collect($data['lines'])->map(function ($line) {
            $paid = in_array($line['price_status'], ['fixed', 'from'], true);

            return [
                'price_status' => $line['price_status'],
                'amount' => $paid ? $line['amount'] : null,
                'currency_unit_id' => $paid ? ($line['currency_unit_id'] ?? null) : null,
                'rate_quantity' => $paid ? ($line['rate_quantity'] ?? 1) : null,
                'price_billing_unit_id' => $paid ? ($line['price_billing_unit_id'] ?? null) : null,
            ];
        })->values()->all();

        if ($canDirectEdit) {
            $result = $prices->applyDirect(
                (int) $place->id,
                (int) $request->user()->id,
                $offerData,
                $range,
                $lines,
                $editingOfferId,
                $editingPeriodId,
            );

            $offerId = (int) ($result['offer_id'] ?? 0);

            if ($offerId > 0) {
                app(XpService::class)->awardPlaceField(
                    (int) $request->user()->id,
                    (int) $place->id,
                    'price-offer:'.$offerId,
                    (int) config('xp.place_info.default_xp', 1),
                    'Preisangabe ergänzt oder aktualisiert',
                    'place_price_offer',
                    $offerId,
                );

                $rewardRequest = (object) [
                    'submitted_by' => (int) $request->user()->id,
                    'place_id' => (int) $place->id,
                    'target_table' => 'place_price_offers',
                    'target_field' => 'period_pricing',
                    'proposed_value' => json_encode([
                        'offer' => $offerData,
                        'range' => $range,
                        'lines' => $lines,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ];

                app(BadgeService::class)->recordApprovedPlaceChange(
                    $rewardRequest,
                    $offerId,
                    (string) $place->name,
                );
            }

            $notifications->queueFavoritePlaceChange(
                (int) $place->id,
                [(int) $request->user()->id],
                'direct_price_edit',
            );

            app(UsageAnalyticsService::class)->track(
                $request,
                'price_submitted',
                'prices',
                'place',
                (int) $place->id,
                ['direct' => true],
            );

            return redirect()
                ->route('places.show', $place->slug)
                ->with('ui_dialog', [
                    'variant' => 'success',
                    'message' => __('place_editing.price.status_saved'),
                ]);
        }

        $changeRequestId = $prices->createProposal(
            (int) $place->id,
            (int) $request->user()->id,
            $offerData,
            $range,
            $lines,
            $data['comment'] ?? null,
            $editingOfferId,
            $editingPeriodId,
        );

        DB::table('audit_logs')->insert([
            'user_id' => $request->user()->id,
            'entity_type' => 'place',
            'entity_id' => $place->id,
            'action' => 'price_period_change_suggested',
            'source' => 'user',
            'old_values' => null,
            'new_values' => json_encode([
                'change_request_id' => $changeRequestId,
                'offer_id' => $editingOfferId,
                'range' => $range,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'internal_comment' => null,
            'created_at' => now(),
        ]);

        app(UsageAnalyticsService::class)->track(
            $request,
            'price_submitted',
            'prices',
            'place',
            (int) $place->id,
            ['direct' => false],
        );

        return redirect()
            ->route('places.show', $place->slug)
            ->with('ui_dialog', [
                'variant' => 'success',
                'message' => __('place_editing.price.status_submitted', ['id' => $changeRequestId]),
            ]);
    }

    private function normalizeOfferData(array $data, int $placeId, ?int $editingOfferId): array
    {
        $sourceType = $data['source_type'];

        if ($sourceType === 'product' && empty($data['price_product_id'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'price_product_id' => __('place_editing.price.validation.product_required'),
            ]);
        }

        if ($sourceType === 'feature' && empty($data['feature_id'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'feature_id' => __('place_editing.price.validation.feature_required'),
            ]);
        }

        if ($sourceType === 'feature') {
            $featureAllowed = DB::table('features as f')
                ->join('feature_categories as fc', 'fc.id', '=', 'f.category_id')
                ->where('f.id', (int) $data['feature_id'])
                ->where('f.is_active', true)
                ->whereIn('fc.slug', ['utilities', 'sanitary', 'facilities', 'services', 'rental'])
                ->exists();

            if (! $featureAllowed) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'feature_id' => __('place_editing.price.validation.feature_invalid'),
                ]);
            }
        }

        $product = ! empty($data['price_product_id'])
            ? DB::table('price_products')->where('id', $data['price_product_id'])->where('is_active', true)->first()
            : null;

        if ($sourceType === 'product' && ! $product) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'price_product_id' => __('place_editing.price.validation.product_unavailable'),
            ]);
        }

        if ($sourceType === 'product'
            && $product?->slug === 'other'
            && blank($data['custom_product_name'] ?? null)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'custom_product_name' => __('place_editing.price.validation.custom_product_required'),
            ]);
        }

        $variantId = $sourceType === 'product' && ! empty($data['price_product_variant_id'])
            ? (int) $data['price_product_variant_id']
            : null;

        if ($variantId) {
            $variant = DB::table('price_product_variants')->where('id', $variantId)->where('is_active', true)->first();

            if (! $variant || (int) $variant->price_product_id !== (int) $product->id) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'price_product_variant_id' => __('place_editing.price.validation.variant_invalid'),
                ]);
            }

            if ($variant->slug === 'other' && blank($data['custom_variant_name'] ?? null)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'custom_variant_name' => __('place_editing.price.validation.custom_variant_required'),
                ]);
            }
        }

        $linkedOfferId = ! empty($data['linked_offer_id']) ? (int) $data['linked_offer_id'] : null;
        if ($linkedOfferId) {
            if ($editingOfferId && $linkedOfferId === $editingOfferId) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'linked_offer_id' => __('place_editing.price.validation.self_link'),
                ]);
            }

            $validLinkedOffer = DB::table('place_price_offers')
                ->where('id', $linkedOfferId)
                ->where('place_id', $placeId)
                ->where('is_active', true)
                ->whereNull('version_valid_until')
                ->exists();

            if (! $validLinkedOffer) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'linked_offer_id' => __('place_editing.price.validation.linked_unavailable'),
                ]);
            }
        }

        return [
            'source_type' => $sourceType,
            'price_product_id' => $sourceType === 'product' ? (int) $product->id : null,
            'feature_id' => $sourceType === 'feature' ? (int) $data['feature_id'] : null,
            'price_product_variant_id' => $variantId,
            'custom_product_name' => $sourceType === 'product' ? trim((string) ($data['custom_product_name'] ?? '')) ?: null : null,
            'custom_variant_name' => $sourceType === 'product' ? trim((string) ($data['custom_variant_name'] ?? '')) ?: null : null,
            'display_name' => trim((string) ($data['display_name'] ?? '')) ?: null,
            'min_vehicle_length_m' => $sourceType === 'product' && $product->supports_vehicle_length
                ? ($data['min_vehicle_length_m'] ?? null)
                : null,
            'max_vehicle_length_m' => $sourceType === 'product' && $product->supports_vehicle_length
                ? ($data['max_vehicle_length_m'] ?? null)
                : null,
            'min_age' => $sourceType === 'product' && $product->supports_age_range
                ? ($data['min_age'] ?? null)
                : null,
            'max_age' => $sourceType === 'product' && $product->supports_age_range
                ? ($data['max_age'] ?? null)
                : null,
            'linked_offer_id' => $linkedOfferId,
            'is_refundable' => (bool) ($data['is_refundable'] ?? false),
            'condition_text' => trim((string) ($data['condition_text'] ?? '')) ?: null,
        ];
    }

    private function place(string $slug): object
    {
        $place = DB::table('places')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->where('publication_status', 'published')
            ->first(['id', 'name', 'slug']);

        abort_unless($place, 404);

        return $place;
    }
}
