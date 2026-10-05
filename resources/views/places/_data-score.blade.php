@if ($dataScore)
    @php
        $toneClasses = [
            'red' => [
                'border' => 'border-red-400 dark:border-red-800',
                'badge' => 'border-red-400 bg-red-50 text-red-800 dark:border-red-800 dark:bg-red-950/30 dark:text-red-200',
                'message' => 'bg-red-50 text-red-900 dark:bg-red-950/30 dark:text-red-200',
            ],
            'yellow' => [
                'border' => 'border-amber-400 dark:border-amber-800',
                'badge' => 'border-amber-400 bg-amber-50 text-amber-900 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-200',
                'message' => 'bg-amber-50 text-amber-900 dark:bg-amber-950/30 dark:text-amber-200',
            ],
            'green' => [
                'border' => 'border-emerald-400 dark:border-emerald-800',
                'badge' => 'border-emerald-400 bg-emerald-50 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-200',
                'message' => 'bg-emerald-50 text-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-200',
            ],
        ];
        $overallTone = $toneClasses[$dataScore['level']];
        $basisTone = $toneClasses[$dataScore['basis_level']];
        $featureTone = $toneClasses[$dataScore['feature_level']];
        $formatScore = static fn (float $value): string => \Illuminate\Support\Number::format(
            round($value, 1, PHP_ROUND_HALF_UP),
            1,
            1,
            app()->getLocale(),
        );
    @endphp

    <aside class="rounded-xl border-2 bg-white p-4 shadow-sm dark:bg-zinc-950/40 {{ $overallTone['border'] }}">
        <div class="flex items-start justify-between gap-3">
            <div>
                <div class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('place_profile.data_score.title') }}</div>
                <div class="mt-2 inline-flex rounded-lg border-2 px-3 py-1.5 text-xl font-bold tabular-nums {{ $overallTone['badge'] }}">
                    {{ $formatScore($dataScore['score']) }} / {{ $formatScore(10.0) }}
                </div>
            </div>
        </div>

        <details class="mt-3 group">
            <summary class="flex cursor-pointer list-none items-center gap-1 text-xs font-medium text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200">
                <x-tabler-icon name="chevron-right" class="size-3.5 transition-transform group-open:rotate-90" />
                {{ __('place_profile.data_score.details') }}
            </summary>

            <div class="mt-2 grid gap-2">
                <div class="flex items-center justify-between gap-3 rounded-lg border-2 px-3 py-2 text-sm {{ $basisTone['badge'] }}">
                    <span>{{ __('place_profile.data_score.basis') }}</span>
                    <strong class="tabular-nums">{{ $formatScore($dataScore['basis_score']) }} / {{ $formatScore(10.0) }}</strong>
                </div>
                <div class="flex items-center justify-between gap-3 rounded-lg border-2 px-3 py-2 text-sm {{ $featureTone['badge'] }}">
                    <span>{{ __('place_profile.data_score.features') }}</span>
                    <strong class="tabular-nums">{{ $formatScore($dataScore['feature_score']) }} / {{ $formatScore(10.0) }}</strong>
                </div>
            </div>
        </details>

        <div class="mt-3 text-[11px] leading-5 text-zinc-500 dark:text-zinc-400">
            @if ($lastContentUpdateAt)
                <div>{{ __('place_profile.data_score.last_change', ['date' => $lastContentUpdateAt->diffForHumans()]) }}</div>
            @else
                <div>{{ __('place_profile.data_score.last_change_unknown') }}</div>
            @endif
            <a href="{{ route('help.show', 'datenscore') }}" class="font-medium underline underline-offset-2 hover:text-zinc-800 dark:hover:text-zinc-200">
                {{ __('place_profile.data_score.what_is_this') }}
            </a>
        </div>

        <div class="mt-3 rounded-lg px-3 py-2.5 text-xs leading-5 {{ $overallTone['message'] }}">
            @if ($dataScore['level'] === 'red')
                <strong>{{ __('place_profile.data_score.message_red_title') }}</strong>
                {{ __('place_profile.data_score.message_red') }}
            @elseif ($dataScore['level'] === 'yellow')
                {{ __('place_profile.data_score.message_yellow') }}
            @else
                {{ __('place_profile.data_score.message_green') }}
            @endif

            @if ($placeInformationMayBeOutdated)
                <div class="mt-2 font-medium">{{ __('place_profile.data_score.outdated') }}</div>
            @endif
        </div>
    </aside>
@endif
