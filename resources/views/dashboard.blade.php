<x-layouts::app title="Favon">
    @if (session('place_submission_success'))
        <div class="fixed inset-0 z-[1000] flex items-end justify-center bg-black/70 p-3 sm:items-center sm:p-4" role="dialog" aria-modal="true" aria-labelledby="place-submission-success-title">
            <div class="max-h-[calc(100dvh-1.5rem)] w-full min-w-0 max-w-md overflow-y-auto rounded-2xl border border-zinc-200 bg-white p-5 shadow-2xl sm:p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <h2 id="place-submission-success-title" class="break-words text-xl font-semibold">{{ __('places.submission_success_title') }}</h2>
                <p class="mt-3 break-words text-sm text-zinc-600 dark:text-zinc-300">{{ __('places.submission_success_message', ['place' => session('place_submission_success.name')]) }}</p>
                <div class="mt-6 flex justify-end">
                    <a href="{{ route('dashboard') }}" class="rounded-lg bg-zinc-950 px-5 py-2 text-sm font-semibold text-white dark:bg-white dark:text-zinc-950">{{ __('ui.feedback.ok') }}</a>
                </div>
            </div>
        </div>
    @endif

    <div class="mx-auto w-full max-w-7xl space-y-5 px-4 py-6">
        <form method="GET" action="{{ route('dashboard') }}" class="rounded-xl border border-zinc-200 bg-white p-3 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex gap-2">
                <input name="q" value="{{ $queryText }}" placeholder="{{ __('ui.transition.search_placeholder') }}" class="min-w-0 flex-1 rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">
                <button class="shrink-0 rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white dark:bg-white dark:text-zinc-950">{{ __('ui.transition.search') }}</button>
            </div>

            <details class="mt-2" @if ($favoritesOnly || count($selectedTypes)) open @endif>
                <summary class="inline-flex cursor-pointer list-none items-center gap-2 rounded-lg px-2 py-1.5 text-sm font-medium text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800 [&::-webkit-details-marker]:hidden">
                    <x-tabler-icon name="adjustments-horizontal" class="size-4" />
                    {{ __('ui.transition.filters') }}
                </summary>

                <div class="mt-2 space-y-3 border-t border-zinc-200 pt-3 dark:border-zinc-800">
                    @auth
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="favorites" value="1" @checked($favoritesOnly)>
                            {{ __('ui.transition.favorites_only') }}
                        </label>
                    @endauth

                    @if ($placeTypes->isNotEmpty())
                        <div class="flex flex-wrap gap-2">
                            @foreach ($placeTypes as $type)
                                <label class="flex items-center gap-1.5 rounded-full border border-zinc-300 px-3 py-1.5 text-xs dark:border-zinc-700">
                                    <input type="checkbox" name="place_types[]" value="{{ $type->slug }}" @checked(in_array($type->slug, $selectedTypes, true))>
                                    {{ __("place_types.{$type->slug}.name") }}
                                </label>
                            @endforeach
                        </div>
                    @endif

                    <div class="flex justify-end">
                        <button class="rounded-lg border border-zinc-300 px-3 py-2 text-sm font-medium hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800">
                            {{ __('ui.transition.apply_filters') }}
                        </button>
                    </div>
                </div>
            </details>
        </form>

        <div class="relative">
            <div id="favon-map" class="h-[42vh] min-h-80 overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-800"></div>

            <button
                type="button"
                data-favon-nearby
                data-error="{{ __('ui.transition.location_failed') }}"
                class="absolute bottom-8 right-3 z-[500] inline-flex min-h-10 items-center gap-2 rounded-full bg-white px-3 py-2 text-sm font-semibold shadow-lg ring-1 ring-zinc-200 hover:bg-zinc-50 dark:bg-zinc-900 dark:ring-zinc-700 dark:hover:bg-zinc-800"
            >
                <x-tabler-icon name="current-location" class="size-5" />
                <span class="hidden sm:inline">{{ __('ui.transition.nearby') }}</span>
            </button>

            @if (auth()->check() && app(\App\Services\PermissionService::class)->can(auth()->user(), 'places.submit'))
                <details class="group absolute right-3 top-3 z-[500]">
                    <summary class="grid size-12 cursor-pointer list-none place-items-center rounded-full bg-blue-600 text-3xl font-light leading-none text-white shadow-lg transition hover:bg-blue-500 [&::-webkit-details-marker]:hidden">+</summary>
                    <div class="absolute right-0 mt-2 w-56 overflow-hidden rounded-xl border border-zinc-200 bg-white p-1 shadow-xl dark:border-zinc-700 dark:bg-zinc-900">
                        <a href="{{ route('places.create') }}" class="block rounded-lg px-3 py-2.5 text-sm font-medium hover:bg-zinc-100 dark:hover:bg-zinc-800">
                            {{ __('ui.transition.add_place') }}
                        </a>
                        <button
                            type="button"
                            data-favon-create-here
                            data-url="{{ route('places.create') }}"
                            data-error="{{ __('ui.transition.location_failed') }}"
                            class="block w-full rounded-lg px-3 py-2.5 text-left text-sm font-medium hover:bg-zinc-100 dark:hover:bg-zinc-800"
                        >
                            {{ __('ui.transition.add_place_here') }}
                        </button>
                    </div>
                </details>
            @endif
        </div>

        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($places as $place)
                <article class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <a href="{{ route('places.show', $place->slug) }}" class="font-semibold hover:underline">{{ $place->name }}</a>
                            <div class="mt-1 text-xs text-zinc-500">{{ __("place_types.{$place->place_type_slug}.name") }}</div>
                        </div>
                        @auth
                            <form method="POST" action="{{ in_array((int) $place->id, $favoriteIds, true) ? route('favorites.destroy', $place->slug) : route('favorites.store', $place->slug) }}">
                                @csrf
                                @if (in_array((int) $place->id, $favoriteIds, true)) @method('DELETE') @endif
                                <button class="text-lg" title="{{ __('ui.transition.favorite') }}">{{ in_array((int) $place->id, $favoriteIds, true) ? '★' : '☆' }}</button>
                            </form>
                        @endauth
                    </div>
                    @if ($place->city || $place->postal_code)
                        <div class="mt-3 text-sm text-zinc-600 dark:text-zinc-300">{{ trim(($place->postal_code ? $place->postal_code.' ' : '').($place->city ?? '')) }}</div>
                    @endif
                    @if ($nearby && isset($place->distance_km))
                        <div class="mt-1 text-xs font-medium text-zinc-500">
                            {{ __('ui.transition.distance_away', ['distance' => number_format((float) $place->distance_km, (float) $place->distance_km < 10 ? 1 : 0, ',', '.')]) }}
                        </div>
                    @endif
                </article>
            @empty
                <div class="rounded-xl border border-dashed border-zinc-300 p-8 text-center text-zinc-500 md:col-span-2 xl:col-span-3 dark:border-zinc-700">{{ __('ui.transition.no_places') }}</div>
            @endforelse
        </div>

        {{ $places->links() }}
    </div>

    @php
        $mapPoints = $places->getCollection()->map(fn ($place) => [
            'name' => $place->name,
            'lat' => (float) $place->latitude,
            'lng' => (float) $place->longitude,
            'url' => route('places.show', $place->slug),
        ])->values();
    @endphp

    @push('scripts')
        <script>
            (() => {
                const points = @json($mapPoints);
                const userPosition = @json($nearby ? ['lat' => $nearLat, 'lng' => $nearLng] : null);

                const init = () => {
                    const element = document.getElementById('favon-map');
                    if (!element || element.dataset.ready || !window.L) return;
                    element.dataset.ready = '1';

                    const map = window.L.map(element).setView(
                        userPosition ? [userPosition.lat, userPosition.lng] : [51.1657, 10.4515],
                        userPosition ? 13 : 6
                    );
                    window.L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap contributors',
                    }).addTo(map);

                    points.forEach((point) => {
                        const marker = window.L.marker([point.lat, point.lng]).addTo(map);
                        marker.bindPopup('<a href="' + point.url + '">' + point.name.replaceAll('<', '&lt;').replaceAll('>', '&gt;') + '</a>');
                    });
                    if (userPosition) {
                        window.L.circleMarker([userPosition.lat, userPosition.lng], {
                            radius: 8,
                            weight: 3,
                            fillOpacity: 0.8,
                        }).addTo(map).bindPopup(@json(__('ui.transition.my_position')));
                    }
                };

                document.addEventListener('DOMContentLoaded', init, { once: true });
                window.addEventListener('favon:leaflet-ready', init, { once: true });
                init();
            })();
        </script>
    @endpush
</x-layouts::app>
