@php
    $knownFeatures = $group->features->filter(fn ($feature) => $feature->tone !== 'unknown')->values();
    $unknownFeatures = $group->features->filter(fn ($feature) => $feature->tone === 'unknown')->values();
    $knownCount = $knownFeatures->count();
    $unknownCount = $unknownFeatures->count();
    $totalCount = $group->features->count();
    $displayFeatures = $knownFeatures->concat($unknownFeatures)->values();
    $isExtraEmptyGroup = !($group->is_standard ?? false) && !($group->has_known_features ?? false);
@endphp

<div
    data-profile-feature-group
    data-feature-category
    data-mobile-expanded="0"
    @class([
        'border-t border-zinc-100 pt-1 first:border-t-0 first:pt-0 dark:border-zinc-800/80 md:pt-3 md:first:pt-0',
        'hidden' => $isExtraEmptyGroup,
    ])
    @if($isExtraEmptyGroup) data-extra-feature-group @endif
>
    <div class="mb-2 flex items-start gap-2">
        <button
            type="button"
            data-feature-group-toggle
            class="flex min-w-0 flex-1 items-center justify-between gap-3 py-1 text-left md:min-h-0 md:cursor-default md:py-0"
            aria-expanded="false"
        >
            <span class="truncate text-sm font-semibold text-zinc-600 md:text-xs md:uppercase md:tracking-wide md:text-zinc-400 dark:text-zinc-300 md:dark:text-zinc-400">
                {{ $group->label }}
                <span class="ml-1 text-xs font-normal text-zinc-400">({{ $knownCount }}/{{ $totalCount }})</span>
            </span>
            <span data-feature-group-chevron class="shrink-0 text-base leading-none text-zinc-400 md:hidden">▼</span>
        </button>

        @if ($canDirectEdit || $canSuggest)
            <button
                type="button"
                data-category-edit-toggle
                class="cw-profile-edit-action inline-flex size-7 shrink-0 items-center justify-center rounded text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
                title="{{ __('feature_workflow.edit_category') }}"
                aria-label="{{ __('feature_workflow.edit_category') }}"
                aria-pressed="false"
            >
                <x-tabler-icon name="pencil" class="size-4" />
            </button>
        @endif
    </div>

    <form
        method="POST"
        action="{{ route('places.features.category.update', [$place->slug, $group->id]) }}"
        data-feature-category-form
        data-category-id="{{ $group->id }}"
        data-storage-key="camperwolf.feature-draft.{{ $place->id }}.{{ $group->id }}"
        class="space-y-3"
        novalidate
    >
        @csrf
        @method('PUT')

        <div class="flex flex-wrap items-start gap-1.5" data-category-feature-list>
            @php $unknownHeadingShown = false; @endphp
            @foreach ($displayFeatures as $feature)
                @if ($feature->tone === 'unknown' && ! $unknownHeadingShown)
                    @php $unknownHeadingShown = true; @endphp
                    <div class="mt-2 basis-full pt-1 text-xs text-zinc-500">
                        {{ $unknownCount }} {{ __('feature_workflow.unknown_features') }}:
                    </div>
                @endif

                @php
                    $toneClasses = match ($feature->tone) {
                        'positive' => 'cw-feature-positive',
                        'negative' => 'border-red-500 bg-red-100 text-red-950 hover:bg-red-200 dark:border-red-600 dark:bg-red-950/35 dark:text-red-300 dark:hover:bg-red-950/50',
                        default => 'border-zinc-300 bg-zinc-100 text-zinc-500 hover:bg-zinc-200 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-500 dark:hover:bg-zinc-800',
                    };
                    $editorId = 'feature-editor-'.$feature->feature_id;
                    $featureIcon = $workflowService->iconNameFor($feature);
                @endphp

                <div
                    data-feature-shell
                    data-feature-id="{{ $feature->feature_id }}"
                    data-original-status="{{ $feature->status }}"
                    data-display-status="{{ $feature->display_status ?? $feature->status }}"
                    @if($feature->tone === 'unknown' && !($feature->is_pending ?? false)) data-feature-unknown @endif
                    class="inline-flex max-w-full flex-col rounded-md border transition {{ $toneClasses }}"
                >
                    <div class="flex min-h-8 max-w-full items-center gap-1 px-1.5 text-xs font-medium">
                        <button
                            type="button"
                            data-feature-open="{{ $editorId }}"
                            class="flex min-w-0 items-center gap-1.5 px-0.5 py-1.5 text-left"
                        >
                            <x-tabler-icon :name="$featureIcon" class="size-4 shrink-0" />
                            <span class="truncate">{{ $feature->feature_label }}</span>
                            @if ($feature->detail_summary !== '')
                                <span class="opacity-50">·</span>
                                <span class="max-w-56 truncate font-semibold">{{ $feature->detail_summary }}</span>
                            @endif
                            @if ($feature->tone === 'negative')
                                <x-tabler-icon name="x" class="size-3.5 shrink-0 text-red-500 dark:text-red-400" />
                            @endif
                        </button>

                        @php
                            $externalConflicts = collect($feature->external_feature_conflicts ?? []);
                            $hasFeatureInfo = filled($feature->comment) || $externalConflicts->isNotEmpty();
                        @endphp
                        @if ($hasFeatureInfo)
                            <button
                                type="button"
                                data-feature-info
                                class="relative inline-flex shrink-0 cursor-help items-center outline-none focus:ring-2 focus:ring-zinc-400/40"
                                aria-label="{{ __('place_profile.show_note') }}"
                                aria-expanded="false"
                            >
                                <x-tabler-icon name="info-circle" class="size-3.5 opacity-80" />
                                <span data-feature-tooltip class="pointer-events-none absolute left-1/2 top-6 z-40 hidden w-72 -translate-x-1/2 rounded-lg bg-zinc-950 px-3 py-2 text-left text-xs font-normal leading-5 text-white shadow-xl dark:bg-white dark:text-zinc-950">
                                    @if (filled($feature->comment))
                                        <span class="block">{{ $feature->comment }}</span>
                                    @endif

                                    @foreach ($externalConflicts as $conflict)
                                        @php
                                            $externalStatusLabel = match ($conflict['status'] ?? null) {
                                                'available' => __('place_profile.external_feature_status.available'),
                                                'unavailable' => __('place_profile.external_feature_status.unavailable'),
                                                default => __('place_profile.external_feature_status.unknown'),
                                            };
                                        @endphp
                                        <span @class([
                                            'block',
                                            'mt-2 border-t border-white/20 pt-2 dark:border-zinc-300' => filled($feature->comment) || !$loop->first,
                                        ])>
                                            {{ __('place_profile.external_feature_conflict', [
                                                'source' => $conflict['source_name'] ?? __('place_profile.external_feature_source_unknown'),
                                                'status' => $externalStatusLabel,
                                            ]) }}
                                        </span>
                                    @endforeach
                                </span>
                            </button>
                        @endif

                        @if ($feature->is_pending ?? false)
                            <span class="shrink-0 rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-800 dark:bg-amber-950/50 dark:text-amber-300">
                                {{ __('feature_workflow.pending_short') }}
                            </span>
                        @endif

                        <span data-feature-dirty-indicator class="hidden size-1.5 shrink-0 rounded-full bg-blue-500" title="{{ __('feature_workflow.changed') }}"></span>
                    </div>

                    @if ($canDirectEdit || $canSuggest)
                        <div id="{{ $editorId }}" data-feature-editor class="hidden w-full border-t border-zinc-200 bg-white px-3 py-3 text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
                            <input
                                type="hidden"
                                data-feature-status
                                name="features[{{ $feature->feature_id }}][status]"
                                value="{{ $feature->status }}"
                            >

                            <div class="flex flex-wrap gap-2" data-status-choices>
                                @foreach ($feature->config['status_options'] ?? [] as $statusOption)
                                    @php
                                        $statusValue = $statusOption['value'];
                                        $selected = $feature->status === $statusValue;
                                    @endphp
                                    <button
                                        type="button"
                                        data-status-choice="{{ $statusValue }}"
                                        @class([
                                            'rounded-lg border px-3 py-2 text-xs font-semibold transition',
                                            'border-zinc-900 bg-zinc-900 text-white dark:border-white dark:bg-white dark:text-zinc-950' => $selected,
                                            'border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-200 dark:hover:bg-zinc-800' => ! $selected,
                                        ])
                                    >
                                        {{ $workflowService->localized($statusOption['label'] ?? [], $locale) }}
                                    </button>
                                @endforeach
                            </div>

                            <div class="mt-3 flex flex-wrap items-end gap-3" data-feature-details>
                                @foreach ($feature->config['details'] ?? [] as $detail)
                                    @php
                                        $detailKey = $detail['key'];
                                        $detailValue = $feature->metadata[$detailKey] ?? null;
                                        $showFor = implode(',', $detail['show_for'] ?? []);
                                        $detailLabel = $workflowService->localized($detail['label'] ?? [], $locale);
                                    @endphp

                                    <div data-show-for="{{ $showFor }}" class="flex flex-wrap items-end gap-2">
                                        @if (($detail['type'] ?? null) === 'number')
                                            <label class="block">
                                                <span class="mb-1 block text-xs font-medium text-zinc-500">{{ $detailLabel }}</span>
                                                <div class="flex items-center gap-1.5">
                                                    <input type="number" step="any" min="0" name="features[{{ $feature->feature_id }}][details][{{ $detailKey }}]" value="{{ $detailValue }}" class="h-9 w-28 rounded-md border border-zinc-300 bg-white px-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                                    <span class="text-sm text-zinc-500">{{ $detail['unit'] ?? '' }}</span>
                                                </div>
                                            </label>
                                        @elseif (($detail['type'] ?? null) === 'select')
                                            <label class="block">
                                                <span class="mb-1 block text-xs font-medium text-zinc-500">{{ $detailLabel }}</span>
                                                <select name="features[{{ $feature->feature_id }}][details][{{ $detailKey }}]" class="h-9 w-auto min-w-40 rounded-md border border-zinc-300 bg-white px-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                                    <option value="">–</option>
                                                    @foreach ($detail['options'] ?? [] as $option)
                                                        <option value="{{ $option['value'] }}" @selected((string) $detailValue === (string) $option['value'])>{{ $workflowService->localized($option['label'] ?? [], $locale) }}</option>
                                                    @endforeach
                                                </select>
                                            </label>
                                        @elseif (($detail['type'] ?? null) === 'number_unit')
                                            <label class="block">
                                                <span class="mb-1 block text-xs font-medium text-zinc-500">{{ $detailLabel }}</span>
                                                <div class="flex gap-2">
                                                    <input type="number" step="any" min="0" name="features[{{ $feature->feature_id }}][details][{{ $detailKey }}][value]" value="{{ is_array($detailValue) ? ($detailValue['value'] ?? '') : '' }}" class="h-9 w-24 rounded-md border border-zinc-300 bg-white px-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                                    <select name="features[{{ $feature->feature_id }}][details][{{ $detailKey }}][unit]" class="h-9 w-auto rounded-md border border-zinc-300 bg-white px-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                                        @foreach ($detail['units'] ?? [] as $unit)
                                                            <option value="{{ $unit['value'] }}" @selected(is_array($detailValue) && ($detailValue['unit'] ?? null) === $unit['value'])>{{ $workflowService->localized($unit['label'] ?? [], $locale) }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </label>
                                        @elseif (in_array(($detail['type'] ?? null), ['pricing', 'money'], true))
                                            @php
                                                $priceValue = is_array($detailValue) ? $detailValue : [];
                                                $priceStatus = $detail['type'] === 'money' ? 'paid' : ($priceValue['status'] ?? 'unknown');
                                            @endphp
                                            <div data-pricing class="flex flex-wrap items-end gap-2">
                                                @if ($detail['type'] === 'pricing')
                                                    <label class="block">
                                                        <span class="mb-1 block text-xs font-medium text-zinc-500">{{ $detailLabel }}</span>
                                                        <select data-pricing-status name="features[{{ $feature->feature_id }}][details][{{ $detailKey }}][status]" class="h-9 w-auto rounded-md border border-zinc-300 bg-white px-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                                            <option value="unknown" @selected($priceStatus === 'unknown')>{{ __('place_profile.price_status.unknown') }}</option>
                                                            <option value="free" @selected($priceStatus === 'free')>{{ __('place_profile.price_status.free') }}</option>
                                                            <option value="paid" @selected($priceStatus === 'paid')>{{ __('place_profile.price_status.paid') }}</option>
                                                        </select>
                                                    </label>
                                                @endif

                                                <div data-paid-fields class="flex flex-wrap items-end gap-2">
                                                    <label class="block">
                                                        <span class="mb-1 block text-xs font-medium text-zinc-500">{{ __('place_profile.amount') }}</span>
                                                        <div class="flex items-center gap-1.5">
                                                            <input type="number" step="0.01" min="0" name="features[{{ $feature->feature_id }}][details][{{ $detailKey }}][amount]" value="{{ $priceValue['amount'] ?? '' }}" class="h-9 w-24 rounded-md border border-zinc-300 bg-white px-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                                            <span class="text-sm text-zinc-500">€</span>
                                                            <input type="hidden" name="features[{{ $feature->feature_id }}][details][{{ $detailKey }}][currency]" value="EUR">
                                                        </div>
                                                    </label>
                                                    <label class="block">
                                                        <span class="mb-1 block text-xs font-medium text-zinc-500">{{ __('place_profile.per') }}</span>
                                                        <input type="number" step="any" min="0.01" name="features[{{ $feature->feature_id }}][details][{{ $detailKey }}][quantity]" value="{{ $priceValue['quantity'] ?? 1 }}" class="h-9 w-20 rounded-md border border-zinc-300 bg-white px-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                                    </label>
                                                    <label class="block">
                                                        <span class="mb-1 block text-xs font-medium text-zinc-500">{{ __('place_profile.unit') }}</span>
                                                        <select name="features[{{ $feature->feature_id }}][details][{{ $detailKey }}][unit]" class="h-9 w-auto rounded-md border border-zinc-300 bg-white px-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                                            @foreach ($detail['units'] ?? [] as $unit)
                                                                <option value="{{ $unit['value'] }}" @selected(($priceValue['unit'] ?? null) === $unit['value'])>{{ $workflowService->localized($unit['label'] ?? [], $locale) }}</option>
                                                            @endforeach
                                                        </select>
                                                    </label>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            <label class="mt-3 block max-w-2xl">
                                <span class="mb-1 block text-xs font-medium text-zinc-500">{{ __('place_profile.additional_note') }}</span>
                                <input
                                    type="text"
                                    name="features[{{ $feature->feature_id }}][comment]"
                                    value="{{ $feature->comment }}"
                                    maxlength="1000"
                                    class="h-9 w-full rounded-md border border-zinc-300 bg-white px-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                                    placeholder="{{ __('place_profile.optional_text') }}"
                                >
                            </label>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        @if ($canDirectEdit || $canSuggest)
            <div data-category-actions class="hidden items-center justify-between gap-3 border-t border-zinc-200 pt-3 dark:border-zinc-800">
                <div class="text-xs text-zinc-500" data-category-change-count></div>
                <div class="flex items-center gap-2">
                    <button type="button" data-category-discard class="rounded-lg px-3 py-2 text-xs font-medium text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800">
                        {{ __('feature_workflow.discard_changes') }}
                    </button>
                    <button type="submit" data-category-submit class="rounded-lg bg-zinc-900 px-4 py-2 text-xs font-semibold text-white hover:bg-zinc-700 disabled:cursor-not-allowed disabled:opacity-40 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200" disabled>
                        {{ $canDirectEdit ? __('feature_workflow.save_category') : __('feature_workflow.submit_category') }}
                    </button>
                </div>
            </div>
        @endif
    </form>
</div>
