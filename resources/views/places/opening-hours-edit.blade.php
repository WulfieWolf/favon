<x-layouts::app :title="__('place_editing.opening_hours.page_title').' · '.$place->name">
    <div class="mx-auto w-full max-w-6xl px-5 py-8 xl:px-7">
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="text-sm font-medium text-zinc-500">
                    {{ $canDirectEdit ? __('place_editing.opening_hours.edit') : __('place_editing.opening_hours.suggest') }}
                </div>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight">{{ $place->name }}</h1>
                <p class="mt-1 text-sm text-zinc-500">
                    {{ __('place_editing.opening_hours.intro') }}
                </p>
            </div>
            <a href="{{ route('places.show', $place->slug) }}" class="rounded-lg border border-zinc-300 px-3 py-2 text-sm font-medium hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800">
                {{ __('place_editing.common.back_to_place') }}
            </a>
        </div>
@if ($overlapPreview)
            <div class="mb-6 rounded-xl border border-amber-300 bg-amber-50 p-5 text-sm text-amber-950 dark:border-amber-800 dark:bg-amber-950/25 dark:text-amber-100">
                <div class="font-semibold">{{ __('place_editing.opening_hours.overlap_title', ['range' => $overlapPreview['range']]) }}</div>
                <p class="mt-1">{{ __('place_editing.opening_hours.overlap_help') }}</p>

                <div class="mt-3 space-y-2">
                    @foreach ($overlapPreview['affected'] as $affected)
                        <div class="rounded-lg border border-amber-200 bg-white/60 px-3 py-2 dark:border-amber-900 dark:bg-black/10">
                            <div class="font-medium">{{ $affected['label'] }}</div>
                            @if ($affected['remainders'] === [])
                                <div class="mt-0.5 text-xs opacity-80">{{ __('place_editing.opening_hours.fully_replaced') }}</div>
                            @else
                                <div class="mt-0.5 text-xs opacity-80">
                                    {{ __('place_editing.opening_hours.remains_as', ['ranges' => implode(__('place_editing.opening_hours.range_joiner'), $affected['remainders'])]) }}
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[320px_minmax(0,1fr)]">
            <aside class="space-y-4">
                <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-base font-semibold">{{ __('place_editing.opening_hours.existing_periods') }}</h2>
                        <a
                            href="{{ route('places.opening-hours.edit', $place->slug) }}"
                            class="inline-flex size-7 items-center justify-center rounded text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
                            title="{{ __('place_editing.opening_hours.add_period') }}"
                            aria-label="{{ __('place_editing.opening_hours.add_period') }}"
                        >
                            <x-tabler-icon name="plus" class="size-4" />
                        </a>
                    </div>

                    @if ($periods->isEmpty())
                        <p class="mt-3 text-sm italic text-zinc-400">{{ __('place_editing.opening_hours.no_periods') }}</p>
                    @else
                        <div class="mt-3 space-y-2">
                            @foreach ($periods as $period)
                                <a
                                    href="{{ route('places.opening-hours.edit', ['slug' => $place->slug, 'period' => $period->id]) }}"
                                    class="block rounded-lg border px-3 py-2 text-sm transition
                                        {{ $editingPeriod && (int) $editingPeriod->id === (int) $period->id
                                            ? 'border-zinc-900 bg-zinc-100 dark:border-zinc-100 dark:bg-zinc-800'
                                            : 'border-zinc-200 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800' }}"
                                >
                                    <div class="font-medium">{{ $period->label }}</div>
                                    <div class="mt-0.5 text-xs text-zinc-400">
                                        {{ trans_choice('place_editing.opening_hours.time_entries', $period->hours->count(), ['count' => $period->hours->count()]) }}
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </section>

                <section class="rounded-xl border border-zinc-200 bg-white p-5 text-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <h2 class="font-semibold">{{ __('place_editing.opening_hours.overlaps') }}</h2>
                    <p class="mt-2 leading-6 text-zinc-500">
                        {{ __('place_editing.opening_hours.overlaps_help') }}
                    </p>
                </section>
            </aside>

            <form method="POST" action="{{ route('places.opening-hours.update', $place->slug) }}" class="space-y-6">
                @csrf
                @method('PUT')

                @if ($editingPeriod)
                    <input type="hidden" name="period_id" value="{{ $editingPeriod->id }}">
                @endif

                @if ($overlapPreview)
                    <input type="hidden" name="confirm_overlap" value="1">
                @endif

                <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold">
                                {{ $editingPeriod ? __('place_editing.opening_hours.edit_period') : __('place_editing.opening_hours.add_period') }}
                            </h2>
                            <p class="mt-1 text-sm text-zinc-500">
                                {{ __('place_editing.opening_hours.season_help') }}
                            </p>
                        </div>
                    </div>

                    @php
                        $mode = old('period_mode', $periodMode);
                    @endphp

                    <div class="mt-4 grid gap-4 md:grid-cols-[220px_1fr_1fr]" data-period-mode-shell>
                        <label class="block">
                            <span class="mb-1 block text-sm font-medium">{{ __('place_editing.opening_hours.period') }}</span>
                            <select
                                name="period_mode"
                                data-period-mode
                                class="h-10 w-full rounded-lg border border-zinc-300 bg-white px-3 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                            >
                                <option value="year_round" @selected($mode === 'year_round')>{{ __('place_editing.opening_hours.year_round') }}</option>
                                <option value="seasonal" @selected($mode === 'seasonal')>{{ __('place_editing.opening_hours.seasonal') }}</option>
                            </select>
                        </label>

                        <label class="block {{ $mode === 'seasonal' ? '' : 'hidden' }}" data-season-field>
                            <span class="mb-1 block text-sm font-medium">{{ __('place_editing.common.from') }}</span>
                            <input
                                type="text"
                                name="start_md"
                                value="{{ old('start_md', $startMd) }}"
                                placeholder="{{ __('place_editing.opening_hours.date_placeholder') }}"
                                maxlength="6"
                                class="h-10 w-full rounded-lg border border-zinc-300 bg-white px-3 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                            >
                        </label>

                        <label class="block {{ $mode === 'seasonal' ? '' : 'hidden' }}" data-season-field>
                            <span class="mb-1 block text-sm font-medium">{{ __('place_editing.common.until') }}</span>
                            <input
                                type="text"
                                name="end_md"
                                value="{{ old('end_md', $endMd) }}"
                                placeholder="{{ __('place_editing.opening_hours.date_placeholder') }}"
                                maxlength="6"
                                class="h-10 w-full rounded-lg border border-zinc-300 bg-white px-3 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                            >
                        </label>
                    </div>
                </section>

                @php
                    $labels = __('place_editing.opening_hours.days');
                @endphp

                <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="flex cursor-pointer items-start gap-3">
                            <input type="checkbox" data-opening-247 class="mt-1 size-4 rounded border-zinc-300">
                            <span>
                                <span class="block font-semibold">{{ __('place_editing.opening_hours.open_247') }}</span>
                                <span class="mt-1 block text-sm text-zinc-500">{{ __('place_editing.opening_hours.open_247_help') }}</span>
                            </span>
                        </label>

                        <label class="flex cursor-pointer items-start gap-3">
                            <input type="checkbox" data-opening-closed class="mt-1 size-4 rounded border-zinc-300">
                            <span>
                                <span class="block font-semibold">{{ __('place_editing.opening_hours.closed_every_day') }}</span>
                                <span class="mt-1 block text-sm text-zinc-500">{{ __('place_editing.opening_hours.closed_every_day_help') }}</span>
                            </span>
                        </label>
                    </div>
                </section>

                <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($labels as $dayKey => $label)
                            @php
                                $day = old("days.{$dayKey}", $days[$dayKey]);
                                $dayMode = $day['mode'] ?? 'unknown';
                            @endphp
                            <div class="grid gap-4 p-5 xl:grid-cols-[140px_210px_minmax(0,1fr)] xl:items-center" data-opening-day data-day-key="{{ $dayKey }}">
                                <div>
                                    <div class="font-semibold">{{ $label }}</div>
                                    @if ((string) $dayKey === '1')
                                        <button
                                            type="button"
                                            data-copy-weekdays
                                            class="mt-2 text-left text-xs font-medium text-zinc-500 underline decoration-zinc-300 underline-offset-2 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-100"
                                        >
                                            {{ __('place_editing.opening_hours.copy_to_weekdays') }}
                                        </button>
                                    @elseif ($dayKey === 'holiday')
                                        <div class="mt-1 text-xs text-zinc-400">{{ __('place_editing.opening_hours.holiday_rule') }}</div>
                                    @endif
                                </div>

                                <label class="block">
                                    <span class="mb-1 block text-xs font-medium text-zinc-500">{{ __('place_editing.common.status') }}</span>
                                    <select
                                        name="days[{{ $dayKey }}][mode]"
                                        data-opening-mode
                                        class="h-10 w-full rounded-lg border border-zinc-300 bg-white px-3 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                                    >
                                        @foreach (__('place_editing.opening_hours.modes') as $value => $label)
                                            <option value="{{ $value }}" @selected($dayMode === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </label>

                                <div data-opening-times class="{{ $dayMode === 'hours' ? '' : 'hidden' }}">
                                    <div class="grid gap-3 sm:grid-cols-2 2xl:grid-cols-4">
                                        <label class="block">
                                            <span class="mb-1 block text-xs font-medium text-zinc-500">{{ __('place_editing.common.from') }}</span>
                                            <input type="time" name="days[{{ $dayKey }}][opens_at]" value="{{ $day['opens_at'] ?? '' }}" class="h-10 w-full rounded-lg border border-zinc-300 bg-white px-3 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                        </label>
                                        <label class="block">
                                            <span class="mb-1 block text-xs font-medium text-zinc-500">{{ __('place_editing.common.until') }}</span>
                                            <input type="time" name="days[{{ $dayKey }}][closes_at]" value="{{ $day['closes_at'] ?? '' }}" class="h-10 w-full rounded-lg border border-zinc-300 bg-white px-3 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                        </label>
                                        <label class="block">
                                            <span class="mb-1 block text-xs font-medium text-zinc-500">{{ __('place_editing.opening_hours.second_from') }} <span class="font-normal text-zinc-400">({{ __('place_editing.common.optional') }})</span></span>
                                            <input type="time" name="days[{{ $dayKey }}][opens_at_2]" value="{{ $day['opens_at_2'] ?? '' }}" class="h-10 w-full rounded-lg border border-zinc-300 bg-white px-3 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                        </label>
                                        <label class="block">
                                            <span class="mb-1 block text-xs font-medium text-zinc-500">{{ __('place_editing.opening_hours.second_until') }} <span class="font-normal text-zinc-400">({{ __('place_editing.common.optional') }})</span></span>
                                            <input type="time" name="days[{{ $dayKey }}][closes_at_2]" value="{{ $day['closes_at_2'] ?? '' }}" class="h-10 w-full rounded-lg border border-zinc-300 bg-white px-3 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                        </label>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>

                @if (! $canDirectEdit)
                    <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                        <label class="block">
                            <span class="mb-1 block text-sm font-medium">{{ __('place_editing.opening_hours.comment') }} <span class="font-normal text-zinc-400">({{ __('place_editing.common.optional') }})</span></span>
                            <textarea name="comment" rows="3" maxlength="2000" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950" placeholder="{{ __('place_editing.opening_hours.comment_placeholder') }}">{{ old('comment') }}</textarea>
                        </label>
                    </section>
                @endif

                <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 text-sm text-zinc-600 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300">
                    {{ __('place_editing.opening_hours.exceptions_help') }}
                </div>

                <div class="flex flex-wrap justify-end gap-3">
                    <a href="{{ route('places.show', $place->slug) }}" class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-semibold hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800">
                        {{ __('place_editing.common.cancel') }}
                    </a>
                    <button type="submit" class="rounded-lg bg-zinc-900 px-5 py-2 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200">
                        @if ($overlapPreview)
                            {{ $canDirectEdit ? __('place_editing.opening_hours.save_overwrite') : __('place_editing.opening_hours.suggest_overwrite') }}
                        @else
                            {{ $canDirectEdit ? __('place_editing.opening_hours.save') : __('place_editing.opening_hours.submit') }}
                        @endif
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            (() => {
                const initOpeningHoursEditor = () => {
                    document.querySelectorAll('[data-opening-day]').forEach((row) => {
                        if (row.dataset.bound) return;
                        row.dataset.bound = '1';

                        const select = row.querySelector('[data-opening-mode]');
                        const times = row.querySelector('[data-opening-times]');

                        const refresh = () => {
                            times?.classList.toggle('hidden', select?.value !== 'hours');
                        };

                        select?.addEventListener('change', refresh);
                        refresh();
                    });

                    const weekdayRows = [...document.querySelectorAll('[data-opening-day]')]
                        .filter((row) => !row.querySelector('[name="days[holiday][mode]"]'));
                    const open247Toggle = document.querySelector('[data-opening-247]');
                    const closedEveryDayToggle = document.querySelector('[data-opening-closed]');

                    const refreshQuickToggles = () => {
                        if (open247Toggle) {
                            open247Toggle.checked = weekdayRows.length > 0
                                && weekdayRows.every((row) => row.querySelector('[data-opening-mode]')?.value === '24h');
                        }

                        if (closedEveryDayToggle) {
                            closedEveryDayToggle.checked = weekdayRows.length > 0
                                && weekdayRows.every((row) => row.querySelector('[data-opening-mode]')?.value === 'closed');
                        }
                    };

                    const applyQuickMode = (mode, enabled) => {
                        weekdayRows.forEach((row) => {
                            const select = row.querySelector('[data-opening-mode]');
                            const times = row.querySelector('[data-opening-times]');
                            if (!select) return;

                            select.value = enabled ? mode : 'unknown';
                            times?.classList.toggle('hidden', select.value !== 'hours');
                        });

                        refreshQuickToggles();
                    };

                    if (open247Toggle && !open247Toggle.dataset.bound) {
                        open247Toggle.dataset.bound = '1';
                        open247Toggle.addEventListener('change', () => applyQuickMode('24h', open247Toggle.checked));
                    }

                    if (closedEveryDayToggle && !closedEveryDayToggle.dataset.bound) {
                        closedEveryDayToggle.dataset.bound = '1';
                        closedEveryDayToggle.addEventListener('change', () => applyQuickMode('closed', closedEveryDayToggle.checked));
                    }

                    weekdayRows.forEach((row) => {
                        const select = row.querySelector('[data-opening-mode]');
                        if (!select || select.dataset.quickToggleBound) return;
                        select.dataset.quickToggleBound = '1';
                        select.addEventListener('change', refreshQuickToggles);
                    });
                    refreshQuickToggles();

                    const copyWeekdaysButton = document.querySelector('[data-copy-weekdays]');
                    if (copyWeekdaysButton && !copyWeekdaysButton.dataset.bound) {
                        copyWeekdaysButton.dataset.bound = '1';
                        copyWeekdaysButton.addEventListener('click', () => {
                            const sourceRow = document.querySelector('[data-opening-day][data-day-key="1"]');
                            if (!sourceRow) return;

                            const sourceMode = sourceRow.querySelector('[data-opening-mode]')?.value ?? 'unknown';
                            const timeFields = ['opens_at', 'closes_at', 'opens_at_2', 'closes_at_2'];

                            ['2', '3', '4', '5'].forEach((dayKey) => {
                                const targetRow = document.querySelector(`[data-opening-day][data-day-key="${dayKey}"]`);
                                if (!targetRow) return;

                                const targetMode = targetRow.querySelector('[data-opening-mode]');
                                if (targetMode) {
                                    targetMode.value = sourceMode;
                                    targetMode.dispatchEvent(new Event('change', { bubbles: true }));
                                }

                                timeFields.forEach((field) => {
                                    const source = sourceRow.querySelector(`[name="days[1][${field}]"]`);
                                    const target = targetRow.querySelector(`[name="days[${dayKey}][${field}]"]`);
                                    if (source && target) target.value = source.value;
                                });
                            });

                            refreshQuickToggles();
                        });
                    }

                    document.querySelectorAll('[data-period-mode-shell]').forEach((shell) => {
                        if (shell.dataset.bound) return;
                        shell.dataset.bound = '1';

                        const select = shell.querySelector('[data-period-mode]');
                        const fields = shell.querySelectorAll('[data-season-field]');

                        const refresh = () => {
                            fields.forEach((field) => field.classList.toggle('hidden', select?.value !== 'seasonal'));
                        };

                        select?.addEventListener('change', refresh);
                        refresh();
                    });
                };

                document.addEventListener('DOMContentLoaded', initOpeningHoursEditor);
                document.addEventListener('livewire:navigated', initOpeningHoursEditor);
                initOpeningHoursEditor();
            })();
        </script>
    @endpush
</x-layouts::app>
