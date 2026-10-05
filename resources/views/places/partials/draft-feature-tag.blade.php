@php
    $featureStatus = old("features.{$feature->feature_id}.status", $feature->status ?? 'unknown');
    $featureComment = old("features.{$feature->feature_id}.comment", $feature->comment ?? '');
    $config = $feature->config;
    $details = $config['details'] ?? [];
    $featureIcon = $workflowService->iconNameFor($feature);
    $tone = in_array($featureStatus, ['no', 'unavailable'], true)
        ? 'negative'
        : ($featureStatus === 'unknown' ? 'unknown' : 'positive');
    $editorId = 'draft-feature-editor-'.$feature->feature_id;
@endphp

<div
    data-feature-shell
    data-feature-id="{{ $feature->feature_id }}"
    data-tone="{{ $tone }}"
    class="cw-feature inline-flex max-w-full flex-col rounded-md border transition"
>
    <div class="flex h-8 max-w-full items-center gap-1.5 px-2 text-xs font-medium">
        <x-tabler-icon :name="$featureIcon" class="size-4 shrink-0" />
        <span class="truncate">{{ $feature->feature_label }}</span>

        <x-tabler-icon
            name="x"
            data-negative-icon
            class="size-3.5 shrink-0 text-red-500 dark:text-red-400 {{ $tone === 'negative' ? '' : 'hidden' }}"
        />

        <button
            type="button"
            data-feature-toggle="{{ $editorId }}"
            class="ml-0.5 inline-flex size-5 shrink-0 items-center justify-center rounded text-current/60 hover:bg-black/5 hover:text-current dark:hover:bg-white/10"
            title="{{ __('feature_workflow.edit_feature') }}"
            aria-label="{{ __('feature_workflow.edit_feature') }}"
        >
            <x-tabler-icon name="pencil" class="size-3.5" />
        </button>
    </div>

    <div
        id="{{ $editorId }}"
        data-feature-editor
        class="hidden w-full border-t border-zinc-200 bg-white px-3 py-4 text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
    >
        <div class="space-y-4">
            <div class="flex flex-wrap items-end gap-3">
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-zinc-500">{{ __('feature_workflow.status') }}</span>
                    <select
                        data-feature-status
                        name="features[{{ $feature->feature_id }}][status]"
                        class="h-9 w-auto min-w-40 rounded-md border border-zinc-300 bg-white px-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                    >
                        @foreach ($config['status_options'] ?? [] as $statusOption)
                            <option value="{{ $statusOption['value'] }}" @selected($featureStatus === $statusOption['value'])>
                                {{ $workflowService->localized($statusOption['label'] ?? [], app()->getLocale()) }}
                            </option>
                        @endforeach
                    </select>
                </label>

                @foreach ($details as $detail)
                    @php
                        $detailKey = $detail['key'];
                        $detailValue = old("features.{$feature->feature_id}.details.{$detailKey}", $feature->metadata[$detailKey] ?? null);
                        $showFor = implode(',', $detail['show_for'] ?? []);
                        $detailLabel = $workflowService->localized($detail['label'] ?? [], app()->getLocale());
                    @endphp

                    <div data-show-for="{{ $showFor }}" class="flex flex-wrap items-end gap-2">
                        @if (($detail['type'] ?? null) === 'number')
                            <label class="block">
                                <span class="mb-1 block text-xs font-medium text-zinc-500">{{ $detailLabel }}</span>
                                <div class="flex items-center gap-1.5">
                                    <input type="number" step="any" min="0"
                                        name="features[{{ $feature->feature_id }}][details][{{ $detailKey }}]"
                                        value="{{ is_array($detailValue) ? '' : $detailValue }}"
                                        class="h-9 w-28 rounded-md border border-zinc-300 bg-white px-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                    <span class="text-sm text-zinc-500">{{ $detail['unit'] ?? '' }}</span>
                                </div>
                            </label>
                        @elseif (($detail['type'] ?? null) === 'select')
                            <label class="block">
                                <span class="mb-1 block text-xs font-medium text-zinc-500">{{ $detailLabel }}</span>
                                <select name="features[{{ $feature->feature_id }}][details][{{ $detailKey }}]"
                                    class="h-9 w-auto min-w-40 rounded-md border border-zinc-300 bg-white px-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                    <option value="">–</option>
                                    @foreach ($detail['options'] ?? [] as $option)
                                        <option value="{{ $option['value'] }}" @selected((string) $detailValue === (string) $option['value'])>
                                            {{ $workflowService->localized($option['label'] ?? [], app()->getLocale()) }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>
                        @elseif (($detail['type'] ?? null) === 'number_unit')
                            <label class="block">
                                <span class="mb-1 block text-xs font-medium text-zinc-500">{{ $detailLabel }}</span>
                                <div class="flex gap-2">
                                    <input type="number" step="any" min="0"
                                        name="features[{{ $feature->feature_id }}][details][{{ $detailKey }}][value]"
                                        value="{{ is_array($detailValue) ? ($detailValue['value'] ?? '') : '' }}"
                                        class="h-9 w-24 rounded-md border border-zinc-300 bg-white px-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                    <select name="features[{{ $feature->feature_id }}][details][{{ $detailKey }}][unit]"
                                        class="h-9 w-auto rounded-md border border-zinc-300 bg-white px-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                        @foreach ($detail['units'] ?? [] as $unit)
                                            <option value="{{ $unit['value'] }}" @selected(is_array($detailValue) && ($detailValue['unit'] ?? null) === $unit['value'])>
                                                {{ $workflowService->localized($unit['label'] ?? [], app()->getLocale()) }}
                                            </option>
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
                                        <select data-pricing-status
                                            name="features[{{ $feature->feature_id }}][details][{{ $detailKey }}][status]"
                                            class="h-9 w-auto rounded-md border border-zinc-300 bg-white px-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                            <option value="unknown" @selected($priceStatus === 'unknown')>{{ __('feature_workflow.price_unknown') }}</option>
                                            <option value="free" @selected($priceStatus === 'free')>{{ __('feature_workflow.price_free') }}</option>
                                            <option value="paid" @selected($priceStatus === 'paid')>{{ __('feature_workflow.price_paid') }}</option>
                                        </select>
                                    </label>
                                @endif

                                <div data-paid-fields class="flex flex-wrap items-end gap-2">
                                    <label class="block">
                                        <span class="mb-1 block text-xs font-medium text-zinc-500">{{ __('feature_workflow.amount') }}</span>
                                        <div class="flex items-center gap-1.5">
                                            <input type="number" step="0.01" min="0"
                                                name="features[{{ $feature->feature_id }}][details][{{ $detailKey }}][amount]"
                                                value="{{ $priceValue['amount'] ?? '' }}"
                                                class="h-9 w-24 rounded-md border border-zinc-300 bg-white px-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                            <span class="text-sm text-zinc-500">€</span>
                                            <input type="hidden" name="features[{{ $feature->feature_id }}][details][{{ $detailKey }}][currency]" value="EUR">
                                        </div>
                                    </label>

                                    <label class="block">
                                        <span class="mb-1 block text-xs font-medium text-zinc-500">{{ __('feature_workflow.quantity') }}</span>
                                        <input type="number" step="any" min="0.01"
                                            name="features[{{ $feature->feature_id }}][details][{{ $detailKey }}][quantity]"
                                            value="{{ $priceValue['quantity'] ?? 1 }}"
                                            class="h-9 w-20 rounded-md border border-zinc-300 bg-white px-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                    </label>

                                    <label class="block">
                                        <span class="mb-1 block text-xs font-medium text-zinc-500">{{ __('feature_workflow.unit') }}</span>
                                        <select name="features[{{ $feature->feature_id }}][details][{{ $detailKey }}][unit]"
                                            class="h-9 w-auto rounded-md border border-zinc-300 bg-white px-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                            @foreach ($detail['units'] ?? [] as $unit)
                                                <option value="{{ $unit['value'] }}" @selected(($priceValue['unit'] ?? null) === $unit['value'])>
                                                    {{ $workflowService->localized($unit['label'] ?? [], app()->getLocale()) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </label>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <label class="block max-w-2xl">
                <span class="mb-1 block text-xs font-medium text-zinc-500">{{ __('feature_workflow.comment') }}</span>
                <textarea name="features[{{ $feature->feature_id }}][comment]" rows="2" maxlength="1000"
                    class="w-full rounded-md border border-zinc-300 bg-white px-2.5 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                    placeholder="{{ __('feature_workflow.comment_placeholder') }}">{{ $featureComment }}</textarea>
            </label>

            <div class="flex justify-end">
                <button type="button" data-feature-toggle="{{ $editorId }}"
                    class="rounded-md px-3 py-2 text-xs font-medium text-zinc-500 hover:bg-zinc-200/70 dark:hover:bg-zinc-800">
                    {{ __('feature_workflow.close_feature') }}
                </button>
            </div>
        </div>
    </div>
</div>
