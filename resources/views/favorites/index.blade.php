<x-layouts::app :title="__('ui.favorites')">
    <div class="mx-auto w-full max-w-[1500px] px-6 py-6">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold tracking-tight">{{ __('ui.favorites') }}</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('ui.favorites_page.intro') }}
            </p>
        </div>
@forelse ($favorites as $favorite)
            @if ($loop->first)
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @endif

            <article class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="text-xs font-medium uppercase tracking-wide text-zinc-400">
                            {{ str($favorite->place_type_slug)->replace('-', ' ')->headline() }}
                        </div>
                        <h2 class="mt-1 truncate text-lg font-semibold">
                            <a href="{{ route('places.show', $favorite->slug) }}" class="hover:underline">
                                {{ $favorite->name }}
                            </a>
                        </h2>
                        <p class="mt-1 text-sm text-zinc-500">
                            {{ trim(($favorite->postal_code ? $favorite->postal_code.' ' : '').($favorite->city ?? '')) ?: __('ui.favorites_page.location_missing') }}
                            @if ($favorite->country_code) · {{ $favorite->country_code }} @endif
                        </p>
                    </div>

                    <form method="POST" action="{{ route('favorites.destroy', $favorite->slug) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex size-10 items-center justify-center rounded-lg border border-red-300 bg-red-50 text-2xl leading-none text-red-600 transition hover:bg-red-100 hover:text-red-700 dark:border-red-900 dark:bg-red-950/30 dark:text-red-500 dark:hover:bg-red-950/50 dark:hover:text-red-400" title="{{ __('ui.favorites_page.remove') }}" aria-label="{{ __('ui.favorites_page.remove') }}">
                            ♥
                        </button>
                    </form>
                </div>

                <div class="mt-4 flex items-center justify-between gap-3">
                    <div class="text-xs text-zinc-400">
                        {{ $favorite->notify_changes ? __('ui.favorites_page.notifications_on') : __('ui.favorites_page.notifications_off') }}
                    </div>
                    <a href="{{ route('places.show', $favorite->slug) }}" class="rounded-lg bg-zinc-900 px-3 py-2 text-sm font-medium text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200">
                        {{ __('ui.favorites_page.open_place') }}
                    </a>
                </div>
            </article>

            @if ($loop->last)
                </div>
            @endif
        @empty
            <div class="rounded-xl border border-dashed border-zinc-300 p-10 text-center dark:border-zinc-700">
                <div class="text-lg font-semibold">{{ __('ui.favorites_page.empty') }}</div>
                <p class="mt-2 text-sm text-zinc-500">{{ __('ui.favorites_page.empty_help') }}</p>
                <a href="{{ route('dashboard') }}" class="mt-4 inline-flex rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200">
                    {{ __('ui.favorites_page.browse') }}
                </a>
            </div>
        @endforelse

        @if ($favorites->hasPages())
            <div class="mt-6">
                {{ $favorites->links() }}
            </div>
        @endif
    </div>
</x-layouts::app>
