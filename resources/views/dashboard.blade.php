<x-layouts::app title="Favon">
    <div class="mx-auto w-full max-w-7xl space-y-5 px-4 py-6">
        <div>
            <h1 class="text-2xl font-semibold">Favon</h1>
            <p class="mt-1 text-sm text-zinc-500">Übergangsansicht nach dem Camperwolf-Cleanup.</p>
        </div>

        <form method="GET" action="{{ route('dashboard') }}" class="grid gap-3 rounded-xl border border-zinc-200 bg-white p-4 md:grid-cols-[1fr_auto_auto] dark:border-zinc-800 dark:bg-zinc-900">
            <input name="q" value="{{ $queryText }}" placeholder="Ort oder Name suchen" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">
            @auth
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="favorites" value="1" @checked($favoritesOnly)>
                    Nur Favoriten
                </label>
            @endauth
            <button class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white dark:bg-white dark:text-zinc-950">Suchen</button>

            @if ($placeTypes->isNotEmpty())
                <div class="flex flex-wrap gap-2 md:col-span-3">
                    @foreach ($placeTypes as $type)
                        <label class="flex items-center gap-1.5 rounded-full border border-zinc-300 px-3 py-1.5 text-xs dark:border-zinc-700">
                            <input type="checkbox" name="place_types[]" value="{{ $type->slug }}" @checked(in_array($type->slug, $selectedTypes, true))>
                            {{ str($type->slug)->replace('-', ' ')->headline() }}
                        </label>
                    @endforeach
                </div>
            @endif
        </form>

        <div id="favon-map" class="h-[42vh] min-h-80 overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-800"></div>

        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($places as $place)
                <article class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <a href="{{ route('places.show', $place->slug) }}" class="font-semibold hover:underline">{{ $place->name }}</a>
                            <div class="mt-1 text-xs text-zinc-500">{{ str($place->place_type_slug)->replace('-', ' ')->headline() }}</div>
                        </div>
                        @auth
                            <form method="POST" action="{{ in_array((int) $place->id, $favoriteIds, true) ? route('favorites.destroy', $place->slug) : route('favorites.store', $place->slug) }}">
                                @csrf
                                @if (in_array((int) $place->id, $favoriteIds, true)) @method('DELETE') @endif
                                <button class="text-lg" title="Favorit">{{ in_array((int) $place->id, $favoriteIds, true) ? '★' : '☆' }}</button>
                            </form>
                        @endauth
                    </div>
                    @if ($place->city || $place->postal_code)
                        <div class="mt-3 text-sm text-zinc-600 dark:text-zinc-300">{{ trim(($place->postal_code ? $place->postal_code.' ' : '').($place->city ?? '')) }}</div>
                    @endif
                </article>
            @empty
                <div class="rounded-xl border border-dashed border-zinc-300 p-8 text-center text-zinc-500 md:col-span-2 xl:col-span-3 dark:border-zinc-700">Keine Plätze gefunden.</div>
            @endforelse
        </div>

        {{ $places->links() }}
    </div>

    @push('scripts')
        <script>
            (() => {
                const points = @json($places->getCollection()->map(fn ($place) => [
                    'name' => $place->name,
                    'lat' => (float) $place->latitude,
                    'lng' => (float) $place->longitude,
                    'url' => route('places.show', $place->slug),
                ])->values());

                const init = () => {
                    const element = document.getElementById('favon-map');
                    if (!element || element.dataset.ready || !window.L) return;
                    element.dataset.ready = '1';

                    const map = window.L.map(element).setView([51.1657, 10.4515], 6);
                    window.L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap contributors',
                    }).addTo(map);

                    const bounds = [];
                    points.forEach((point) => {
                        const marker = window.L.marker([point.lat, point.lng]).addTo(map);
                        marker.bindPopup('<a href="' + point.url + '">' + point.name.replaceAll('<', '&lt;').replaceAll('>', '&gt;') + '</a>');
                        bounds.push([point.lat, point.lng]);
                    });
                    if (bounds.length) map.fitBounds(bounds, { padding: [24, 24], maxZoom: 14 });
                };

                document.addEventListener('DOMContentLoaded', init, { once: true });
                window.addEventListener('camperwolf:leaflet-ready', init, { once: true });
                init();
            })();
        </script>
    @endpush
</x-layouts::app>
