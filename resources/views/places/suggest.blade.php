@push('styles')
<style>
        #suggest-map { min-height: 420px; }
        #suggest-map .leaflet-control-attribution { font-size: 10px; }
        .dark #suggest-map .leaflet-layer,
        .dark #suggest-map .leaflet-control-zoom-in,
        .dark #suggest-map .leaflet-control-zoom-out,
        .dark #suggest-map .leaflet-control-attribution { filter: brightness(.82) contrast(1.15); }
    </style>
@endpush

@php
    $countries = app(\App\Services\CountryService::class)->all(app()->getLocale());
@endphp

@push('scripts')
<script>
        (() => {
            const initSuggestMap = () => {
            const mapElement = document.getElementById('suggest-map');
            const latitudeInput = document.getElementById('latitude');
            const longitudeInput = document.getElementById('longitude');
            const addressSearchInput = document.getElementById('address-search');
            const addressSearchButton = document.getElementById('address-search-button');
            const addressSearchResults = document.getElementById('address-search-results');
            const addressSearchStatus = document.getElementById('address-search-status');
            const countryCodeInput = document.querySelector('[name="country_code"]');
            const streetInput = document.querySelector('input[name="street"]');
            const houseNumberInput = document.querySelector('input[name="house_number"]');
            const postalCodeInput = document.querySelector('input[name="postal_code"]');
            const cityInput = document.querySelector('input[name="city"]');
            const geocoderLanguage = @json(app()->getLocale());
            const i18n = @json(trans('places.geocoder'));
            const duplicateI18n = @json(trans('places.duplicates'));
            const duplicateUrl = @json(route('places.suggest.duplicates'));
            const nameInput = document.querySelector('input[name="name"]');
            const placeTypeSelect = document.getElementById('place-type');
            const placeTypeDescription = document.getElementById('place-type-description');
            const duplicatePanel = document.getElementById('duplicate-panel');
            const duplicateList = document.getElementById('duplicate-list');
            let duplicateTimer = null;

            if (!mapElement || !latitudeInput || !longitudeInput || typeof L === 'undefined') {
                return;
            }

            const oldLat = Number.parseFloat(latitudeInput.value);
            const oldLng = Number.parseFloat(longitudeInput.value);
            const hasOldCoordinates = Number.isFinite(oldLat) && Number.isFinite(oldLng);
            const initialLat = hasOldCoordinates ? oldLat : 51.1657;
            const initialLng = hasOldCoordinates ? oldLng : 10.4515;
            const initialZoom = hasOldCoordinates ? 14 : 6;

            const map = L.map(mapElement).setView([initialLat, initialLng], initialZoom);
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors',
            }).addTo(map);

            let marker = null;
            let searchTimer = null;
            let searchController = null;
            let reverseController = null;

            const setPosition = (lat, lng, center = false) => {
                latitudeInput.value = Number(lat).toFixed(7);
                longitudeInput.value = Number(lng).toFixed(7);

                if (!marker) {
                    marker = L.marker([lat, lng], { draggable: true }).addTo(map);
                    marker.on('dragend', async () => {
                        const point = marker.getLatLng();
                        setPosition(point.lat, point.lng, false);
                        await reverseGeocode(point.lat, point.lng);
                    });
                } else {
                    marker.setLatLng([lat, lng]);
                }

                if (center) {
                    map.setView([lat, lng], Math.max(map.getZoom(), 16));
                }

                scheduleDuplicateCheck();
            };

            const updatePlaceTypeDescription = () => {
                if (!placeTypeSelect || !placeTypeDescription) return;
                const option = placeTypeSelect.options[placeTypeSelect.selectedIndex];
                placeTypeDescription.textContent = option?.dataset?.description || '';
                placeTypeDescription.classList.toggle('hidden', !placeTypeDescription.textContent);
            };

            const renderDuplicates = (matches = []) => {
                if (!duplicatePanel || !duplicateList) return;
                duplicateList.innerHTML = '';

                if (!Array.isArray(matches) || matches.length === 0) {
                    duplicatePanel.classList.add('hidden');
                    return;
                }

                matches.forEach((match) => {
                    const item = document.createElement('div');
                    item.className = 'flex flex-wrap items-center justify-between gap-2 border-t border-amber-200 py-2 first:border-t-0 dark:border-amber-900';

                    const left = document.createElement('div');
                    const name = document.createElement('div');
                    name.className = 'text-sm font-medium';
                    name.textContent = match.name;

                    const meta = document.createElement('div');
                    meta.className = 'text-xs text-zinc-500 dark:text-zinc-400';
                    const status = match.publication_status === 'pending'
                        ? duplicateI18n.pending
                        : duplicateI18n.published;
                    meta.textContent = duplicateI18n.meta
                        .replace(':distance', String(match.distance_m))
                        .replace(':status', status);

                    left.append(name, meta);
                    item.append(left);

                    if (match.publication_status === 'published' && match.slug) {
                        const link = document.createElement('a');
                        link.href = @json(url('/places')) + '/' + encodeURIComponent(match.slug);
                        link.className = 'text-xs font-medium underline underline-offset-2';
                        link.textContent = duplicateI18n.open;
                        item.append(link);
                    }

                    duplicateList.append(item);
                });

                duplicatePanel.classList.remove('hidden');
            };

            const checkDuplicates = async () => {
                const lat = Number.parseFloat(latitudeInput.value);
                const lng = Number.parseFloat(longitudeInput.value);
                if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                    renderDuplicates([]);
                    return;
                }

                const params = new URLSearchParams({
                    latitude: String(lat),
                    longitude: String(lng),
                    name: nameInput?.value?.trim() || '',
                });

                try {
                    const response = await fetch(duplicateUrl + '?' + params.toString(), {
                        headers: { Accept: 'application/json' },
                    });
                    if (!response.ok) return;
                    const data = await response.json();
                    renderDuplicates(data.matches || []);
                } catch (error) {
                    console.error('Duplicate check failed', error);
                }
            };

            const scheduleDuplicateCheck = () => {
                window.clearTimeout(duplicateTimer);
                duplicateTimer = window.setTimeout(checkDuplicates, 300);
            };

            const fillAddressFields = (properties = {}) => {
                if (countryCodeInput && properties.countrycode) {
                    const code = String(properties.countrycode).toUpperCase();
                    if ([...countryCodeInput.options].some((option) => option.value === code)) {
                        countryCodeInput.value = code;
                    }
                }

                if (streetInput) streetInput.value = properties.street ?? '';
                if (houseNumberInput) houseNumberInput.value = properties.housenumber ?? '';
                if (postalCodeInput) postalCodeInput.value = properties.postcode ?? '';
                if (cityInput) {
                    cityInput.value = properties.city
                        ?? properties.locality
                        ?? properties.district
                        ?? properties.county
                        ?? '';
                }
            };

            const formatAddress = (properties = {}) => {
                const streetLine = [properties.street, properties.housenumber].filter(Boolean).join(' ');
                const cityLine = [properties.postcode, properties.city ?? properties.locality ?? properties.district].filter(Boolean).join(' ');
                const parts = [streetLine, cityLine, properties.state, properties.country]
                    .filter(Boolean)
                    .filter((value, index, array) => array.indexOf(value) === index);

                return parts.join(', ') || properties.name || i18n.unknown;
            };

            const clearSearchResults = () => {
                if (!addressSearchResults) return;
                addressSearchResults.innerHTML = '';
                addressSearchResults.classList.add('hidden');
            };

            const renderSearchResults = (features) => {
                clearSearchResults();

                if (!addressSearchResults || !Array.isArray(features) || features.length === 0) {
                    if (addressSearchStatus) addressSearchStatus.textContent = i18n.none;
                    return;
                }

                features.forEach((feature) => {
                    const coordinates = feature?.geometry?.coordinates ?? [];
                    const lng = Number.parseFloat(coordinates[0]);
                    const lat = Number.parseFloat(coordinates[1]);
                    const properties = feature?.properties ?? {};
                    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'block w-full border-b border-zinc-200 px-3 py-2 text-left text-sm last:border-b-0 hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800';
                    button.textContent = formatAddress(properties);
                    button.addEventListener('click', () => {
                        setPosition(lat, lng, true);
                        fillAddressFields(properties);
                        addressSearchInput.value = formatAddress(properties);
                        clearSearchResults();
                        if (addressSearchStatus) addressSearchStatus.textContent = i18n.accepted;
                    });
                    addressSearchResults.appendChild(button);
                });

                if (addressSearchResults.children.length > 0) {
                    addressSearchResults.classList.remove('hidden');
                    if (addressSearchStatus) addressSearchStatus.textContent = `${addressSearchResults.children.length}${i18n.found_suffix}`;
                }
            };

            const performAddressSearch = async () => {
                if (!addressSearchInput || !addressSearchResults) return;

                const query = addressSearchInput.value.trim();
                if (query.length < 3) {
                    clearSearchResults();
                    if (addressSearchStatus) addressSearchStatus.textContent = i18n.min_chars;
                    return;
                }

                if (searchController) searchController.abort();
                searchController = new AbortController();
                if (addressSearchStatus) addressSearchStatus.textContent = i18n.searching;
                if (addressSearchButton) addressSearchButton.disabled = true;

                try {
                    const center = map.getCenter();
                    const params = new URLSearchParams({
                        q: query,
                        limit: '7',
                        lang: geocoderLanguage,
                        lat: String(center.lat),
                        lon: String(center.lng),
                    });
                    const response = await fetch(`https://photon.komoot.io/api/?${params.toString()}`, {
                        headers: { 'Accept': 'application/json' },
                        signal: searchController.signal,
                    });
                    if (!response.ok) throw new Error(`HTTP ${response.status}`);
                    const data = await response.json();
                    renderSearchResults(data?.features ?? []);
                } catch (error) {
                    if (error.name === 'AbortError') return;
                    console.error('Address search failed', error);
                    clearSearchResults();
                    if (addressSearchStatus) addressSearchStatus.textContent = i18n.unavailable;
                } finally {
                    if (addressSearchButton) addressSearchButton.disabled = false;
                }
            };

            const reverseGeocode = async (lat, lng) => {
                if (reverseController) reverseController.abort();
                reverseController = new AbortController();
                if (addressSearchStatus) addressSearchStatus.textContent = i18n.reverse_searching;

                try {
                    const params = new URLSearchParams({
                        lat: String(lat),
                        lon: String(lng),
                        limit: '1',
                        lang: geocoderLanguage,
                    });
                    const response = await fetch(`https://photon.komoot.io/reverse?${params.toString()}`, {
                        headers: { 'Accept': 'application/json' },
                        signal: reverseController.signal,
                    });
                    if (!response.ok) throw new Error(`HTTP ${response.status}`);

                    const data = await response.json();
                    const feature = data?.features?.[0];
                    if (!feature) {
                        if (addressSearchStatus) addressSearchStatus.textContent = i18n.reverse_none;
                        return;
                    }

                    const properties = feature.properties ?? {};
                    fillAddressFields(properties);
                    if (addressSearchInput) addressSearchInput.value = formatAddress(properties);
                    clearSearchResults();
                    if (addressSearchStatus) addressSearchStatus.textContent = i18n.reverse_done;
                } catch (error) {
                    if (error.name === 'AbortError') return;
                    console.error('Reverse geocoding failed', error);
                    if (addressSearchStatus) addressSearchStatus.textContent = i18n.reverse_failed;
                }
            };

            if (hasOldCoordinates) setPosition(oldLat, oldLng, false);
            updatePlaceTypeDescription();
            placeTypeSelect?.addEventListener('change', updatePlaceTypeDescription);
            nameInput?.addEventListener('input', scheduleDuplicateCheck);

            map.on('click', async (event) => {
                setPosition(event.latlng.lat, event.latlng.lng, false);
                await reverseGeocode(event.latlng.lat, event.latlng.lng);
            });

            const syncFromInputs = async () => {
                const lat = Number.parseFloat(latitudeInput.value);
                const lng = Number.parseFloat(longitudeInput.value);
                if (Number.isFinite(lat) && Number.isFinite(lng)) {
                    setPosition(lat, lng, true);
                    await reverseGeocode(lat, lng);
                }
            };

            latitudeInput.addEventListener('change', syncFromInputs);
            longitudeInput.addEventListener('change', syncFromInputs);
            if (addressSearchButton) addressSearchButton.addEventListener('click', performAddressSearch);

            if (addressSearchInput) {
                addressSearchInput.addEventListener('input', () => {
                    window.clearTimeout(searchTimer);
                    if (addressSearchInput.value.trim().length < 3) {
                        clearSearchResults();
                        return;
                    }
                    searchTimer = window.setTimeout(performAddressSearch, 350);
                });

                addressSearchInput.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        window.clearTimeout(searchTimer);
                        performAddressSearch();
                    }
                    if (event.key === 'Escape') clearSearchResults();
                });
            }

            document.addEventListener('click', (event) => {
                if (!addressSearchResults || !addressSearchInput) return;
                if (!addressSearchResults.contains(event.target) && event.target !== addressSearchInput) clearSearchResults();
            });
            };

            if (typeof L !== 'undefined') {
                initSuggestMap();
            } else {
                window.addEventListener('camperwolf:leaflet-ready', initSuggestMap, { once: true });
            }
        })();
    </script>
@endpush

<x-layouts::app :title="$canPublishDirectly ? __('places.suggest.title_direct') : __('places.suggest.title')">
    <div class="mx-auto w-full max-w-6xl px-5 py-8 xl:px-7">
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-baseline gap-x-4 gap-y-1">
                    <h1 class="text-2xl font-semibold tracking-tight">{{ $canPublishDirectly ? __('places.suggest.title_direct') : __('places.suggest.title') }}</h1>
                    <span class="text-xs font-medium text-zinc-900 dark:text-white">(<a href="{{ route('help.show', 'platz-vorschlagen-schritt-fuer-schritt') }}" class="underline decoration-zinc-400 underline-offset-2 hover:text-zinc-600 dark:decoration-zinc-500 dark:hover:text-zinc-300">{{ __('places.suggest_help') }}</a>)</span>
                </div>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $canPublishDirectly ? __('places.suggest.intro_direct') : __('places.suggest.intro') }}</p>
            </div>
            <a href="{{ route('dashboard') }}" class="rounded-lg border border-zinc-300 px-3 py-2 text-sm font-medium hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800">{{ __('places.common.back_overview') }}</a>
        </div>

        <form method="POST" action="{{ route('places.suggest.store') }}" class="space-y-6" novalidate>
            @csrf

            <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold">{{ __('places.suggest.basic') }}</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <label class="block md:col-span-2">
                        <span class="mb-1 block text-sm font-medium">{{ __('places.suggest.name') }} *</span>
                        <input type="text" name="name" value="{{ old('name') }}" required maxlength="255" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                    </label>
                    <label class="block md:col-span-2">
                        <span class="mb-1 block text-sm font-medium">{{ __('places.suggest.place_type') }} *</span>
                        <select id="place-type" name="place_type_id" required class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                            <option value="">{{ __('places.suggest.choose') }}</option>
                            @foreach ($placeTypes as $placeType)
                                <option value="{{ $placeType->id }}" data-description="{{ $placeType->description }}" @selected((string) old('place_type_id') === (string) $placeType->id)>{{ $placeType->label }}</option>
                            @endforeach
                        </select>
                        <p id="place-type-description" class="mt-2 hidden text-xs leading-5 text-zinc-500 dark:text-zinc-400"></p>
                    </label>
                </div>
            </section>

            <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold">{{ __('places.suggest.position') }} *</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('places.suggest.position_help') }}</p>

                <div class="relative mt-4">
                    <div class="flex gap-2">
                        <input id="address-search" type="search" autocomplete="off" placeholder="{{ __('places.suggest.search_placeholder') }}" class="min-w-0 flex-1 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                        <button id="address-search-button" type="button" class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium hover:bg-zinc-100 disabled:cursor-wait disabled:opacity-60 dark:border-zinc-700 dark:hover:bg-zinc-800">{{ __('places.suggest.search') }}</button>
                    </div>
                    <div id="address-search-results" class="absolute z-[1000] mt-1 hidden max-h-72 w-full overflow-y-auto rounded-lg border border-zinc-300 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900"></div>
                    <p id="address-search-status" class="mt-1 min-h-5 text-xs text-zinc-500 dark:text-zinc-400">{{ __('places.suggest.search_note') }}</p>
                </div>

                <div class="mt-4 grid gap-4 lg:grid-cols-[1fr_320px]">
                    <div id="suggest-map" class="overflow-hidden rounded-xl border border-zinc-300 dark:border-zinc-700"></div>
                    <div class="grid content-start gap-4">
                        <label class="block">
                            <span class="mb-1 block text-sm font-medium">{{ __('places.suggest.latitude') }} *</span>
                            <input id="latitude" type="number" step="0.0000001" min="-90" max="90" name="latitude" value="{{ old('latitude') }}" required class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-sm font-medium">{{ __('places.suggest.longitude') }} *</span>
                            <input id="longitude" type="number" step="0.0000001" min="-180" max="180" name="longitude" value="{{ old('longitude') }}" required class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                        </label>
                    </div>
                </div>

                <div id="duplicate-panel" class="mt-4 hidden rounded-xl border border-amber-300 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-950/20">
                    <div class="font-semibold text-amber-900 dark:text-amber-200">{{ __('places.duplicates.title') }}</div>
                    <p class="mt-1 text-xs text-amber-800/80 dark:text-amber-300/80">{{ __('places.duplicates.help') }}</p>
                    <div id="duplicate-list" class="mt-3"></div>
                </div>
            </section>

            <div class="hidden" aria-hidden="true">
                <input type="text" name="street" value="{{ old('street') }}">
                <input type="text" name="house_number" value="{{ old('house_number') }}">
                <input type="text" name="postal_code" value="{{ old('postal_code') }}">
                <input type="text" name="city" value="{{ old('city') }}">
                <input type="text" name="address_addition" value="{{ old('address_addition') }}">
                <select name="country_code">
                    <option value="">—</option>
                    @foreach ($countries as $countryCode => $countryName)
                        <option value="{{ $countryCode }}" @selected(old('country_code', 'DE') === $countryCode)>{{ $countryName }}</option>
                    @endforeach
                </select>
            </div>

            <section class="rounded-xl border p-5" style="border-color:#3f3f46;background:#18181b;color:#f4f4f5">
                <h2 class="text-base font-semibold" style="color:#fafafa">{{ $canPublishDirectly ? __('places.suggest.finish_title_direct') : __('places.suggest.finish_title') }}</h2>
                <p class="mt-1 text-sm" style="color:#d4d4d8">{{ $canPublishDirectly ? __('places.suggest.finish_help_direct') : __('places.suggest.finish_help') }}</p>
                <div class="mt-4 flex flex-wrap justify-end gap-3">
                    <a href="{{ route('dashboard') }}" class="rounded-lg border px-4 py-2 text-sm font-medium" style="border-color:#52525b;color:#f4f4f5;background:#27272a">{{ __('places.common.cancel') }}</a>
                    <button type="submit" name="intent" value="submit" class="rounded-lg border px-4 py-2 text-sm font-semibold" style="border-color:#71717a;color:#fafafa;background:#27272a">
                        {{ $canPublishDirectly ? __('places.suggest.submit_direct') : __('places.suggest.submit_now') }}
                    </button>
                    <button type="submit" name="intent" value="draft" class="rounded-lg px-5 py-2 text-sm font-semibold" style="background:#fafafa;color:#18181b">
                        {{ __('places.suggest.continue_quick_features') }}
                    </button>
                </div>
            </section>
        </form>
    </div>
</x-layouts::app>