<x-layouts::app :title="$place->name">
    <div class="mx-auto w-full max-w-5xl space-y-5 px-4 py-6">
        <a href="{{ route('dashboard') }}" class="text-sm text-zinc-500 hover:underline">← Zurück zur Suche</a>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold">{{ $place->name }}</h1>
                    <div class="mt-1 text-sm text-zinc-500">{{ str($place->place_type_slug)->replace('-', ' ')->headline() }}</div>
                </div>
                @auth
                    <form method="POST" action="{{ $isFavorite ? route('favorites.destroy', $place->slug) : route('favorites.store', $place->slug) }}">
                        @csrf
                        @if ($isFavorite) @method('DELETE') @endif
                        <button class="rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700">{{ $isFavorite ? '★ Favorit' : '☆ Favorit' }}</button>
                    </form>
                @endauth
            </div>

            @php
                $address = trim(implode(' ', array_filter([$place->street, $place->house_number])));
                $city = trim(implode(' ', array_filter([$place->postal_code, $place->city])));
            @endphp
            @if ($address || $city)
                <div class="mt-4 text-sm text-zinc-600 dark:text-zinc-300">
                    @if ($address)<div>{{ $address }}</div>@endif
                    @if ($city)<div>{{ $city }}</div>@endif
                </div>
            @endif
        </div>

        <div id="favon-place-map" class="h-[48vh] min-h-80 overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-800"></div>

        <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950/30 dark:text-amber-100">
            Dieses Profil ist absichtlich reduziert. Favon-spezifische Attribute, Check-ins, Health Checks und Ratings werden als Nächstes neu aufgebaut.
        </div>
    </div>

    @push('scripts')
        <script>
            (() => {
                const init = () => {
                    const element = document.getElementById('favon-place-map');
                    if (!element || element.dataset.ready || !window.L) return;
                    element.dataset.ready = '1';
                    const lat = @json((float) $place->latitude);
                    const lng = @json((float) $place->longitude);
                    const map = window.L.map(element).setView([lat, lng], 15);
                    window.L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap contributors',
                    }).addTo(map);
                    window.L.marker([lat, lng]).addTo(map);
                };
                document.addEventListener('DOMContentLoaded', init, { once: true });
                window.addEventListener('camperwolf:leaflet-ready', init, { once: true });
                init();
            })();
        </script>
    @endpush
</x-layouts::app>
