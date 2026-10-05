<x-layouts::app :title="__('feature_workflow.review_title')">
    <div class="mx-auto w-full max-w-6xl px-5 py-8 xl:px-7">
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="mb-2 inline-flex rounded-full border border-amber-400/50 bg-amber-500/10 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:text-amber-300">
                    {{ __('feature_workflow.review_step') }} · {{ __('places.draft.badge') }}
                </div>
                <h1 class="text-2xl font-semibold tracking-tight">{{ __('feature_workflow.review_title') }}</h1>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('feature_workflow.review_intro') }}</p>
            </div>
            <a href="{{ route('places.drafts.features.edit', $place->id) }}" class="rounded-lg border border-zinc-300 px-3 py-2 text-sm font-medium hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800">
                {{ __('feature_workflow.edit_features') }}
            </a>
        </div>
<div class="grid gap-6 lg:grid-cols-2">
            <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-semibold">{{ __('feature_workflow.basic') }}</h2>
                    <a href="{{ route('places.drafts.edit', $place->id) }}" class="text-xs font-medium text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">{{ __('feature_workflow.edit_details') }}</a>
                </div>
                <dl class="mt-4 grid gap-3 text-sm">
                    <div><dt class="text-zinc-500 dark:text-zinc-400">{{ __('places.suggest.name') }}</dt><dd class="font-medium">{{ $place->name }}</dd></div>
                    <div><dt class="text-zinc-500 dark:text-zinc-400">{{ __('places.suggest.latitude') }} / {{ __('places.suggest.longitude') }}</dt><dd>{{ $place->latitude }}, {{ $place->longitude }}</dd></div>
                </dl>
            </section>

            <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold">{{ __('feature_workflow.address') }}</h2>
                <div class="mt-4 text-sm">
                    @if ($address)
                        <div>{{ trim(($address->street ?? '').' '.($address->house_number ?? '')) ?: '—' }}</div>
                        <div>{{ trim(($address->postal_code ?? '').' '.($address->city ?? '')) }}</div>
                        <div>{{ $address->country_code ?? '' }}</div>
                        @if ($address->address_addition)
                            <div class="mt-2 text-zinc-500 dark:text-zinc-400">{{ $address->address_addition }}</div>
                        @endif
                    @else
                        <div class="text-zinc-500 dark:text-zinc-400">—</div>
                    @endif
                </div>
            </section>

            <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900 lg:col-span-2">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-semibold">{{ __('feature_workflow.details') }}</h2>
                    <a href="{{ route('places.drafts.edit', $place->id) }}" class="text-xs font-medium text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">{{ __('feature_workflow.edit_details') }}</a>
                </div>
                <div class="mt-4 grid gap-4 md:grid-cols-2 text-sm">
                    <div>
                        <div class="mb-1 font-medium">{{ __('places.draft.description') }}</div>
                        <div class="whitespace-pre-line text-zinc-600 dark:text-zinc-300">{{ $translation->description ?? '—' }}</div>
                    </div>
                    <div class="grid gap-2">
                        <div><span class="text-zinc-500 dark:text-zinc-400">{{ __('places.draft.operator') }}:</span> {{ $details->operator_name ?? '—' }}</div>
                        <div><span class="text-zinc-500 dark:text-zinc-400">{{ __('places.draft.pitch_count') }}:</span> {{ $details->pitch_count ?? '—' }}</div>
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900 lg:col-span-2">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-semibold">{{ __('feature_workflow.features') }}</h2>
                    <a href="{{ route('places.drafts.features.edit', $place->id) }}" class="text-xs font-medium text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">{{ __('feature_workflow.edit_features') }}</a>
                </div>

                @php
                    $hasFeatureValues = false;
                @endphp
                <div class="mt-4 grid gap-5 md:grid-cols-2">
                    @foreach ($groups as $group)
                        @php
                            $visibleFeatures = $group->features->filter(fn ($feature) => ($feature->status ?? 'unknown') !== 'unknown' || !empty($feature->metadata) || filled($feature->comment));
                        @endphp
                        @if ($visibleFeatures->isNotEmpty())
                            @php $hasFeatureValues = true; @endphp
                            <div>
                                <h3 class="mb-2 text-sm font-semibold text-zinc-500 dark:text-zinc-400">{{ $group->label }}</h3>
                                <div class="space-y-2">
                                    @foreach ($visibleFeatures as $feature)
                                        @php
                                            $statusOption = collect($feature->config['status_options'] ?? [])->firstWhere('value', $feature->status);
                                            $statusLabel = $statusOption ? $workflowService->localized($statusOption['label'] ?? [], app()->getLocale()) : $feature->status;
                                        @endphp
                                        <div class="rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700">
                                            <div class="font-medium">{{ $feature->feature_label }}: {{ $statusLabel }}</div>
                                            @if (!empty($feature->metadata))
                                                <div class="mt-1 space-y-1 text-xs text-zinc-500 dark:text-zinc-400">
                                                    @foreach ($feature->metadata as $key => $value)
                                                        <div>
                                                            <span class="font-medium">{{ str_replace('_', ' ', $key) }}:</span>
                                                            @if (is_array($value))
                                                                {{ collect($value)->filter(fn ($v) => $v !== null && $v !== '')->map(fn ($v, $k) => $k.'='.$v)->implode(', ') }}
                                                            @else
                                                                {{ $value }}
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                            @if ($feature->comment)
                                                <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('feature_workflow.comment_suffix', ['comment' => $feature->comment]) }}</div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
                @if (! $hasFeatureValues)
                    <p class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">{{ __('feature_workflow.no_features') }}</p>
                @endif
            </section>
        </div>

        <form method="POST" action="{{ route('places.drafts.submit', $place->id) }}" class="mt-6 space-y-4 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            @csrf
            <label class="block">
                <span class="mb-1 block text-sm font-medium">{{ __('feature_workflow.moderation_note') }}</span>
                <textarea name="comment" rows="3" maxlength="2000" placeholder="{{ __('feature_workflow.moderation_placeholder') }}" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">{{ old('comment') }}</textarea>
            </label>
            <div class="flex justify-end">
                <button type="submit" class="rounded-lg bg-zinc-900 px-5 py-2 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200">{{ __('feature_workflow.submit') }}</button>
            </div>
        </form>
    </div>
</x-layouts::app>
