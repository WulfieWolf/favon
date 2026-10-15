<x-layouts::app :title="__('places.suggest.title')">
    <div class="mx-auto w-full max-w-5xl space-y-6 px-4 py-6">
        <div>
            <h1 class="text-2xl font-semibold">{{ __('places.suggest.title') }}</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('places.suggest.intro') }}</p>
        </div>

        <div class="rounded-xl border border-amber-300/60 bg-amber-50 p-4 text-sm text-amber-950 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100">
            <div class="font-semibold">{{ __('places.suggest.rules_title') }}</div>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                <li>{{ __('places.suggest.rules_legal') }}</li>
                <li>{{ __('places.suggest.rules_private') }}</li>
                <li>{{ __('places.suggest.rules_personal') }}</li>
                <li>{{ __('places.suggest.rules_contact') }}</li>
            </ul>
            <p class="mt-2 font-medium">{{ __('places.suggest.rules_reject') }}</p>
        </div>

        <form method="POST" action="{{ route('places.store') }}" class="space-y-6">
            @csrf

            <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold">{{ __('places.suggest.place_type') }}</h2>
                <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($placeTypes as $type)
                        @php($checked = (string) old('place_type_id') === (string) $type->id)
                        <label class="cursor-pointer">
                            <input type="radio" name="place_type_id" value="{{ $type->id }}" class="peer sr-only" @checked($checked) required>
                            <div class="h-full rounded-xl border border-zinc-300 p-4 transition peer-checked:border-zinc-950 peer-checked:ring-2 peer-checked:ring-zinc-950/20 dark:border-zinc-700 dark:peer-checked:border-white dark:peer-checked:ring-white/20">
                                <div class="font-semibold">{{ __("place_types.{$type->slug}.name") }}</div>
                                <div class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __("place_types.{$type->slug}.description") }}</div>
                            </div>
                        </label>
                    @endforeach
                </div>
                @error('place_type_id') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </section>

            <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="grid gap-5">
                    <div>
                        <label for="name" class="text-sm font-medium">{{ __('places.suggest.name') }}</label>
                        <input id="name" name="name" value="{{ old('name') }}" maxlength="255" required class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">
                        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="description" class="text-sm font-medium">{{ __('places.suggest.description') }}</label>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('places.suggest.description_help') }}</p>
                        <textarea id="description" name="description" rows="4" maxlength="2000" class="mt-2 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">{{ old('description') }}</textarea>
                        @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <fieldset>
                            <legend class="text-sm font-medium">{{ __('places.suggest.environment') }}</legend>
                            <div class="mt-2 grid grid-cols-3 gap-2">
                                @foreach (['outdoor', 'indoor', 'both'] as $value)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="environment" value="{{ $value }}" class="peer sr-only" @checked(old('environment') === $value)>
                                        <div class="rounded-lg border border-zinc-300 px-3 py-2 text-center text-sm peer-checked:border-zinc-950 peer-checked:bg-zinc-950 peer-checked:text-white dark:border-zinc-700 dark:peer-checked:border-white dark:peer-checked:bg-white dark:peer-checked:text-zinc-950">
                                            {{ __("places.suggest.environment_{$value}") }}
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>

                        <fieldset>
                            <legend class="text-sm font-medium">{{ __('places.suggest.gloryhole') }}</legend>
                            <div class="mt-2 grid grid-cols-2 gap-2">
                                @foreach (['1' => __('global.yes'), '0' => __('global.no')] as $value => $label)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="has_gloryhole" value="{{ $value }}" class="peer sr-only" @checked((string) old('has_gloryhole') === (string) $value)>
                                        <div class="rounded-lg border border-zinc-300 px-3 py-2 text-center text-sm peer-checked:border-zinc-950 peer-checked:bg-zinc-950 peer-checked:text-white dark:border-zinc-700 dark:peer-checked:border-white dark:peer-checked:bg-white dark:peer-checked:text-zinc-950">{{ $label }}</div>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold">{{ __('places.suggest.position') }}</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('places.suggest.position_help_simple') }}</p>

                <div id="favon-place-create-map" class="mt-4 h-80 overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-800"></div>

                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    <div>
                        <label for="latitude" class="text-sm font-medium">{{ __('places.suggest.latitude') }}</label>
                        <input id="latitude" name="latitude" value="{{ old('latitude') }}" required inputmode="decimal" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">
                    </div>
                    <div>
                        <label for="longitude" class="text-sm font-medium">{{ __('places.suggest.longitude') }}</label>
                        <input id="longitude" name="longitude" value="{{ old('longitude') }}" required inputmode="decimal" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">
                    </div>
                </div>

                <div class="mt-5">
                    <div class="text-sm font-medium">{{ __('places.suggest.address') }}</div>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('places.suggest.address_help') }}</p>
                    <div class="mt-3 grid gap-3 md:grid-cols-6">
                        <input name="street" value="{{ old('street') }}" placeholder="{{ __('places.suggest.street') }}" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 md:col-span-4 dark:border-zinc-700 dark:bg-zinc-950">
                        <input name="house_number" value="{{ old('house_number') }}" placeholder="{{ __('places.suggest.house_number') }}" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 md:col-span-2 dark:border-zinc-700 dark:bg-zinc-950">
                        <input name="postal_code" value="{{ old('postal_code') }}" placeholder="{{ __('places.suggest.postal_code') }}" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 md:col-span-2 dark:border-zinc-700 dark:bg-zinc-950">
                        <input name="city" value="{{ old('city') }}" placeholder="{{ __('places.suggest.city') }}" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 md:col-span-3 dark:border-zinc-700 dark:bg-zinc-950">
                        <input name="country_code" value="{{ old('country_code', 'DE') }}" placeholder="DE" maxlength="2" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 uppercase md:col-span-1 dark:border-zinc-700 dark:bg-zinc-950">
                    </div>
                </div>
            </section>

            <div class="flex justify-end gap-3">
                <a href="{{ route('dashboard') }}" class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium dark:border-zinc-700">{{ __('places.common.cancel') }}</a>
                <button type="submit" class="rounded-lg bg-zinc-950 px-5 py-2 text-sm font-semibold text-white dark:bg-white dark:text-zinc-950">{{ __('places.suggest.submit') }}</button>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            (() => {
                const init = () => {
                    const el = document.getElementById('favon-place-create-map');
                    if (!el || el.dataset.ready || !window.L) return;
                    el.dataset.ready = '1';

                    const latInput = document.getElementById('latitude');
                    const lngInput = document.getElementById('longitude');
                    const existingLat = Number.parseFloat(latInput.value);
                    const existingLng = Number.parseFloat(lngInput.value);
                    const hasExisting = Number.isFinite(existingLat) && Number.isFinite(existingLng);
                    const start = hasExisting ? [existingLat, existingLng] : [51.1657, 10.4515];

                    const map = window.L.map(el).setView(start, hasExisting ? 15 : 6);
                    window.L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap contributors',
                    }).addTo(map);

                    let marker = hasExisting ? window.L.marker(start, { draggable: true }).addTo(map) : null;

                    const setPoint = (latlng) => {
                        if (!marker) {
                            marker = window.L.marker(latlng, { draggable: true }).addTo(map);
                            marker.on('dragend', () => setPoint(marker.getLatLng()));
                        } else {
                            marker.setLatLng(latlng);
                        }
                        latInput.value = Number(latlng.lat).toFixed(7);
                        lngInput.value = Number(latlng.lng).toFixed(7);
                    };

                    map.on('click', (event) => setPoint(event.latlng));
                    if (marker) marker.on('dragend', () => setPoint(marker.getLatLng()));
                };

                document.addEventListener('DOMContentLoaded', init, { once: true });
                window.addEventListener('favon:leaflet-ready', init, { once: true });
                init();
            })();
        </script>
    @endpush
</x-layouts::app>
