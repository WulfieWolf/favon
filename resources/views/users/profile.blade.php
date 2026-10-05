@php
    $__profileRenderStarted = ($profileEnabled ?? false) ? microtime(true) : null;
    $__profileSlotStarted = $__profileRenderStarted;
@endphp
@php
    $genderLabel = $gender ? __('community_profile.gender_values.'.$gender) : null;
    $hasDetails = filled($bio)
        || filled($hometownCity)
        || filled($age)
        || filled($gender)
        || filled($vehicleType);
@endphp

<x-layouts::app :title="$handle">
    @if ($profileEnabled ?? false)
        <div class="mx-auto mt-4 w-full max-w-5xl border border-amber-300 bg-amber-50 px-4 py-3 text-xs text-amber-950 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-100">
            <div class="mb-2 font-semibold">User Profile Performance</div>
            <div class="grid gap-x-6 gap-y-1 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($profile as $label => $milliseconds)
                    <div class="flex justify-between gap-3"><span>{{ $label }}</span><strong>{{ number_format($milliseconds, 2, ',', '.') }} ms</strong></div>
                @endforeach
            </div>
        </div>
    @endif
    <div class="mx-auto w-full max-w-5xl px-4 py-5 sm:px-5 sm:py-8 xl:px-7">
        <section class="rounded-2xl border border-zinc-200 bg-white p-4 sm:p-6 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex min-w-0 items-center gap-4">
                    <div class="relative shrink-0">
                        @if ($profilePhotoUrl)
                            <img src="{{ $profilePhotoUrl }}" alt="{{ $handle }}" class="shrink-0 rounded-full object-cover ring-1 ring-zinc-200 dark:ring-zinc-700" style="width:72px;height:72px;max-width:72px;max-height:72px;">
                        @else
                            <div class="flex shrink-0 items-center justify-center rounded-full bg-zinc-200 text-xl font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200" style="width:72px;height:72px;max-width:72px;max-height:72px;">
                                {{ strtoupper(substr($handle, 0, 2)) }}
                            </div>
                        @endif

                        @if ($showGamification && $gamification)
                            <span class="absolute bottom-1 right-1 inline-flex min-w-8 items-center justify-center gap-1 rounded-full border-2 border-white bg-zinc-900 px-2 py-1 text-xs font-bold text-white shadow-sm dark:border-zinc-900 dark:bg-white dark:text-zinc-950" title="{{ __('community_profile.level_label', ['level' => $gamification['level']]) }}">
                                <x-tabler-icon name="sparkles" class="size-3.5" />
                                {{ $gamification['level'] }}
                            </span>
                        @endif
                    </div>

                    <div class="min-w-0">
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('community_profile.public_title') }}</p>
                        <h1 class="break-words text-2xl font-semibold tracking-tight sm:text-3xl">{{ $handle }}</h1>
                        @if ($handle !== $automaticHandle)
                            <p class="mt-1 text-xs font-medium text-zinc-400">
                                {{ __('community_profile.camperwolf_id') }}: {{ $automaticHandle }}
                            </p>
                        @endif
                        @if ($showGamification && $selectedTitle)
                            <p class="mt-1 text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $selectedTitle['label'] }}</p>
                        @endif
                        @if ($joinedAt)
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('community_profile.member_since', ['date' => $joinedAt]) }}
                            </p>
                        @endif
                    </div>
                </div>

                @if ($isOwner)
                    <a href="{{ route('community-profile.edit') }}" class="inline-flex self-start items-center justify-center gap-2 rounded-lg border border-zinc-300 px-3 py-2 text-sm font-medium hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800" style="width:fit-content;">
                        <x-tabler-icon name="pencil" class="size-4" />
                        <span>{{ __('community_profile.edit_profile') }}</span>
                    </a>
                @endif
            </div>
        </section>

        @if ($showGamification && $gamification)
            <section class="mt-6 rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold">{{ __('community_profile.xp_title') }}</h2>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('community_profile.level_label', ['level' => $gamification['level']]) }}
                            · {{ __('community_profile.xp_total', ['xp' => $gamification['xp']]) }}
                        </p>
                    </div>
                    <div class="text-sm font-medium">
                        {{ __('community_profile.xp_progress', [
                            'current' => $gamification['progress_xp'],
                            'needed' => $gamification['needed_xp'],
                            'next' => $gamification['level'] + 1,
                        ]) }}
                    </div>
                </div>

                <div class="mt-4 h-3 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-800" aria-label="{{ $gamification['progress_percent'] }}%">
                    <div class="h-full rounded-full bg-zinc-900 transition-all dark:bg-white" style="width: {{ $gamification['progress_percent'] }}%"></div>
                </div>
            </section>
        @endif

        <div class="mt-6 grid gap-6 lg:grid-cols-[1.4fr_1fr]">
            <div class="space-y-6">
                @if ($bio)
                    <section class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                        <h2 class="text-lg font-semibold">{{ __('community_profile.about') }}</h2>
                        <p class="mt-3 whitespace-pre-line text-sm leading-6 text-zinc-700 dark:text-zinc-300">{{ $bio }}</p>
                    </section>
                @endif

                @if (! $hasDetails)
                    <section class="rounded-2xl border border-zinc-200 bg-white p-5 text-sm text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400">
                        {{ __('community_profile.empty') }}
                    </section>
                @endif
            </div>

            <div class="space-y-6">
                @if ($hometownCity || $age !== null || $gender || $vehicleType)
                    <section class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                        <dl class="space-y-4 text-sm">
                            @if ($hometownCity)
                                <div>
                                    <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('community_profile.hometown') }}</dt>
                                    <dd class="mt-1">{{ $hometownCity }}@if($hometownCountryName), {{ $hometownCountryName }}@endif</dd>
                                </div>
                            @endif

                            @if ($age !== null)
                                <div>
                                    <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('community_profile.age') }}</dt>
                                    <dd class="mt-1">{{ __('community_profile.years', ['age' => $age]) }}</dd>
                                </div>
                            @endif

                            @if ($gender)
                                <div>
                                    <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('community_profile.gender') }}</dt>
                                    <dd class="mt-1">
                                        {{ $genderLabel }}
                                        @if ($gender === 'nonbinary_other' && $genderCustom)
                                            <span class="text-zinc-500 dark:text-zinc-400">· {{ $genderCustom }}</span>
                                        @endif
                                    </dd>
                                </div>
                            @endif

                            @if ($vehicleType)
                                <div>
                                    <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('community_profile.vehicle') }}</dt>
                                    <dd class="mt-1">
                                        {{ $vehicleLabel ?: $vehicleType }}
                                        @if ($vehicleDetails)
                                            <span class="text-zinc-500 dark:text-zinc-400">· {{ $vehicleDetails }}</span>
                                        @endif
                                    </dd>
                                </div>
                            @endif
                        </dl>
                    </section>
                @endif


            </div>
        </div>

        @if ($showGamification && $badgeSummary)
            <section class="mt-6 rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="flex flex-wrap items-baseline gap-x-4">
                            <h2 class="text-lg font-semibold">{{ __('community_profile.badges_title') }}</h2>
                            <span class="text-sm text-zinc-500 dark:text-zinc-400">(<a href="{{ route('help.show', 'badges-und-achievements') }}" class="font-medium text-zinc-900 underline underline-offset-2 hover:no-underline dark:text-white">{{ __('community_profile.settings.gamification_what_is_this') }}</a>)</span>
                        </div>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('community_profile.badges_intro') }}
                        </p>
                    </div>
                    @if ($badgeSummary['hidden_total'] > 0)
                        <div class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                            {{ __('community_profile.hidden_achievements', ['found' => $badgeSummary['hidden_found'], 'total' => $badgeSummary['hidden_total']]) }}
                        </div>
                    @endif
                </div>

                <div class="mt-5">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('community_profile.progress_badges') }}</h3>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($badgeSummary['progress_badges'] as $badge)
                            @php
                                $tierIconStyle = match ($badge['tier']) {
                                    'bronze' => 'background-color:#CD7F32;color:#211408;border-color:#A66327;',
                                    'silver' => 'background-color:#C0C0C0;color:#171717;border-color:#9CA3AF;',
                                    'gold' => 'background-color:#FFD700;color:#3B2F00;border-color:#D4AF00;',
                                    'platinum' => 'background-color:#7DD3FC;color:#082F49;border-color:#38BDF8;',
                                    default => null,
                                };
                            @endphp
                            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900" title="{{ $badge['description'] }}">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="flex size-10 shrink-0 items-center justify-center rounded-xl border border-zinc-300 bg-zinc-100 text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400"
                                        @if ($tierIconStyle) style="{{ $tierIconStyle }}" @endif
                                    >
                                        <x-tabler-icon :name="$badge['icon']" class="size-5" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="font-semibold">
                                            {{ $badge['name'] }}
                                            @if ($badge['tier_label'])
                                                <span class="font-normal text-zinc-600 dark:text-zinc-300">· {{ $badge['tier_label'] }}</span>
                                            @endif
                                        </div>
                                        <div class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">
                                            {{ number_format($badge['count'], 0, ',', '.') }}
                                            @if ($badge['slug'] === 'explorer')
                                                {{ __('community_profile.places_count') }}
                                            @elseif ($badge['slug'] === 'photographer')
                                                {{ __('community_profile.photos_count') }}
                                            @elseif ($badge['slug'] === 'connoisseur')
                                                {{ __('community_profile.reviews_count') }}
                                            @else
                                                {{ __('community_profile.contributions_count') }}
                                            @endif
                                        </div>

                                        @if ($badge['next_threshold'])
                                            @php
                                                $progressPercent = min(100, max(0, ($badge['count'] / max(1, $badge['next_threshold'])) * 100));
                                            @endphp
                                            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-zinc-200/80 dark:bg-zinc-700">
                                                <div class="h-full rounded-full bg-zinc-900 dark:bg-white" style="width: {{ $progressPercent }}%"></div>
                                            </div>
                                            <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                                {{ __('community_profile.progress_to_next_tier', [
                                                    'current' => number_format($badge['count'], 0, ',', '.'),
                                                    'target' => number_format($badge['next_threshold'], 0, ',', '.'),
                                                    'tier' => $badge['next_tier_label'],
                                                ]) }}
                                            </div>
                                        @elseif ($badge['tier'] === 'platinum')
                                            <div class="mt-2 text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('community_profile.platinum_continues') }}</div>
                                        @endif

                                        @if ($badge['unlocked_at'])
                                            <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                                {{ __('community_profile.reached_on', ['tier' => $badge['tier_label'], 'date' => \App\Support\LocalTime::parse($badge['unlocked_at'])->translatedFormat(__('community_profile.date_format'))]) }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mt-6">
                    @php
                        $unlockedAchievements = collect($badgeSummary['achievements'])
                            ->where('unlocked', true)
                            ->values();
                        $pendingAchievements = collect($badgeSummary['achievements'])
                            ->where('unlocked', false)
                            ->values();
                    @endphp

                    <h3 class="text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('community_profile.achievements') }}</h3>

                    @if ($unlockedAchievements->isNotEmpty())
                        <div class="mt-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">
                                {{ __('community_profile.received') }}
                            </div>

                            <div class="mt-2 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                @foreach ($unlockedAchievements as $achievement)
                                    @php
                                        $tooltip = $achievement['name']."\n".$achievement['description'];
                                        if ($achievement['unlocked_at']) {
                                            $tooltip .= "\n".__('community_profile.received_on', [
                                                'date' => \App\Support\LocalTime::parse($achievement['unlocked_at'])->translatedFormat(__('community_profile.date_format')),
                                            ]);
                                        }
                                    @endphp
                                    <div
                                        class="rounded-xl border border-zinc-300 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900"
                                        title="{{ $tooltip }}"
                                    >
                                        <div class="flex items-start gap-3">
                                            <div class="flex size-10 shrink-0 items-center justify-center rounded-full border border-zinc-300 bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800">
                                                <x-tabler-icon :name="$achievement['icon']" class="size-5" />
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="font-semibold">{{ $achievement['name'] }}</div>
                                                <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $achievement['description'] }}</div>

                                                @if ($achievement['progress'] !== null && $achievement['target'])
                                                    <div class="mt-2 text-xs font-medium">
                                                        {{ $achievement['progress'] }} / {{ $achievement['target'] }}
                                                    </div>
                                                @endif

                                                <div class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                                                    {{ __('community_profile.received_on', ['date' => \App\Support\LocalTime::parse($achievement['unlocked_at'])->translatedFormat(__('community_profile.date_format'))]) }} ·
                                                    {{ __('community_profile.rarity_all_users', ['percent' => $achievement['rarity'] < 0.1 ? '<0.1' : number_format($achievement['rarity'], 1, '.', '')]) }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($pendingAchievements->isNotEmpty())
                        <div class="{{ $unlockedAchievements->isNotEmpty() ? 'mt-6' : 'mt-4' }}">
                            <div class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-500">
                                {{ __('community_profile.pending') }}
                            </div>

                            <div class="mt-2 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                @foreach ($pendingAchievements as $achievement)
                                    @php
                                        $tooltip = $achievement['name']."\n".$achievement['description'];
                                    @endphp
                                    <div
                                        class="rounded-xl border border-dashed border-zinc-300/70 bg-zinc-100/30 p-4 text-zinc-500 dark:border-zinc-700/70 dark:bg-transparent dark:text-zinc-500"
                                        title="{{ $tooltip }}"
                                    >
                                        <div class="flex items-start gap-3">
                                            <div class="flex size-10 shrink-0 items-center justify-center rounded-full border border-zinc-300/70 bg-transparent text-zinc-400 dark:border-zinc-700 dark:text-zinc-600">
                                                <x-tabler-icon :name="$achievement['icon']" class="size-5" />
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="font-semibold text-zinc-600 dark:text-zinc-500">{{ $achievement['name'] }}</div>
                                                <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-600">{{ $achievement['description'] }}</div>

                                                @if ($achievement['progress'] !== null && $achievement['target'])
                                                    <div class="mt-2 text-xs font-medium text-zinc-500 dark:text-zinc-600">
                                                        {{ $achievement['progress'] }} / {{ $achievement['target'] }}
                                                    </div>
                                                @endif

                                                <div class="mt-2 text-xs text-zinc-400 dark:text-zinc-600">
                                                    {{ __('community_profile.rarity_all_users', ['percent' => $achievement['rarity'] < 0.1 ? '<0.1' : number_format($achievement['rarity'], 1, '.', '')]) }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                @if (count($badgeSummary['manual_badges']) > 0)
                    <div class="mt-6">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('community_profile.awards') }}</h3>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach ($badgeSummary['manual_badges'] as $badge)
                                <div class="rounded-xl border border-zinc-300 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900" title="{{ $badge['description'] }}">
                                    <div class="flex items-start gap-3">
                                        <div class="flex size-10 shrink-0 items-center justify-center rounded-lg border border-zinc-300 bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800">
                                            <x-tabler-icon :name="$badge['icon']" class="size-5" />
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="font-semibold">{{ $badge['name'] }}</div>
                                            <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $badge['description'] }}</div>
                                            @if ($badge['award_comment'])
                                                <div class="mt-2 rounded-lg bg-zinc-50 p-2 text-xs italic text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                                    „{{ $badge['award_comment'] }}“
                                                </div>
                                            @endif
                                            <div class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                                                {{ __('community_profile.received_on', ['date' => \App\Support\LocalTime::parse($badge['unlocked_at'])->translatedFormat(__('community_profile.date_format'))]) }}
                                                · {{ __('community_profile.rarity_all_users', ['percent' => $badge['rarity'] < 0.1 ? '<0.1' : number_format($badge['rarity'], 1, '.', '')]) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>
        @endif

        @if ($showGamification && $xpEntries)
            <section class="mt-6 rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold">{{ __('community_profile.xp_log_title') }}</h2>

                <div class="mt-4 divide-y divide-zinc-200 overflow-y-auto overscroll-contain pr-2 dark:divide-zinc-800" style="height: 16rem;">
                    @forelse ($xpEntries as $entry)
                        <div class="flex gap-4 py-3 first:pt-0 last:pb-0">
                            <div class="min-w-0 flex-1">
                                <div class="text-sm">
                                    @if ($entry->place_slug)
                                        <a href="{{ route('places.show', $entry->place_slug) }}" class="font-medium hover:underline">
                                            {{ $entry->description }}
                                        </a>
                                    @else
                                        <span class="font-medium">{{ $entry->description }}</span>
                                    @endif
                                </div>
                                <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $entry->created_at_label }}
                                </div>
                            </div>
                            <div class="shrink-0 text-sm font-bold {{ $entry->xp >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $entry->xp >= 0 ? '+' : '' }}{{ $entry->xp }} XP
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('community_profile.xp_log_empty') }}</p>
                    @endforelse
                </div>

            </section>
        @endif
    </div>
    @if ($profileEnabled ?? false)
        @php($__profileSlotMs = round((microtime(true) - $__profileSlotStarted) * 1000, 2))
        <div class="mx-auto mb-4 w-full max-w-5xl border border-sky-300 bg-sky-50 px-4 py-3 text-xs text-sky-950 dark:border-sky-800 dark:bg-sky-950/30 dark:text-sky-100">
            <div class="flex justify-between gap-3"><span>Blade page slot</span><strong>{{ number_format($__profileSlotMs, 2, ',', '.') }} ms</strong></div>
        </div>
    @endif
</x-layouts::app>
@if ($profileEnabled ?? false)
    @php($__profileBladeMs = round((microtime(true) - $__profileRenderStarted) * 1000, 2))
    <div class="mx-auto mb-4 w-full max-w-5xl border border-sky-400 bg-sky-100 px-4 py-3 text-xs text-sky-950 dark:border-sky-700 dark:bg-sky-950/40 dark:text-sky-100">
        <div class="flex justify-between gap-3"><span>Blade total incl. layout</span><strong>{{ number_format($__profileBladeMs, 2, ',', '.') }} ms</strong></div>
    </div>
@endif
