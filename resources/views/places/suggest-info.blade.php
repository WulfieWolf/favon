@push('styles')
<style>
        #place-edit-map { height: 320px; }
    </style>
@endpush

@push('scripts')
<script>
        (() => {
            const initPlaceEditMap = () => {
                const mapElement = document.getElementById('place-edit-map');
                if (!mapElement || mapElement.dataset.initialized || typeof L === 'undefined') return;

                const latitudeInput = document.querySelector('input[name="latitude"]');
                const longitudeInput = document.querySelector('input[name="longitude"]');
                const nameInput = document.querySelector('input[name="name"]');
                const duplicatePanel = document.getElementById('duplicate-panel');
                const duplicateList = document.getElementById('duplicate-list');
                const duplicateUrl = @json(route('places.suggest.duplicates'));
                const duplicateI18n = @json(trans('places.duplicates'));
                const placeId = @json((int) $place->id);
                let duplicateTimer = null;

                const initialLat = Number.parseFloat(latitudeInput?.value);
                const initialLng = Number.parseFloat(longitudeInput?.value);
                if (!Number.isFinite(initialLat) || !Number.isFinite(initialLng)) return;

                mapElement.dataset.initialized = '1';
                const map = L.map(mapElement).setView([initialLat, initialLng], 16);
                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);

                const marker = L.marker([initialLat, initialLng], { draggable: true }).addTo(map);

                const renderDuplicates = (matches = []) => {
                    duplicateList.innerHTML = '';
                    if (!matches.length) {
                        duplicatePanel.classList.add('hidden');
                        return;
                    }

                    matches.forEach((match) => {
                        const row = document.createElement('div');
                        row.className = 'flex flex-wrap items-center justify-between gap-2 border-t border-amber-200 py-2 first:border-t-0 dark:border-amber-900';

                        const text = document.createElement('div');
                        const name = document.createElement('div');
                        name.className = 'text-sm font-medium';
                        name.textContent = match.name;

                        const meta = document.createElement('div');
                        meta.className = 'text-xs text-zinc-500 dark:text-zinc-400';
                        const status = match.publication_status === 'pending' ? duplicateI18n.pending : duplicateI18n.published;
                        meta.textContent = duplicateI18n.meta
                            .replace(':distance', String(match.distance_m))
                            .replace(':status', status);

                        text.append(name, meta);
                        row.append(text);

                        if (match.publication_status === 'published' && match.slug) {
                            const link = document.createElement('a');
                            link.href = @json(url('/places')) + '/' + encodeURIComponent(match.slug);
                            link.className = 'text-xs font-medium underline underline-offset-2';
                            link.textContent = duplicateI18n.open;
                            row.append(link);
                        }

                        duplicateList.append(row);
                    });

                    duplicatePanel.classList.remove('hidden');
                };

                const checkDuplicates = async () => {
                    const lat = Number.parseFloat(latitudeInput?.value);
                    const lng = Number.parseFloat(longitudeInput?.value);
                    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

                    const params = new URLSearchParams({
                        latitude: String(lat),
                        longitude: String(lng),
                        name: nameInput?.value?.trim() || '',
                        exclude_place_id: String(placeId),
                    });

                    try {
                        const response = await fetch(duplicateUrl + '?' + params.toString(), {
                            headers: { Accept: 'application/json' },
                        });
                        if (!response.ok) return;
                        const data = await response.json();
                        renderDuplicates(data.matches || []);
                    } catch (_) {
                        // Duplicate checking is advisory and must not block editing.
                    }
                };

                const scheduleDuplicateCheck = () => {
                    clearTimeout(duplicateTimer);
                    duplicateTimer = setTimeout(checkDuplicates, 300);
                };

                const syncMarker = () => {
                    const lat = Number.parseFloat(latitudeInput?.value);
                    const lng = Number.parseFloat(longitudeInput?.value);
                    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;
                    marker.setLatLng([lat, lng]);
                    map.panTo([lat, lng]);
                    scheduleDuplicateCheck();
                };

                marker.on('dragend', () => {
                    const position = marker.getLatLng();
                    latitudeInput.value = position.lat.toFixed(7);
                    longitudeInput.value = position.lng.toFixed(7);
                    scheduleDuplicateCheck();
                });

                map.on('click', (event) => {
                    marker.setLatLng(event.latlng);
                    latitudeInput.value = event.latlng.lat.toFixed(7);
                    longitudeInput.value = event.latlng.lng.toFixed(7);
                    scheduleDuplicateCheck();
                });

                latitudeInput?.addEventListener('change', syncMarker);
                longitudeInput?.addEventListener('change', syncMarker);
                nameInput?.addEventListener('input', scheduleDuplicateCheck);
            };

            document.addEventListener('DOMContentLoaded', initPlaceEditMap, { once: true });
            document.addEventListener('livewire:navigated', initPlaceEditMap);
            window.addEventListener('camperwolf:leaflet-ready', initPlaceEditMap);
        })();
    </script>
@endpush

<x-layouts::app :title="__('place_editing.info.page_title').' · '.$place->name">
    <div class="mx-auto w-full max-w-5xl px-5 py-8 xl:px-7">
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="text-sm font-medium text-zinc-500">{{ $canDirectEdit ? __('place_editing.info.eyebrow_edit') : __('place_editing.info.eyebrow') }}</div>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight">{{ $place->name }}</h1>
                <p class="mt-1 text-sm text-zinc-500">
                    {{ $canDirectEdit ? __('place_editing.info.intro_edit') : __('place_editing.info.intro') }}
                </p>
            </div>
            <a href="{{ route('places.show', $place->slug) }}" class="rounded-lg border border-zinc-300 px-3 py-2 text-sm font-medium hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800">
                {{ __('place_editing.common.back_to_place') }}
            </a>
        </div>
<form method="POST" action="{{ route('places.info-suggest.update', $place->slug) }}" class="space-y-6">
            @csrf

            <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold">{{ __('place_editing.info.basics_position') }}</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <label class="block md:col-span-2">
                        <span class="mb-1 block text-sm font-medium">{{ __('place_editing.info.name') }}</span>
                        <input type="text" name="name" value="{{ old('name', $place->name) }}" maxlength="255" required class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-sm font-medium">{{ __('place_editing.info.place_type') }}</span>
                        <select name="place_type_id" required class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                            @foreach ($placeTypes as $placeType)
                                <option value="{{ $placeType->id }}" @selected((int) old('place_type_id', $place->place_type_id) === (int) $placeType->id)>{{ $placeType->label }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-sm font-medium">{{ __('place_editing.info.legal_status') }}</span>
                        @php($legalStatus = old('legal_status', $place->legal_status ?? 'unclear'))
                        <select name="legal_status" required class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                            @foreach (__('place_editing.info.legal_statuses') as $value => $label)
                                <option value="{{ $value }}" @selected($legalStatus === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>

                    <div class="md:col-span-2">
                        <div id="place-edit-map" class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700"></div>
                        <p class="mt-2 text-xs text-zinc-500">{{ __('place_editing.info.map_help') }}</p>
                    </div>

                    <label class="block">
                        <span class="mb-1 block text-sm font-medium">{{ __('place_editing.info.latitude') }}</span>
                        <input type="number" step="0.0000001" name="latitude" value="{{ old('latitude', $place->latitude) }}" required class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-sm font-medium">{{ __('place_editing.info.longitude') }}</span>
                        <input type="number" step="0.0000001" name="longitude" value="{{ old('longitude', $place->longitude) }}" required class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                    </label>

                    <div id="duplicate-panel" class="hidden md:col-span-2 rounded-xl border border-amber-300 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-950/20">
                        <div class="font-semibold text-amber-900 dark:text-amber-200">{{ __('places.duplicates.title') }}</div>
                        <p class="mt-1 text-xs text-amber-800/80 dark:text-amber-300/80">{{ __('places.duplicates.help') }}</p>
                        <div id="duplicate-list" class="mt-3"></div>
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold">{{ __('place_editing.info.basic_info') }}</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <label class="block md:col-span-2">
                        <span class="mb-1 block text-sm font-medium">{{ __('place_editing.info.operator') }}</span>
                        <input type="text" name="operator_name" value="{{ old('operator_name', $details->operator_name ?? '') }}" maxlength="255" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-sm font-medium">{{ __('place_editing.info.pitch_count') }}</span>
                        <input type="number" name="pitch_count" value="{{ old('pitch_count', $details->pitch_count ?? '') }}" min="0" max="1000000" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-sm font-medium">{{ __('place_editing.info.minimum_stay_nights') }}</span>
                        <input type="number" name="minimum_stay_nights" value="{{ old('minimum_stay_nights', $details->minimum_stay_nights ?? '') }}" min="1" max="3650" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-sm font-medium">{{ __('place_editing.info.pitch_area_min_m2') }}</span>
                        <input type="number" step="0.01" name="pitch_area_min_m2" value="{{ old('pitch_area_min_m2', $details->pitch_area_min_m2 ?? '') }}" min="0.01" max="1000000" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-sm font-medium">{{ __('place_editing.info.opening_status') }}</span>
                        @php($openingStatus = old('opening_status', $place->opening_status ?? 'unclear'))
                        <select name="opening_status" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                            @foreach (__('place_editing.info.opening_statuses') as $value => $label)
                                <option value="{{ $value }}" @selected($openingStatus === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-sm font-medium">{{ __('place_editing.info.website') }}</span>
                        <input type="url" name="website" value="{{ old('website', $website->value ?? '') }}" maxlength="1024" placeholder="https://…" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-sm font-medium">{{ __('place_editing.info.phone') }}</span>
                        <input type="tel" name="phone" value="{{ old('phone', $phone->value ?? '') }}" maxlength="255" autocomplete="tel" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-sm font-medium">{{ __('place_editing.info.email') }}</span>
                        <input type="email" name="email" value="{{ old('email', $email->value ?? '') }}" maxlength="320" autocomplete="email" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                    </label>

                    <div class="md:col-span-2 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-xs leading-5 text-zinc-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-400">
                        <strong class="text-red-600 dark:text-red-400">{{ __('place_editing.info.contact_privacy_important') }}</strong>
                        {{ __('place_editing.info.contact_privacy_help') }}
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold">{{ __('place_editing.info.address') }}</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <label class="block">
                        <span class="mb-1 block text-sm font-medium">{{ __('place_editing.info.country') }}</span>
                        <select name="country_code" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                            <option value="">–</option>
                            @foreach ($countries as $code => $label)
                                <option value="{{ $code }}" @selected(old('country_code', $address->country_code ?? '') === $code)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-sm font-medium">{{ __('place_editing.info.postal_code') }}</span>
                        <input type="text" name="postal_code" value="{{ old('postal_code', $address->postal_code ?? '') }}" maxlength="32" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                    </label>

                    <label class="block md:col-span-2">
                        <span class="mb-1 block text-sm font-medium">{{ __('place_editing.info.city') }}</span>
                        <input type="text" name="city" value="{{ old('city', $address->city ?? '') }}" maxlength="255" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-sm font-medium">{{ __('place_editing.info.street') }}</span>
                        <input type="text" name="street" value="{{ old('street', $address->street ?? '') }}" maxlength="255" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-sm font-medium">{{ __('place_editing.info.house_number') }}</span>
                        <input type="text" name="house_number" value="{{ old('house_number', $address->house_number ?? '') }}" maxlength="32" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                    </label>

                    <label class="block md:col-span-2">
                        <span class="mb-1 block text-sm font-medium">{{ __('place_editing.info.address_addition') }}</span>
                        <input type="text" name="address_addition" value="{{ old('address_addition', $address->address_addition ?? '') }}" maxlength="255" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                    </label>
                </div>
            </section>

            <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold">{{ __('place_editing.info.description_access') }}</h2>
                <div class="mt-4 space-y-4">
                    <label class="block">
                        <span class="mb-1 block text-sm font-medium">{{ __('place_editing.info.description') }}</span>
                        <textarea name="description" rows="5" maxlength="10000" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">{{ old('description', $translation->description ?? '') }}</textarea>
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-sm font-medium">{{ __('place_editing.info.directions') }}</span>
                        <textarea name="directions" rows="3" maxlength="10000" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">{{ old('directions', $translation->directions ?? '') }}</textarea>
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-sm font-medium">{{ __('place_editing.info.access') }}</span>
                        <textarea name="access_information" rows="3" maxlength="10000" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">{{ old('access_information', $translation->access_information ?? '') }}</textarea>
                    </label>
                </div>
            </section>

            <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold">{{ __('place_editing.info.suitable_for') }}</h2>
                <p class="mt-1 text-xs text-zinc-500">{{ __('place_editing.info.vehicle_capacity_help') }}</p>
                <div class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    @php($oldVehicleIds = array_map('intval', old('vehicle_type_ids', $selectedVehicleIds)))
                    @foreach ($vehicleTypes as $vehicle)
                        @php($capacityValue = old('vehicle_capacities.'.$vehicle->id, $selectedVehicleCapacities[(int) $vehicle->id] ?? null))
                        <label class="flex items-center gap-2 rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700">
                            <input
                                type="checkbox"
                                name="vehicle_type_ids[]"
                                value="{{ $vehicle->id }}"
                                {{ in_array((int) $vehicle->id, $oldVehicleIds, true) ? 'checked' : '' }}
                                class="rounded border-zinc-300"
                            >
                            <span class="min-w-0 flex-1">{{ $vehicle->label }}</span>
                            <input
                                type="number"
                                name="vehicle_capacities[{{ $vehicle->id }}]"
                                value="{{ $capacityValue }}"
                                min="1"
                                max="1000000"
                                inputmode="numeric"
                                placeholder="{{ __('place_editing.info.vehicle_capacity_placeholder') }}"
                                aria-label="{{ __('place_editing.info.vehicle_capacity_aria', ['vehicle' => $vehicle->label]) }}"
                                class="w-20 rounded-md border border-zinc-300 bg-white px-2 py-1 text-right text-xs dark:border-zinc-700 dark:bg-zinc-950"
                            >
                        </label>
                    @endforeach
                </div>
            </section>

            <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <label class="block">
                    <span class="mb-1 block text-sm font-medium">{{ __('place_editing.info.comment') }} <span class="font-normal text-zinc-400">({{ __('place_editing.common.optional') }})</span></span>
                    <textarea name="comment" rows="3" maxlength="2000" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950" placeholder="{{ __('place_editing.info.comment_placeholder') }}">{{ old('comment') }}</textarea>
                </label>
            </section>

            <div class="flex flex-wrap justify-end gap-3">
                <a href="{{ route('places.show', $place->slug) }}" class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-semibold hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800">
                    {{ __('place_editing.common.cancel') }}
                </a>
                <button type="submit" class="rounded-lg bg-zinc-900 px-5 py-2 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200">
                    {{ $canDirectEdit ? __('place_editing.info.save') : __('place_editing.info.submit') }}
                </button>
            </div>
        </form>
    </div>
</x-layouts::app>
