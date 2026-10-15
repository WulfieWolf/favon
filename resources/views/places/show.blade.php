<x-layouts::app :title="$place->name">
    <div class="mx-auto w-full max-w-5xl space-y-5 px-4 py-6">
        <a href="{{ route('dashboard') }}" class="text-sm text-zinc-500 hover:underline">← Zurück zur Suche</a>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold">{{ $place->name }}</h1>
                    <div class="mt-1 text-sm text-zinc-500">{{ __("place_types.{$place->place_type_slug}.name") }}</div>
                    @php
                        $targetAudiences = array_values(array_filter([
                            $place->target_mm ? __('places.suggest.target_audience_mm') : null,
                            $place->target_mf ? __('places.suggest.target_audience_mf') : null,
                        ]));
                    @endphp
                    @if ($targetAudiences)
                        <div class="mt-2 inline-flex rounded-full border border-zinc-300 px-2.5 py-1 text-xs font-medium text-zinc-600 dark:border-zinc-700 dark:text-zinc-300">
                            {{ __('places.profile.target_audience', ['audience' => implode(' + ', $targetAudiences)]) }}
                        </div>
                    @endif
                </div>
                @if (app(\App\Services\PermissionService::class)->isOwner(auth()->user()))
                    <a href="{{ route('admin.places.edit', $place->id) }}" class="rounded-lg border border-zinc-300 px-3 py-2 text-sm font-medium dark:border-zinc-700">{{ __('places.admin_edit.title') }}</a>
                @endif
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
            @if (filled($place->description))
                <p class="mt-4 whitespace-pre-line break-words text-sm text-zinc-700 dark:text-zinc-300">{{ $place->description }}</p>
            @endif
        </div>

        <div id="favon-place-map" class="h-[48vh] min-h-80 overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-800"></div>

        @php
            $googleMapsUrl = 'https://www.google.com/maps/search/?api=1&query='.urlencode($place->latitude.','.$place->longitude);
            $appleMapsUrl = 'https://maps.apple.com/?ll='.urlencode($place->latitude.','.$place->longitude).'&q='.urlencode($place->name);
        @endphp
        <div class="grid gap-3 sm:grid-cols-2">
            <a href="{{ $googleMapsUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-zinc-300 bg-white px-4 py-2 text-sm font-semibold hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:bg-zinc-800">
                {{ __('places.common.google_maps') }}
            </a>
            <a href="{{ $appleMapsUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-zinc-300 bg-white px-4 py-2 text-sm font-semibold hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:bg-zinc-800">
                {{ __('places.common.apple_maps') }}
            </a>
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
                window.addEventListener('favon:leaflet-ready', init, { once: true });
                init();
            })();
        </script>
    @endpush
</x-layouts::app>
