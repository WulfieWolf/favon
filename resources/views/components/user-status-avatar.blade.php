@props([
    'user',
    'photoUrl' => null,
    'gamification' => null,
    'title' => null,
    'joinedAt' => null,
    'shape' => 'rounded-lg',
])

<span class="group relative inline-flex size-10 shrink-0" tabindex="0">
    @if ($photoUrl)
        <img src="{{ $photoUrl }}" alt="{{ $user->publicName() }}" class="size-10 object-cover {{ $shape }}">
    @else
        <span class="flex size-10 items-center justify-center bg-zinc-200 text-sm font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 {{ $shape }}">
            {{ $user->initials() }}
        </span>
    @endif

    @if ($gamification)
        <span class="pointer-events-none absolute bottom-0 right-0 inline-flex min-w-5 translate-x-1/4 translate-y-1/4 items-center justify-center rounded-full border-2 border-white bg-zinc-900 px-1 text-[10px] font-bold leading-4 text-white shadow-sm dark:border-zinc-950 dark:bg-white dark:text-zinc-950" title="{{ __('community_profile.level_label', ['level' => $gamification['level']]) }}">
            {{ $gamification['level'] }}
        </span>
    @endif

    <span class="pointer-events-none absolute left-0 top-full z-[80] mt-3 hidden w-64 max-w-[calc(100vw-2rem)] rounded-xl border border-zinc-200 bg-white p-4 text-left shadow-xl group-hover:block group-focus-within:block sm:left-full sm:top-0 sm:ml-3 sm:mt-0 dark:border-zinc-700 dark:bg-zinc-900">
        <span class="block truncate text-sm font-semibold text-zinc-950 dark:text-white">{{ $user->publicName() }}</span>
        @if ($title)
            <span class="mt-0.5 block truncate text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ $title['label'] }}</span>
        @endif
        @if ($gamification)
            <span class="mt-2 block text-xs text-zinc-600 dark:text-zinc-400">
                {{ __('community_profile.level_label', ['level' => $gamification['level']]) }} · {{ __('community_profile.xp_total', ['xp' => number_format($gamification['xp'], 0, ',', '.')]) }}
            </span>
        @endif
        @if ($joinedAt)
            <span class="mt-1 block text-xs text-zinc-500 dark:text-zinc-400">{{ __('community_profile.member_since', ['date' => $joinedAt]) }}</span>
        @endif
    </span>
</span>
