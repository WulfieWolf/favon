<x-layouts::app :title="__('admin.place_submissions.review_title')">
    <div class="mx-auto w-full max-w-6xl space-y-6 px-4 py-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold">{{ __('admin.place_submissions.review_title') }}</h1>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('admin.place_submissions.review_intro', ['user' => $place->creator_name ?? '—']) }}</p>
            </div>
            <a href="{{ route('admin.place-submissions.index') }}" class="text-sm text-zinc-500 hover:text-zinc-950 dark:hover:text-white">{{ __('admin.place_submissions.back') }}</a>
        </div>

        <form method="POST" action="{{ route('admin.place-submissions.approve', $place->id) }}" class="space-y-6">
            @csrf

            <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="grid gap-5">
                    <div>
                        <label class="text-sm font-medium" for="name">{{ __('places.suggest.name') }}</label>
                        <input id="name" name="name" value="{{ old('name', $place->name) }}" required class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">
                    </div>

                    <div>
                        <label class="text-sm font-medium" for="place_type_id">{{ __('places.suggest.place_type') }}</label>
                        <select id="place_type_id" name="place_type_id" required class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">
                            @foreach ($placeTypes as $type)
                                <option value="{{ $type->id }}" @selected((int) old('place_type_id', $place->place_type_id) === (int) $type->id)>{{ __("place_types.{$type->slug}.name") }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="text-sm font-medium" for="description">{{ __('places.suggest.description') }}</label>
                        <textarea id="description" name="description" rows="4" maxlength="2000" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">{{ old('description', $place->description) }}</textarea>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="text-sm font-medium" for="environment">{{ __('places.suggest.environment') }}</label>
                            <select id="environment" name="environment" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">
                                <option value="">{{ __('admin.place_submissions.unknown') }}</option>
                                @foreach (['outdoor', 'indoor', 'both'] as $value)
                                    <option value="{{ $value }}" @selected(old('environment', $place->environment) === $value)>{{ __("places.suggest.environment_{$value}") }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-medium" for="has_gloryhole">{{ __('places.suggest.gloryhole') }}</label>
                            <select id="has_gloryhole" name="has_gloryhole" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">
                                <option value="">{{ __('admin.place_submissions.unknown') }}</option>
                                <option value="1" @selected((string) old('has_gloryhole', $place->has_gloryhole === null ? '' : '1') === '1')>{{ __('global.yes') }}</option>
                                <option value="0" @selected((string) old('has_gloryhole', $place->has_gloryhole === null ? '' : '0') === '0')>{{ __('global.no') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold">{{ __('places.suggest.position') }}</h2>
                <div id="favon-place-review-map" class="mt-4 h-80 overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-800"></div>

                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    <input id="latitude" name="latitude" value="{{ old('latitude', $place->latitude) }}" required class="rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">
                    <input id="longitude" name="longitude" value="{{ old('longitude', $place->longitude) }}" required class="rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">
                </div>

                <div class="mt-4 grid gap-3 md:grid-cols-6">
                    <input name="street" value="{{ old('street', $place->street) }}" placeholder="{{ __('places.suggest.street') }}" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 md:col-span-4 dark:border-zinc-700 dark:bg-zinc-950">
                    <input name="house_number" value="{{ old('house_number', $place->house_number) }}" placeholder="{{ __('places.suggest.house_number') }}" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 md:col-span-2 dark:border-zinc-700 dark:bg-zinc-950">
                    <input name="postal_code" value="{{ old('postal_code', $place->postal_code) }}" placeholder="{{ __('places.suggest.postal_code') }}" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 md:col-span-2 dark:border-zinc-700 dark:bg-zinc-950">
                    <input name="city" value="{{ old('city', $place->city) }}" placeholder="{{ __('places.suggest.city') }}" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 md:col-span-3 dark:border-zinc-700 dark:bg-zinc-950">
                    <input name="country_code" value="{{ old('country_code', $place->country_code) }}" placeholder="DE" maxlength="2" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 uppercase md:col-span-1 dark:border-zinc-700 dark:bg-zinc-950">
                </div>
            </section>

            @if ($errors->any())
                <div class="rounded-xl border border-red-300 bg-red-50 p-4 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/30 dark:text-red-300">{{ __('places.common.check_inputs') }}</div>
            @endif

            <div class="flex justify-end">
                <button type="submit" class="rounded-lg bg-emerald-700 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-800">{{ __('admin.place_submissions.approve') }}</button>
            </div>
        </form>

        <section class="rounded-xl border border-red-300 bg-red-50 p-5 dark:border-red-900 dark:bg-red-950/20">
            <h2 class="font-semibold text-red-900 dark:text-red-200">{{ __('admin.place_submissions.reject') }}</h2>
            <p class="mt-1 text-sm text-red-700 dark:text-red-300">{{ __('admin.place_submissions.reject_help') }}</p>
            <form method="POST" action="{{ route('admin.place-submissions.reject', $place->id) }}" class="mt-4 flex flex-col gap-3 md:flex-row">
                @csrf
                <input name="reason" required maxlength="1000" placeholder="{{ __('admin.place_submissions.reject_reason') }}" class="flex-1 rounded-lg border border-red-300 bg-white px-3 py-2 dark:border-red-900 dark:bg-zinc-950">
                <button type="submit" class="rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white hover:bg-red-800">{{ __('admin.place_submissions.reject') }}</button>
            </form>
        </section>
    </div>

    @push('scripts')
        <script>
            (() => {
                const init = () => {
                    const el = document.getElementById('favon-place-review-map');
                    if (!el || el.dataset.ready || !window.L) return;
                    el.dataset.ready = '1';

                    const latInput = document.getElementById('latitude');
                    const lngInput = document.getElementById('longitude');
                    const start = [Number.parseFloat(latInput.value), Number.parseFloat(lngInput.value)];
                    const map = window.L.map(el).setView(start, 16);
                    window.L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap contributors',
                    }).addTo(map);

                    const marker = window.L.marker(start, { draggable: true }).addTo(map);
                    const setPoint = (latlng) => {
                        marker.setLatLng(latlng);
                        latInput.value = Number(latlng.lat).toFixed(7);
                        lngInput.value = Number(latlng.lng).toFixed(7);
                    };

                    map.on('click', (event) => setPoint(event.latlng));
                    marker.on('dragend', () => setPoint(marker.getLatLng()));
                };

                document.addEventListener('DOMContentLoaded', init, { once: true });
                window.addEventListener('favon:leaflet-ready', init, { once: true });
                init();
            })();
        </script>
    @endpush
</x-layouts::app>
