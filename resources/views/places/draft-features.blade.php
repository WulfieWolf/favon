@push('styles')
    <style>
        .cw-feature[data-tone="positive"] {
            border-color: rgb(16 185 129);
            background: rgb(209 250 229);
            color: rgb(6 78 59);
        }
        .cw-feature[data-tone="positive"]:hover {
            background: rgb(167 243 208);
        }
        .dark .cw-feature[data-tone="positive"] {
            border-color: rgb(5 150 105);
            background: rgba(6, 78, 59, .38);
            color: rgb(167 243 208);
        }
        .dark .cw-feature[data-tone="positive"]:hover {
            background: rgba(6, 78, 59, .52);
        }

        .cw-feature[data-tone="negative"] {
            border-color: rgb(239 68 68);
            background: rgb(254 226 226);
            color: rgb(69 10 10);
        }
        .cw-feature[data-tone="negative"]:hover {
            background: rgb(254 202 202);
        }
        .dark .cw-feature[data-tone="negative"] {
            border-color: rgb(185 28 28);
            background: rgba(69, 10, 10, .35);
            color: rgb(252 165 165);
        }
        .dark .cw-feature[data-tone="negative"]:hover {
            background: rgba(69, 10, 10, .50);
        }

        .cw-feature[data-tone="unknown"] {
            border-color: rgb(212 212 216);
            background: rgb(244 244 245);
            color: rgb(113 113 122);
        }
        .cw-feature[data-tone="unknown"]:hover {
            background: rgb(228 228 231);
        }
        .dark .cw-feature[data-tone="unknown"] {
            border-color: rgb(63 63 70);
            background: rgb(24 24 27);
            color: rgb(113 113 122);
        }
        .dark .cw-feature[data-tone="unknown"]:hover {
            background: rgb(39 39 42);
        }
    </style>
@endpush

@push('scripts')
    <script>
        (() => {
            const toneForStatus = (status) => {
                if (status === 'unknown') return 'unknown';
                if (status === 'no' || status === 'unavailable') return 'negative';
                return 'positive';
            };

            const refreshEditor = (editor) => {
                const status = editor.querySelector('[data-feature-status]')?.value ?? 'unknown';

                editor.querySelectorAll('[data-show-for]').forEach((row) => {
                    const allowed = (row.dataset.showFor || '').split(',').filter(Boolean);
                    row.classList.toggle('hidden', !allowed.includes(status));
                });

                editor.querySelectorAll('[data-pricing-status]').forEach((pricingStatus) => {
                    const pricingBox = pricingStatus.closest('[data-pricing]');
                    pricingBox?.querySelector('[data-paid-fields]')
                        ?.classList.toggle('hidden', pricingStatus.value !== 'paid');
                });
            };

            const refreshGroup = (group) => {
                const knownContainer = group.querySelector('[data-known-features]');
                const unknownContainer = group.querySelector('[data-unknown-features]');
                const emptyKnown = group.querySelector('[data-known-empty]');
                const unknownBlock = group.querySelector('[data-unknown-block]');
                const unknownCount = group.querySelector('[data-unknown-count]');

                const known = knownContainer?.querySelectorAll(':scope > [data-feature-shell]').length ?? 0;
                const unknown = unknownContainer?.querySelectorAll(':scope > [data-feature-shell]').length ?? 0;

                emptyKnown?.classList.toggle('hidden', known !== 0);
                unknownBlock?.classList.toggle('hidden', unknown === 0);
                if (unknownCount) unknownCount.textContent = unknown;
            };

            const placeShell = (shell) => {
                const group = shell.closest('[data-feature-group]');
                if (!group) return;

                const target = shell.dataset.tone === 'unknown'
                    ? group.querySelector('[data-unknown-features]')
                    : group.querySelector('[data-known-features]');

                if (target && shell.parentElement !== target) {
                    target.appendChild(shell);
                }

                refreshGroup(group);
            };

            const updateShellTone = (shell) => {
                const editor = shell.querySelector('[data-feature-editor]');
                const status = editor?.querySelector('[data-feature-status]')?.value ?? 'unknown';
                const tone = toneForStatus(status);

                shell.dataset.tone = tone;
                shell.querySelector('[data-negative-icon]')?.classList.toggle('hidden', tone !== 'negative');
            };

            const initFeatureDraft = () => {
                document.querySelectorAll('[data-feature-editor]').forEach((editor) => {
                    const shell = editor.closest('[data-feature-shell]');
                    const status = editor.querySelector('[data-feature-status]');

                    const refresh = () => {
                        refreshEditor(editor);
                        if (shell) updateShellTone(shell);
                    };

                    status?.addEventListener('change', refresh);
                    editor.querySelectorAll('[data-pricing-status]').forEach((pricingStatus) => {
                        pricingStatus.addEventListener('change', () => refreshEditor(editor));
                    });

                    refresh();
                });

                document.querySelectorAll('[data-feature-toggle]').forEach((button) => {
                    if (button.dataset.bound) return;
                    button.dataset.bound = '1';

                    button.addEventListener('click', () => {
                        const target = document.getElementById(button.dataset.featureToggle);
                        if (!target) return;

                        const shell = target.closest('[data-feature-shell]');
                        const willOpen = target.classList.contains('hidden');

                        target.classList.toggle('hidden', !willOpen);
                        shell?.classList.toggle('basis-full', willOpen);
                        shell?.classList.toggle('w-full', willOpen);

                        if (willOpen) {
                            target.querySelector('select, input, textarea')?.focus();
                        } else if (shell) {
                            updateShellTone(shell);
                            placeShell(shell);
                        }
                    });
                });

                document.querySelectorAll('[data-feature-group]').forEach(refreshGroup);
            };

            document.addEventListener('DOMContentLoaded', initFeatureDraft, { once: true });
            document.addEventListener('livewire:navigated', initFeatureDraft);
        })();
    </script>
@endpush

<x-layouts::app :title="__('feature_workflow.title')">
    @php
        $workflowService = app(\App\Services\FeatureWorkflowService::class);
    @endphp

    <div class="mx-auto w-full max-w-6xl px-5 py-8 xl:px-7">
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="mb-2 inline-flex rounded-full border border-amber-400/50 bg-amber-500/10 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:text-amber-300">
                    {{ __('feature_workflow.step') }} · {{ __('places.draft.badge') }}
                </div>
                <h1 class="text-2xl font-semibold tracking-tight">{{ __('feature_workflow.title') }}</h1>
                <p class="mt-1 max-w-3xl text-sm text-zinc-500 dark:text-zinc-400">{{ __('feature_workflow.intro_compact') }}</p>
            </div>

            <a href="{{ route('dashboard') }}"
                class="rounded-lg border border-zinc-300 px-3 py-2 text-sm font-medium hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800">
                {{ __('places.common.back_overview') }}
            </a>
        </div>
<form method="POST" action="{{ route('places.drafts.features.update', $place->id) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="mb-4">
                    <h2 class="text-lg font-semibold">
                        {{ __('feature_workflow.features') }}
                        <span class="text-sm font-normal text-zinc-500 dark:text-zinc-400">(je +{{ config('xp.place_info.default_xp', 1) }} XP)</span>
                    </h2>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('feature_workflow.local_changes_hint') }}</p>
                </div>

                @if ($groups->isEmpty())
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('feature_workflow.no_quick_features') }}</p>
                @else
                <div class="space-y-4">
                    @foreach ($groups as $group)
                        @php
                            $knownFeatures = $group->features->filter(function ($feature) {
                                $status = old("features.{$feature->feature_id}.status", $feature->status ?? 'unknown');
                                return $status !== 'unknown';
                            })->values();

                            $unknownFeatures = $group->features->filter(function ($feature) {
                                $status = old("features.{$feature->feature_id}.status", $feature->status ?? 'unknown');
                                return $status === 'unknown';
                            })->values();
                        @endphp

                        <div data-feature-group class="border-t border-zinc-100 pt-3 first:border-t-0 first:pt-0 dark:border-zinc-800/80">
                            <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-400">{{ $group->label }}</h3>

                            <p data-known-empty class="mb-2 text-xs text-zinc-500 {{ $knownFeatures->isEmpty() ? '' : 'hidden' }}">
                                {{ __('feature_workflow.no_known_features') }}
                            </p>

                            <div data-known-features class="flex flex-wrap items-start gap-1.5">
                                @foreach ($knownFeatures as $feature)
                                    @include('places.partials.draft-feature-tag', ['feature' => $feature, 'workflowService' => $workflowService])
                                @endforeach
                            </div>

                            <div data-unknown-block class="mt-2.5 {{ $unknownFeatures->isEmpty() ? 'hidden' : '' }}">
                                <div class="mb-1.5 text-xs text-zinc-500">
                                    <span data-unknown-count>{{ $unknownFeatures->count() }}</span> {{ __('feature_workflow.unknown_features') }}:
                                </div>

                                <div data-unknown-features class="flex flex-wrap items-start gap-1.5">
                                    @foreach ($unknownFeatures as $feature)
                                        @include('places.partials.draft-feature-tag', ['feature' => $feature, 'workflowService' => $workflowService])
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                @endif
            </section>

            <div class="flex flex-wrap justify-between gap-3">
                <a href="{{ route('dashboard') }}"
                    class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800">
                    {{ __('places.common.cancel') }}
                </a>

                <div class="flex flex-wrap gap-3">
                    <button type="submit" name="intent" value="save"
                        class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-semibold hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800">
                        {{ __('feature_workflow.save') }}
                    </button>
                    <button type="submit" name="intent" value="submit"
                        class="rounded-lg bg-zinc-900 px-5 py-2 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200">
                        {{ $canPublishDirectly ? __('feature_workflow.publish_direct') : __('feature_workflow.continue') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</x-layouts::app>
