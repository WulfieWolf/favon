import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

window.L = L;
window.dispatchEvent(new CustomEvent('favon:leaflet-ready'));


function photonLabel(feature) {
    const properties = feature?.properties || {};
    const parts = [
        properties.name,
        [properties.street, properties.housenumber].filter(Boolean).join(' '),
        [properties.postcode, properties.city || properties.locality || properties.district].filter(Boolean).join(' '),
        properties.country,
    ].filter(Boolean);

    return [...new Set(parts)].join(', ');
}

function photonAddress(feature) {
    const properties = feature?.properties || {};
    const street = [properties.street, properties.housenumber].filter(Boolean).join(' ').trim();

    return {
        streetAddress: street || null,
        postalCode: properties.postcode || null,
        city: properties.city || properties.locality || properties.district || properties.county || null,
        countryCode: (properties.countrycode || '').toUpperCase() || null,
    };
}

function fillAddressFields(element, feature) {
    const address = photonAddress(feature);
    const streetInput = document.getElementById(element.dataset.streetInput || '');
    const postalInput = document.getElementById(element.dataset.postalInput || '');
    const cityInput = document.getElementById(element.dataset.cityInput || '');
    const countryInput = document.getElementById(element.dataset.countryInput || '');

    if (streetInput && address.streetAddress !== null) streetInput.value = address.streetAddress;
    if (postalInput && address.postalCode !== null) postalInput.value = address.postalCode;
    if (cityInput && address.city !== null) cityInput.value = address.city;

    if (countryInput && address.countryCode) {
        const option = Array.from(countryInput.options || []).find((item) => item.value === address.countryCode);
        if (option) countryInput.value = address.countryCode;
    }
}

async function photonReverse(element, latlng) {
    const locale = element.dataset.locale || document.documentElement.lang || 'en';
    const url = new URL('https://photon.komoot.io/reverse');
    url.searchParams.set('lat', Number(latlng.lat).toFixed(7));
    url.searchParams.set('lon', Number(latlng.lng).toFixed(7));
    url.searchParams.set('limit', '1');
    url.searchParams.set('lang', locale.split('-')[0]);

    const response = await fetch(url, {
        headers: { Accept: 'application/json' },
    });

    if (!response.ok) {
        throw new Error('Reverse geocoding failed');
    }

    const payload = await response.json();
    return Array.isArray(payload.features) ? payload.features[0] || null : null;
}

function initFavonPlacePickers() {
    document.querySelectorAll('[data-favon-place-picker]').forEach((element) => {
        if (element.dataset.ready === '1') {
            return;
        }

        const latInput = document.getElementById(element.dataset.latInput || '');
        const lngInput = document.getElementById(element.dataset.lngInput || '');
        const searchInput = document.getElementById(element.dataset.searchInput || '');

        if (!latInput || !lngInput) {
            return;
        }

        element.dataset.ready = '1';

        const existingLat = Number.parseFloat(latInput.value);
        const existingLng = Number.parseFloat(lngInput.value);
        const hasExisting = Number.isFinite(existingLat) && Number.isFinite(existingLng);
        const start = hasExisting ? [existingLat, existingLng] : [51.1657, 10.4515];

        const map = L.map(element).setView(start, hasExisting ? 15 : 6);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(map);

        element._favonMap = map;

        let marker = hasExisting ? L.marker(start, { draggable: true }).addTo(map) : null;
        let reverseRequest = 0;

        const setPoint = async (latlng, options = {}) => {
            if (!marker) {
                marker = L.marker(latlng, { draggable: true }).addTo(map);
                marker.on('dragend', () => setPoint(marker.getLatLng(), { reverse: true }));
            } else {
                marker.setLatLng(latlng);
            }

            latInput.value = Number(latlng.lat).toFixed(7);
            lngInput.value = Number(latlng.lng).toFixed(7);

            if (options.zoom !== false) {
                map.setView(latlng, Math.max(map.getZoom(), 16));
            }

            if (!options.reverse) {
                return;
            }

            const requestId = ++reverseRequest;

            try {
                const feature = await photonReverse(element, latlng);
                if (requestId !== reverseRequest || !feature) {
                    return;
                }

                fillAddressFields(element, feature);

                if (searchInput) {
                    searchInput.value = photonLabel(feature);
                }
            } catch {
                // Position is still valid even if reverse geocoding is unavailable.
            }
        };

        element._favonSetPoint = setPoint;

        map.on('click', (event) => setPoint(event.latlng, { reverse: true }));

        if (marker) {
            marker.on('dragend', () => setPoint(marker.getLatLng(), { reverse: true }));
        }

        requestAnimationFrame(() => map.invalidateSize());
    });
}

function initFavonAddressSearch() {
    document.querySelectorAll('[data-favon-address-search]').forEach((input) => {
        if (input.dataset.ready === '1') {
            return;
        }

        const results = document.getElementById(input.dataset.results || '');
        const mapElement = document.getElementById(input.dataset.map || '');

        if (!results || !mapElement) {
            return;
        }

        input.dataset.ready = '1';

        let debounceTimer = null;
        let requestController = null;

        const hideResults = () => {
            results.classList.add('hidden');
            results.innerHTML = '';
        };

        const renderMessage = (message) => {
            results.innerHTML = '';
            const item = document.createElement('div');
            item.className = 'px-3 py-2 text-sm text-zinc-500 dark:text-zinc-400';
            item.textContent = message;
            results.appendChild(item);
            results.classList.remove('hidden');
        };

        const renderFeatures = (features) => {
            results.innerHTML = '';

            if (!features.length) {
                renderMessage(mapElement.dataset.geocoderNone || 'No results');
                return;
            }

            features.forEach((feature) => {
                const coordinates = feature?.geometry?.coordinates;
                if (!Array.isArray(coordinates) || coordinates.length < 2) {
                    return;
                }

                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'block w-full border-b border-zinc-100 px-3 py-2 text-left text-sm last:border-b-0 hover:bg-zinc-100 dark:border-zinc-800 dark:hover:bg-zinc-800';
                button.textContent = photonLabel(feature);

                button.addEventListener('click', async () => {
                    const latlng = L.latLng(Number(coordinates[1]), Number(coordinates[0]));
                    input.value = photonLabel(feature);
                    fillAddressFields(mapElement, feature);
                    hideResults();

                    if (typeof mapElement._favonSetPoint === 'function') {
                        await mapElement._favonSetPoint(latlng, { reverse: false });
                    }
                });

                results.appendChild(button);
            });

            results.classList.remove('hidden');
        };

        input.addEventListener('input', () => {
            window.clearTimeout(debounceTimer);

            const query = input.value.trim();
            if (query.length < 3) {
                hideResults();
                return;
            }

            debounceTimer = window.setTimeout(async () => {
                requestController?.abort();
                requestController = new AbortController();

                renderMessage(mapElement.dataset.geocoderSearching || 'Searching…');

                try {
                    const locale = mapElement.dataset.locale || document.documentElement.lang || 'en';
                    const url = new URL('https://photon.komoot.io/api');
                    url.searchParams.set('q', query);
                    url.searchParams.set('limit', '6');
                    url.searchParams.set('lang', locale.split('-')[0]);

                    const map = mapElement._favonMap;
                    if (map) {
                        const center = map.getCenter();
                        url.searchParams.set('lat', Number(center.lat).toFixed(5));
                        url.searchParams.set('lon', Number(center.lng).toFixed(5));
                        url.searchParams.set('zoom', String(Math.round(map.getZoom())));
                    }

                    const response = await fetch(url, {
                        signal: requestController.signal,
                        headers: { Accept: 'application/json' },
                    });

                    if (!response.ok) {
                        throw new Error('Address search failed');
                    }

                    const payload = await response.json();
                    renderFeatures(Array.isArray(payload.features) ? payload.features : []);
                } catch (error) {
                    if (error.name === 'AbortError') {
                        return;
                    }

                    renderMessage(mapElement.dataset.geocoderUnavailable || 'Address search unavailable');
                }
            }, 450);
        });

        input.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                hideResults();
            }
        });

        document.addEventListener('click', (event) => {
            if (!input.contains(event.target) && !results.contains(event.target)) {
                hideResults();
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initFavonPlacePickers();
    initFavonAddressSearch();
}, { once: true });

document.addEventListener('livewire:navigated', () => {
    initFavonPlacePickers();
    initFavonAddressSearch();
});

initFavonPlacePickers();
initFavonAddressSearch();
