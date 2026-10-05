<x-layouts::app :title="__('place_editing.price.page_title').' · '.$place->name">
    @php
        $sourceType = old('source_type', $editingOffer?->source_type ?? 'product');
        $selectedProductId = old('price_product_id', $editingOffer?->price_product_id);
        $selectedFeatureId = old('feature_id', $editingOffer?->feature_id);
        $selectedVariantId = old('price_product_variant_id', $editingOffer?->price_product_variant_id);
        $formLines = old('lines', $lines);
    @endphp

    <div class="mx-auto max-w-6xl space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <a href="{{ route('places.show', $place->slug) }}" class="text-xs text-zinc-500 hover:text-zinc-900 dark:hover:text-white">← {{ __('place_editing.common.back_to_place') }}</a>
                <h1 class="mt-2 text-2xl font-semibold">{{ __('place_editing.price.edit') }}</h1>
                <p class="mt-1 text-sm text-zinc-500">{{ $place->name }}</p>
            </div>
            <a href="{{ route('places.prices.edit', $place->slug) }}" class="rounded-lg border border-zinc-300 px-3 py-2 text-sm font-medium dark:border-zinc-700">
                {{ __('place_editing.price.new_offer') }}
            </a>
        </div>
@if ($offers->isNotEmpty())
            <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="text-base font-semibold">{{ __('place_editing.price.existing_offers') }}</h2>
                <div class="mt-3 grid gap-3 md:grid-cols-2">
                    @foreach ($offers as $offer)
                        <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                            <div class="font-medium">{{ $offer->label }}</div>
                            @if ($offer->descriptor && $offer->descriptor !== $offer->label)
                                <div class="mt-0.5 text-xs text-zinc-500">{{ $offer->descriptor }}</div>
                            @endif
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach ($offer->periods as $period)
                                    <a href="{{ route('places.prices.edit', [$place->slug, 'offer' => $offer->id, 'period' => $period->id]) }}" class="rounded-md border border-zinc-200 px-2 py-1 text-xs hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">
                                        {{ __('place_editing.price.edit_period', ['period' => $period->label]) }}
                                    </a>
                                @endforeach
                                <a href="{{ route('places.prices.edit', [$place->slug, 'offer' => $offer->id]) }}" class="rounded-md border border-dashed border-zinc-300 px-2 py-1 text-xs text-zinc-500 hover:text-zinc-900 dark:border-zinc-700 dark:hover:text-white">
                                    + {{ __('place_editing.price.add_season_price') }}
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <form method="POST" action="{{ route('places.prices.update', $place->slug) }}" class="space-y-6" id="price-form">
            @csrf
            @method('PUT')
            <input type="hidden" name="offer_id" value="{{ old('offer_id', $editingOffer?->id) }}">
            <input type="hidden" name="period_id" value="{{ old('period_id', $editingPeriod?->id) }}">

            <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <div>
                    <h2 class="text-base font-semibold">{{ __('place_editing.price.offer') }}</h2>
                    <p class="mt-1 text-xs text-zinc-500">{{ __('place_editing.price.offer_help') }}</p>
                </div>

                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <label class="block">
                        <span class="mb-1 block text-sm font-medium">{{ __('place_editing.price.type') }}</span>
                        <select name="source_type" id="source-type" class="h-10 w-full rounded-lg border border-zinc-300 bg-white px-3 dark:border-zinc-700 dark:bg-zinc-950">
                            @foreach (__('place_editing.price.source_types') as $value => $label)
                                <option value="{{ $value }}" @selected($sourceType === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block" id="product-field">
                        <span class="mb-1 block text-sm font-medium">{{ __('place_editing.price.product') }}</span>
                        <select name="price_product_id" id="product-select" class="h-10 w-full rounded-lg border border-zinc-300 bg-white px-3 dark:border-zinc-700 dark:bg-zinc-950">
                            <option value="">{{ __('place_editing.price.choose') }}</option>
                            @foreach ($products as $product)
                                <option
                                    value="{{ $product->id }}"
                                    data-vehicle-length="{{ $product->supports_vehicle_length ? '1' : '0' }}"
                                    data-age-range="{{ $product->supports_age_range ? '1' : '0' }}"
                                    data-slug="{{ $product->slug }}"
                                    @selected((string) $selectedProductId === (string) $product->id)
                                >{{ $product->label }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="hidden" id="feature-field">
                        <span class="mb-1 block text-sm font-medium">{{ __('place_editing.price.feature') }}</span>
                        <select name="feature_id" class="h-10 w-full rounded-lg border border-zinc-300 bg-white px-3 dark:border-zinc-700 dark:bg-zinc-950">
                            <option value="">{{ __('place_editing.price.choose') }}</option>
                            @foreach ($features as $feature)
                                <option value="{{ $feature->id }}" @selected((string) $selectedFeatureId === (string) $feature->id)>{{ $feature->label }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block" id="variant-field">
                        <span class="mb-1 block text-sm font-medium">{{ __('place_editing.price.variant') }} <span class="font-normal text-zinc-400">({{ __('place_editing.common.optional') }})</span></span>
                        <select name="price_product_variant_id" id="variant-select" class="h-10 w-full rounded-lg border border-zinc-300 bg-white px-3 dark:border-zinc-700 dark:bg-zinc-950">
                            <option value="">{{ __('place_editing.price.no_variant') }}</option>
                            @foreach ($variants as $variant)
                                <option value="{{ $variant->id }}" data-product-id="{{ $variant->price_product_id }}" data-slug="{{ $variant->slug }}" @selected((string) $selectedVariantId === (string) $variant->id)>
                                    {{ $variant->label }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block" id="custom-product-field">
                        <span class="mb-1 block text-sm font-medium">{{ __('place_editing.price.custom_product') }}</span>
                        <input type="text" name="custom_product_name" value="{{ old('custom_product_name', $editingOffer?->custom_product_name) }}" maxlength="160" placeholder="{{ __('place_editing.price.custom_product_placeholder') }}" class="h-10 w-full rounded-lg border border-zinc-300 bg-white px-3 dark:border-zinc-700 dark:bg-zinc-950">
                    </label>

                    <label class="block" id="custom-variant-field">
                        <span class="mb-1 block text-sm font-medium">{{ __('place_editing.price.custom_variant') }}</span>
                        <input type="text" name="custom_variant_name" value="{{ old('custom_variant_name', $editingOffer?->custom_variant_name) }}" maxlength="160" placeholder="{{ __('place_editing.price.custom_variant_placeholder') }}" class="h-10 w-full rounded-lg border border-zinc-300 bg-white px-3 dark:border-zinc-700 dark:bg-zinc-950">
                    </label>

                    <label class="block md:col-span-2">
                        <span class="mb-1 block text-sm font-medium">{{ __('place_editing.price.display_name') }} <span class="font-normal text-zinc-400">({{ __('place_editing.common.optional') }})</span></span>
                        <input type="text" name="display_name" value="{{ old('display_name', $editingOffer?->display_name) }}" maxlength="160" placeholder="{{ __('place_editing.price.display_name_placeholder') }}" class="h-10 w-full rounded-lg border border-zinc-300 bg-white px-3 dark:border-zinc-700 dark:bg-zinc-950">
                        <span class="mt-1 block text-xs text-zinc-400">{{ __('place_editing.price.display_name_help') }}</span>
                    </label>

                    <div id="vehicle-length-fields" class="grid gap-3 sm:grid-cols-2 md:col-span-2">
                        <label>
                            <span class="mb-1 block text-sm font-medium">{{ __('place_editing.price.min_vehicle_length') }}</span>
                            <input type="number" step="0.01" min="0" max="100" name="min_vehicle_length_m" value="{{ old('min_vehicle_length_m', $editingOffer?->min_vehicle_length_m) }}" class="h-10 w-full rounded-lg border border-zinc-300 bg-white px-3 dark:border-zinc-700 dark:bg-zinc-950">
                        </label>
                        <label>
                            <span class="mb-1 block text-sm font-medium">{{ __('place_editing.price.max_vehicle_length') }}</span>
                            <input type="number" step="0.01" min="0" max="100" name="max_vehicle_length_m" value="{{ old('max_vehicle_length_m', $editingOffer?->max_vehicle_length_m) }}" class="h-10 w-full rounded-lg border border-zinc-300 bg-white px-3 dark:border-zinc-700 dark:bg-zinc-950">
                        </label>
                    </div>

                    <div id="age-fields" class="grid gap-3 sm:grid-cols-2 md:col-span-2">
                        <label>
                            <span class="mb-1 block text-sm font-medium">{{ __('place_editing.price.min_age') }}</span>
                            <input type="number" min="0" max="120" name="min_age" value="{{ old('min_age', $editingOffer?->min_age) }}" class="h-10 w-full rounded-lg border border-zinc-300 bg-white px-3 dark:border-zinc-700 dark:bg-zinc-950">
                        </label>
                        <label>
                            <span class="mb-1 block text-sm font-medium">{{ __('place_editing.price.max_age') }}</span>
                            <input type="number" min="0" max="120" name="max_age" value="{{ old('max_age', $editingOffer?->max_age) }}" class="h-10 w-full rounded-lg border border-zinc-300 bg-white px-3 dark:border-zinc-700 dark:bg-zinc-950">
                        </label>
                    </div>

                    <label class="block">
                        <span class="mb-1 block text-sm font-medium">{{ __('place_editing.price.linked_offer') }} <span class="font-normal text-zinc-400">({{ __('place_editing.common.optional') }})</span></span>
                        <select name="linked_offer_id" class="h-10 w-full rounded-lg border border-zinc-300 bg-white px-3 dark:border-zinc-700 dark:bg-zinc-950">
                            <option value="">{{ __('place_editing.price.applies_generally') }}</option>
                            @foreach ($offers as $offer)
                                @if (! $editingOffer || $offer->id !== $editingOffer->id)
                                    <option value="{{ $offer->id }}" @selected((string) old('linked_offer_id', $editingOffer?->linked_offer_id) === (string) $offer->id)>
                                        {{ __('place_editing.price.only_for', ['offer' => $offer->label]) }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </label>

                    <label class="flex items-center gap-2 self-end pb-2">
                        <input type="checkbox" name="is_refundable" value="1" @checked(old('is_refundable', $editingOffer?->is_refundable)) class="rounded border-zinc-300 dark:border-zinc-700">
                        <span class="text-sm">{{ __('place_editing.price.refundable') }}</span>
                    </label>

                    <label class="block md:col-span-2">
                        <span class="mb-1 block text-sm font-medium">{{ __('place_editing.price.conditions') }} <span class="font-normal text-zinc-400">({{ __('place_editing.common.optional') }})</span></span>
                        <textarea name="condition_text" rows="3" maxlength="1000" placeholder="{{ __('place_editing.price.conditions_placeholder') }}" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">{{ old('condition_text', $editingOffer?->condition_text) }}</textarea>
                    </label>
                </div>
            </section>

            <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="text-base font-semibold">{{ __('place_editing.price.period') }}</h2>
                <p class="mt-1 text-xs text-zinc-500">{{ __('place_editing.price.period_help') }}</p>

                <div class="mt-4 flex flex-wrap gap-4">
                    <label class="flex items-center gap-2">
                        <input type="radio" name="period_mode" value="year_round" @checked(old('period_mode', $periodMode) === 'year_round')>
                        <span>{{ __('place_editing.price.year_round') }}</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="radio" name="period_mode" value="seasonal" @checked(old('period_mode', $periodMode) === 'seasonal')>
                        <span>{{ __('place_editing.price.seasonal') }}</span>
                    </label>
                </div>

                <div id="season-fields" class="mt-4 grid gap-3 sm:grid-cols-2">
                    <label>
                        <span class="mb-1 block text-sm font-medium">{{ __('place_editing.common.from') }}</span>
                        <input type="text" name="start_md" value="{{ old('start_md', $startMd) }}" placeholder="01.04." maxlength="6" class="h-10 w-full rounded-lg border border-zinc-300 bg-white px-3 dark:border-zinc-700 dark:bg-zinc-950">
                    </label>
                    <label>
                        <span class="mb-1 block text-sm font-medium">{{ __('place_editing.common.until') }}</span>
                        <input type="text" name="end_md" value="{{ old('end_md', $endMd) }}" placeholder="31.10." maxlength="6" class="h-10 w-full rounded-lg border border-zinc-300 bg-white px-3 dark:border-zinc-700 dark:bg-zinc-950">
                    </label>
                </div>

                @if ($overlapPreview)
                    <div class="mt-4 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-100">
                        <div class="font-semibold">{{ __('place_editing.price.overlap_title', ['range' => $overlapPreview['range']]) }}</div>
                        <div class="mt-2 space-y-1">
                            @foreach ($overlapPreview['affected'] as $affected)
                                <div>
                                    {{ $affected['label'] }}
                                    @if (empty($affected['remainders']))
                                        → {{ __('place_editing.price.fully_replaced') }}
                                    @else
                                        → {{ __('place_editing.price.remains_as', ['ranges' => implode(__('place_editing.price.range_joiner'), $affected['remainders'])]) }}
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        <label class="mt-3 flex items-center gap-2 font-medium">
                            <input type="checkbox" name="confirm_overlap" value="1">
                            <span>{{ __('place_editing.price.accept_overlap') }}</span>
                        </label>
                    </div>
                @endif
            </section>

            <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-base font-semibold">{{ __('place_editing.price.price_lines') }}</h2>
                        <p class="mt-1 text-xs text-zinc-500">{{ __('place_editing.price.price_lines_help') }}</p>
                    </div>
                    <button type="button" id="add-price-line" class="rounded-lg border border-zinc-300 px-3 py-2 text-xs font-medium dark:border-zinc-700">+ {{ __('place_editing.price.add_line') }}</button>
                </div>

                <div id="price-lines" class="mt-4 space-y-3">
                    @foreach ($formLines as $index => $line)
                        <div class="price-line rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                            <div class="grid gap-3 md:grid-cols-5">
                                <label>
                                    <span class="mb-1 block text-xs font-medium text-zinc-500">{{ __('place_editing.price.price_type') }}</span>
                                    <select name="lines[{{ $index }}][price_status]" data-name="price_status" class="price-status h-10 w-full rounded-lg border border-zinc-300 bg-white px-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                        @foreach (__('place_editing.price.price_statuses') as $value => $label)
                                            <option value="{{ $value }}" @selected(($line['price_status'] ?? 'fixed') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </label>

                                <label class="amount-field">
                                    <span class="mb-1 block text-xs font-medium text-zinc-500">{{ __('place_editing.price.amount') }}</span>
                                    <input type="number" step="0.01" min="0" name="lines[{{ $index }}][amount]" data-name="amount" value="{{ $line['amount'] ?? '' }}" class="h-10 w-full rounded-lg border border-zinc-300 bg-white px-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                </label>

                                <label class="currency-field">
                                    <span class="mb-1 block text-xs font-medium text-zinc-500">{{ __('place_editing.price.currency') }}</span>
                                    <select name="lines[{{ $index }}][currency_unit_id]" data-name="currency_unit_id" class="h-10 w-full rounded-lg border border-zinc-300 bg-white px-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                        <option value="{{ $eurId }}" selected>EUR (€)</option>
                                    </select>
                                </label>

                                <label class="billing-field">
                                    <span class="mb-1 block text-xs font-medium text-zinc-500">{{ __('place_editing.price.billing') }}</span>
                                    <select name="lines[{{ $index }}][price_billing_unit_id]" data-name="price_billing_unit_id" class="h-10 w-full rounded-lg border border-zinc-300 bg-white px-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                        @foreach ($billingUnits as $billingUnit)
                                            <option value="{{ $billingUnit->id }}" @selected((string) ($line['price_billing_unit_id'] ?? '') === (string) $billingUnit->id)>{{ $billingUnit->label }}</option>
                                        @endforeach
                                    </select>
                                </label>

                                <label class="quantity-field">
                                    <span class="mb-1 block text-xs font-medium text-zinc-500">{{ __('place_editing.price.quantity') }}</span>
                                    <div class="flex gap-2">
                                        <input type="number" step="0.001" min="0.001" name="lines[{{ $index }}][rate_quantity]" data-name="rate_quantity" value="{{ $line['rate_quantity'] ?? 1 }}" class="h-10 min-w-0 flex-1 rounded-lg border border-zinc-300 bg-white px-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                        <button type="button" class="remove-price-line rounded-lg border border-zinc-300 px-2 text-zinc-500 dark:border-zinc-700" title="{{ __('place_editing.price.remove_line') }}" aria-label="{{ __('place_editing.price.remove_line') }}">×</button>
                                    </div>
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            @if (! $canDirectEdit)
                <label class="block rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                    <span class="mb-1 block text-sm font-medium">{{ __('place_editing.price.comment') }} <span class="font-normal text-zinc-400">({{ __('place_editing.common.optional') }})</span></span>
                    <textarea name="comment" rows="3" maxlength="2000" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">{{ old('comment') }}</textarea>
                </label>
            @endif

            <div class="flex justify-end gap-3">
                <a href="{{ route('places.show', $place->slug) }}" class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium dark:border-zinc-700">{{ __('place_editing.common.cancel') }}</a>
                <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white dark:bg-white dark:text-zinc-900">
                    {{ $canDirectEdit ? __('place_editing.price.save') : __('place_editing.price.submit') }}
                </button>
            </div>
        </form>
    </div>

    <template id="price-line-template">
        <div class="price-line rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
            <div class="grid gap-3 md:grid-cols-5">
                <label>
                    <span class="mb-1 block text-xs font-medium text-zinc-500">{{ __('place_editing.price.price_type') }}</span>
                    <select data-name="price_status" class="price-status h-10 w-full rounded-lg border border-zinc-300 bg-white px-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                        @foreach (__('place_editing.price.price_statuses') as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="amount-field">
                    <span class="mb-1 block text-xs font-medium text-zinc-500">{{ __('place_editing.price.amount') }}</span>
                    <input data-name="amount" type="number" step="0.01" min="0" class="h-10 w-full rounded-lg border border-zinc-300 bg-white px-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                </label>
                <label class="currency-field">
                    <span class="mb-1 block text-xs font-medium text-zinc-500">{{ __('place_editing.price.currency') }}</span>
                    <select data-name="currency_unit_id" class="h-10 w-full rounded-lg border border-zinc-300 bg-white px-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                        <option value="{{ $eurId }}">EUR (€)</option>
                    </select>
                </label>
                <label class="billing-field">
                    <span class="mb-1 block text-xs font-medium text-zinc-500">{{ __('place_editing.price.billing') }}</span>
                    <select data-name="price_billing_unit_id" class="h-10 w-full rounded-lg border border-zinc-300 bg-white px-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                        @foreach ($billingUnits as $billingUnit)
                            <option value="{{ $billingUnit->id }}">{{ $billingUnit->label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="quantity-field">
                    <span class="mb-1 block text-xs font-medium text-zinc-500">{{ __('place_editing.price.quantity') }}</span>
                    <div class="flex gap-2">
                        <input data-name="rate_quantity" type="number" step="0.001" min="0.001" value="1" class="h-10 min-w-0 flex-1 rounded-lg border border-zinc-300 bg-white px-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                        <button type="button" class="remove-price-line rounded-lg border border-zinc-300 px-2 text-zinc-500 dark:border-zinc-700" title="{{ __('place_editing.price.remove_line') }}" aria-label="{{ __('place_editing.price.remove_line') }}">×</button>
                    </div>
                </label>
            </div>
        </div>
    </template>

    <script>
        (() => {
            const sourceType = document.getElementById('source-type');
            const productField = document.getElementById('product-field');
            const featureField = document.getElementById('feature-field');
            const variantField = document.getElementById('variant-field');
            const productSelect = document.getElementById('product-select');
            const variantSelect = document.getElementById('variant-select');
            const customProductField = document.getElementById('custom-product-field');
            const customVariantField = document.getElementById('custom-variant-field');
            const vehicleFields = document.getElementById('vehicle-length-fields');
            const ageFields = document.getElementById('age-fields');
            const seasonFields = document.getElementById('season-fields');
            const priceLines = document.getElementById('price-lines');
            const template = document.getElementById('price-line-template');

            function updateSourceFields() {
                const productMode = sourceType.value === 'product';
                productField.classList.toggle('hidden', !productMode);
                variantField.classList.toggle('hidden', !productMode);
                featureField.classList.toggle('hidden', productMode);
                customProductField.classList.toggle('hidden', !productMode);
                customVariantField.classList.toggle('hidden', !productMode);

                if (productMode) updateProductFields();
                else {
                    vehicleFields.classList.add('hidden');
                    ageFields.classList.add('hidden');
                }
            }

            function updateProductFields() {
                const selected = productSelect.options[productSelect.selectedIndex];
                const productId = productSelect.value;
                const selectedVariant = variantSelect.value;

                [...variantSelect.options].forEach(option => {
                    if (!option.value) return;
                    option.hidden = option.dataset.productId !== productId;
                });

                const selectedVariantOption = variantSelect.options[variantSelect.selectedIndex];
                if (selectedVariant && selectedVariantOption && selectedVariantOption.hidden) {
                    variantSelect.value = '';
                }

                customProductField.classList.toggle('hidden', !selected || selected.dataset.slug !== 'other');
                const variantOption = variantSelect.options[variantSelect.selectedIndex];
                customVariantField.classList.toggle('hidden', !variantOption || variantOption.dataset.slug !== 'other');
                vehicleFields.classList.toggle('hidden', !selected || selected.dataset.vehicleLength !== '1');
                ageFields.classList.toggle('hidden', !selected || selected.dataset.ageRange !== '1');
            }

            function updatePeriodFields() {
                const checked = document.querySelector('input[name="period_mode"]:checked');
                seasonFields.classList.toggle('hidden', !checked || checked.value !== 'seasonal');
            }

            function updateLine(line) {
                const status = line.querySelector('.price-status')?.value;
                const paid = status === 'fixed' || status === 'from';
                line.querySelector('.amount-field')?.classList.toggle('hidden', !paid);
                line.querySelector('.currency-field')?.classList.toggle('hidden', !paid);
                line.querySelector('.billing-field')?.classList.toggle('hidden', !paid);
                line.querySelector('.quantity-field')?.classList.toggle('hidden', !paid);
            }

            function reindexLines() {
                [...priceLines.querySelectorAll('.price-line')].forEach((line, index) => {
                    line.querySelectorAll('[data-name]').forEach(input => {
                        input.name = 'lines[' + index + '][' + input.dataset.name + ']';
                    });
                    updateLine(line);
                });
            }

            sourceType.addEventListener('change', updateSourceFields);
            productSelect.addEventListener('change', updateProductFields);
            variantSelect.addEventListener('change', updateProductFields);
            document.querySelectorAll('input[name="period_mode"]').forEach(input => input.addEventListener('change', updatePeriodFields));

            priceLines.addEventListener('change', event => {
                if (event.target.classList.contains('price-status')) {
                    updateLine(event.target.closest('.price-line'));
                }
            });

            priceLines.addEventListener('click', event => {
                const button = event.target.closest('.remove-price-line');
                if (!button) return;
                const lines = priceLines.querySelectorAll('.price-line');
                if (lines.length <= 1) return;
                button.closest('.price-line').remove();
                reindexLines();
            });

            document.getElementById('add-price-line').addEventListener('click', () => {
                if (priceLines.querySelectorAll('.price-line').length >= 10) return;
                const fragment = template.content.cloneNode(true);
                priceLines.appendChild(fragment);
                reindexLines();
            });

            updateSourceFields();
            updatePeriodFields();
            reindexLines();
        })();
    </script>
</x-layouts::app>
