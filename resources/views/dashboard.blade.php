@push('styles')
<style>
        #place-map .leaflet-control-attribution { font-size: 10px; }
        .dark #place-map .leaflet-layer,
        .dark #place-map .leaflet-control-zoom-in,
        .dark #place-map .leaflet-control-zoom-out,
        .dark #place-map .leaflet-control-attribution { filter: brightness(.82) contrast(1.15); }

        #place-map .cw-place-marker {
            background: transparent;
            border: 0;
            transition: transform .12s ease;
            transform-origin: 50% 100%;
        }

        #place-map .cw-place-marker .cw-marker-shape {
            fill: #2563eb;
            stroke: #1e3a8a;
            transition: fill .12s ease, stroke .12s ease;
        }

        #place-map .cw-place-marker.is-favorite .cw-marker-shape {
            fill: #dc2626;
            stroke: #7f1d1d;
        }

        #place-map .cw-place-marker.is-hovered {
            transform: scale(1.2);
            z-index: 1000 !important;
        }

        #place-map .cw-place-marker.is-hovered .cw-marker-shape {
            fill: #16a34a;
            stroke: #14532d;
        }

        .cw-double-chevron-up {
            position: relative;
            display: inline-block;
            width: 18px;
            height: 14px;
        }

        .cw-double-chevron-up::before,
        .cw-double-chevron-up::after {
            content: '';
            position: absolute;
            left: 50%;
            width: 9px;
            height: 9px;
            border-left: 2px solid currentColor;
            border-top: 2px solid currentColor;
            transform: translateX(-50%) rotate(45deg);
        }

        .cw-double-chevron-up::before {
            top: 1px;
        }

        .cw-double-chevron-up::after {
            top: 6px;
        }

        .cw-double-chevron-down {
            position: relative;
            display: inline-block;
            width: 18px;
            height: 14px;
        }

        .cw-double-chevron-down::before,
        .cw-double-chevron-down::after {
            content: '';
            position: absolute;
            left: 50%;
            width: 9px;
            height: 9px;
            border-right: 2px solid currentColor;
            border-bottom: 2px solid currentColor;
            transform: translateX(-50%) rotate(45deg);
        }

        .cw-double-chevron-down::before {
            top: -1px;
        }

        .cw-double-chevron-down::after {
            top: 4px;
        }

        .cw-double-chevron-up.hidden,
        .cw-double-chevron-down.hidden {
            display: none;
        }

        .browse-place-card-body {
            display: flex;
            gap: 1rem;
        }

        .browse-place-photo {
            display: grid;
            width: 7rem;
            height: 5.25rem;
            flex: 0 0 7rem;
            place-items: center;
            overflow: hidden;
            border: 1px solid rgb(228 228 231);
            border-radius: .5rem;
            background: rgb(244 244 245);
        }

        .dark .browse-place-photo {
            border-color: rgb(63 63 70);
            background: rgb(39 39 42);
        }

        .browse-place-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .browse-place-photo-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .15rem;
            width: 100%;
            height: 100%;
            padding: .75rem;
            color: rgb(113 113 122);
        }

        .dark .browse-place-photo-placeholder {
            color: rgb(161 161 170);
        }

        .browse-place-photo-placeholder svg {
            width: 2rem;
            height: 2rem;
            stroke-width: 1.5;
        }

        .browse-place-photo-placeholder.is-multiple svg {
            width: 1.5rem;
            height: 1.5rem;
        }

        .browse-place-photo-placeholder.is-multiple svg:nth-child(2) {
            width: 2.05rem;
            height: 2.05rem;
        }

        @media (max-width: 767px) {
            .browse-place-photo-placeholder svg {
                width: 3.5rem;
                height: 3.5rem;
            }

            .browse-place-photo-placeholder.is-multiple {
                gap: .3rem;
            }

            .browse-place-photo-placeholder.is-multiple svg {
                width: 2.6rem;
                height: 2.6rem;
            }

            .browse-place-photo-placeholder.is-multiple svg:nth-child(2) {
                width: 3.5rem;
                height: 3.5rem;
            }
        }

        #browse-layout {
            --map-width: 42vw;
            display: grid;
            grid-template-columns: 280px minmax(320px, 1fr) 9px minmax(320px, var(--map-width));
        }

        #browse-layout.results-hidden {
            grid-template-columns: 280px 0 9px minmax(320px, 1fr);
        }

        #browse-layout.map-hidden {
            grid-template-columns: 280px minmax(320px, 1fr) 9px 0;
        }

        #browse-layout.results-hidden #results-panel,
        #browse-layout.map-hidden #map-panel {
            overflow: hidden;
            visibility: hidden;
        }

        #browse-resizer {
            cursor: col-resize;
            touch-action: none;
        }

        #browse-resizer::before {
            content: '';
            position: absolute;
            inset: 0 3px;
            background: rgb(212 212 216);
        }

        .dark #browse-resizer::before {
            background: rgb(63 63 70);
        }

        #browse-resizer:hover::before,
        #browse-resizer.is-dragging::before {
            background: rgb(113 113 122);
        }

        @media (max-width: 767px) {
            .browse-place-card-body {
                flex-direction: column;
                gap: .75rem;
            }

            .browse-place-photo {
                width: 100%;
                height: auto;
                aspect-ratio: 16 / 9;
                flex-basis: auto;
                max-height: 12rem;
            }

            #browse-layout {
                display: flex;
                min-height: calc(100vh - 3.5rem);
                flex-direction: column;
            }

            #filter-panel {
                position: fixed;
                inset: 3.5rem 0 0 0;
                z-index: 2147481500;
                display: none;
                overflow-y: auto;
                border-right: 0;
            }

            #browse-layout.mobile-filter-open #filter-panel {
                display: block;
            }

            [data-mobile-filter-toggle][aria-expanded="true"] {
                background: rgb(39 39 42);
                color: white;
            }

            .dark [data-mobile-filter-toggle][aria-expanded="true"] {
                background: white;
                color: rgb(24 24 27);
            }

            #filter-panel > div {
                position: static;
                max-height: none;
                overflow: visible;
                padding-right: 0;
            }

            #mobile-filter-backdrop {
                position: fixed;
                inset: 3.5rem 0 0 0;
                z-index: 2147481400;
                display: none;
                background: rgb(0 0 0 / 0.55);
            }

            #browse-layout.mobile-filter-open #mobile-filter-backdrop {
                display: block;
            }

            #map-panel {
                order: 1;
                visibility: visible !important;
                overflow: visible !important;
                border-bottom: 1px solid rgb(228 228 231);
            }

            .dark #map-panel {
                border-bottom-color: rgb(39 39 42);
            }

            #results-panel {
                order: 2;
                visibility: visible !important;
                overflow: visible !important;
            }

            #browse-resizer {
                display: none;
            }

            #map {
                position: relative;
                top: auto;
                height: 38vh;
                min-height: 250px;
                max-height: 390px;
            }

            #browse-layout.mobile-map-collapsed #map {
                display: none;
            }

            #mobile-map-toggle {
                display: flex;
            }

            #results-panel > div:first-child {
                padding-left: 1rem;
                padding-right: 1rem;
            }
        }

        @media (min-width: 768px) {
            #mobile-map-toggle,
            #mobile-filter-backdrop,
            [data-mobile-filter-close] {
                display: none !important;
            }
        }
    </style>
@endpush

@push('scripts')
<script>
        (() => {
            const markers = @json($markers);
            const browseProfileEnabled = @json($profileEnabled);
            const mapFilterActive = @json($mapFilter);
            const initialMapBounds = @json($mapBounds);
            const initialMapView = @json($mapView);
            let camperwolfMap = null;
            let mapFilterTimer = null;
            let userLocationMarker = null;
            let userAccuracyCircle = null;
            let locationStatusTimer = null;
            let nearbyTemporaryMarkers = [];
            const mapMarkers = new Map();
            const i18n = {
                openProfile: @js(__('ui.browse.open_profile')),
                showLess: @js(__('ui.browse.show_less')),
                showMore: @js(__('ui.browse.show_more')),
                myLocation: @js(__('ui.browse.my_location')),
                nearbyPlaces: @js(__('ui.browse.nearby_places')),
                nearbySearching: @js(__('ui.browse.nearby_searching')),
                nearbyNone: @js(__('ui.browse.nearby_none')),
                nearbyFoundOne: @js(trans_choice('ui.browse.nearby_found', 1, ['count' => 1])),
                nearbyFoundMany: @js(trans_choice('ui.browse.nearby_found', 3, ['count' => ':count'])),
                nearbyFailed: @js(__('ui.browse.nearby_failed')),
                locationSecureRequired: @js(__('ui.browse.location_secure_required')),
                locationFinding: @js(__('ui.browse.location_finding')),
                locationFound: @js(__('ui.browse.location_found')),
                locationNotSupported: @js(__('ui.browse.location_not_supported')),
                locationDenied: @js(__('ui.browse.location_denied')),
                locationUnavailable: @js(__('ui.browse.location_unavailable')),
                locationTimeout: @js(__('ui.browse.location_timeout')),
                locationAccuracy: @js(__('ui.browse.location_accuracy')),
            };

            const escapeHtml = (value) => String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');

            const placeMarkerIcon = (favorite = false) => L.divIcon({
                className: `cw-place-marker${favorite ? ' is-favorite' : ''}`,
                html: '<svg aria-hidden="true" width="25" height="41" viewBox="0 0 25 41">'
                    + '<path class="cw-marker-shape" d="M12.5 1C6.15 1 1 6.15 1 12.5 1 22 12.5 40 12.5 40S24 22 24 12.5C24 6.15 18.85 1 12.5 1Z" stroke-width="2"/>'
                    + '<circle cx="12.5" cy="12.5" r="4.2" fill="white"/>'
                    + '</svg>',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
            });

            const setHoveredMarker = (placeId, hovered) => {
                const marker = mapMarkers.get(String(placeId));
                const element = marker?.getElement();
                if (element) {
                    element.classList.toggle('is-hovered', hovered);
                    if (hovered) marker.setZIndexOffset(1000);
                    else marker.setZIndexOffset(marker.options.favorite ? 500 : 0);
                }
            };

            const applyMapBoundsToUrl = (map) => {
                const bounds = map.getBounds();
                const url = new URL(window.location.href);
                url.searchParams.set('map_filter', '1');
                url.searchParams.set('north', bounds.getNorth().toFixed(6));
                url.searchParams.set('south', bounds.getSouth().toFixed(6));
                url.searchParams.set('east', bounds.getEast().toFixed(6));
                url.searchParams.set('west', bounds.getWest().toFixed(6));
                const center = map.getCenter();
                url.searchParams.set('map_lat', center.lat.toFixed(6));
                url.searchParams.set('map_lng', center.lng.toFixed(6));
                url.searchParams.set('map_zoom', String(map.getZoom()));
                url.searchParams.delete('page');
                window.location.assign(url.toString());
            };


            const setLocationStatus = (message, duration = 5000) => {
                const status = document.getElementById('map-location-status');
                if (!status) return;

                window.clearTimeout(locationStatusTimer);
                status.textContent = message;
                status.hidden = false;

                if (duration > 0) {
                    locationStatusTimer = window.setTimeout(() => {
                        status.hidden = true;
                    }, duration);
                }
            };

            const showUserLocation = (map, position, recenter = true) => {
                const latitude = Number(position.coords.latitude);
                const longitude = Number(position.coords.longitude);
                const accuracy = Math.max(0, Number(position.coords.accuracy) || 0);
                const latLng = [latitude, longitude];

                if (userLocationMarker) {
                    userLocationMarker.remove();
                }

                if (userAccuracyCircle) {
                    userAccuracyCircle.remove();
                }

                userAccuracyCircle = L.circle(latLng, {
                    radius: accuracy,
                    weight: 1,
                    fillOpacity: 0.08,
                    interactive: false,
                }).addTo(map);

                const accuracyLabel = i18n.locationAccuracy.replace(':meters', Math.round(accuracy));
                userLocationMarker = L.circleMarker(latLng, {
                    radius: 7,
                    weight: 3,
                    fillOpacity: 1,
                })
                    .addTo(map)
                    .bindPopup(`<strong>${escapeHtml(i18n.myLocation)}</strong><br>${escapeHtml(accuracyLabel)}`);

                if (recenter) {
                    if (accuracy > 1000) {
                        map.fitBounds(userAccuracyCircle.getBounds(), {
                            animate: true,
                            padding: [40, 40],
                            maxZoom: 13,
                        });
                    } else {
                        map.setView(latLng, Math.max(map.getZoom(), 15), { animate: true });
                    }

                    setLocationStatus(i18n.locationFound, 4000);
                }
            };

            const locationErrorMessage = (error) => error.code === 1
                ? i18n.locationDenied
                : (error.code === 3 ? i18n.locationTimeout : i18n.locationUnavailable);

            const getUserPosition = (button, onSuccess) => {
                if (!window.isSecureContext) {
                    setLocationStatus(i18n.locationSecureRequired, 7000);
                    return;
                }

                if (!('geolocation' in navigator)) {
                    setLocationStatus(i18n.locationNotSupported, 7000);
                    return;
                }

                button.disabled = true;
                button.setAttribute('aria-busy', 'true');

                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        button.disabled = false;
                        button.removeAttribute('aria-busy');
                        onSuccess(position);
                    },
                    (error) => {
                        button.disabled = false;
                        button.removeAttribute('aria-busy');
                        setLocationStatus(locationErrorMessage(error), 7000);
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 60000,
                    },
                );
            };

            const requestUserLocation = (map, button) => {
                button.title = i18n.locationFinding;
                setLocationStatus(i18n.locationFinding, 0);

                getUserPosition(button, (position) => {
                    button.title = i18n.myLocation;
                    showUserLocation(map, position);
                });
            };

            const distanceMeters = (fromLat, fromLng, toLat, toLng) => {
                const earthRadius = 6371000;
                const toRadians = (degrees) => degrees * Math.PI / 180;
                const deltaLat = toRadians(toLat - fromLat);
                const deltaLng = toRadians(toLng - fromLng);
                const lat1 = toRadians(fromLat);
                const lat2 = toRadians(toLat);

                const a = Math.sin(deltaLat / 2) ** 2
                    + Math.cos(lat1) * Math.cos(lat2) * Math.sin(deltaLng / 2) ** 2;

                return earthRadius * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            };

            const clearNearbyTemporaryMarkers = () => {
                nearbyTemporaryMarkers.forEach((marker) => marker.remove());
                nearbyTemporaryMarkers = [];
            };

            const nearbyCandidatesUrl = () => {
                const url = new URL(window.location.href);
                [
                    'map_filter',
                    'north',
                    'south',
                    'east',
                    'west',
                    'map_lat',
                    'map_lng',
                    'map_zoom',
                    'page',
                ].forEach((key) => url.searchParams.delete(key));
                url.searchParams.set('nearby_candidates', '1');

                return url;
            };

            const showNearbyPlaces = (map, position, places) => {
                const latitude = Number(position.coords.latitude);
                const longitude = Number(position.coords.longitude);

                const nearest = places
                    .map((place) => ({
                        ...place,
                        distance: distanceMeters(
                            latitude,
                            longitude,
                            Number(place.latitude),
                            Number(place.longitude),
                        ),
                    }))
                    .sort((a, b) => a.distance - b.distance)
                    .slice(0, 3);

                if (nearest.length === 0) {
                    setLocationStatus(i18n.nearbyNone, 7000);
                    return;
                }

                clearNearbyTemporaryMarkers();
                showUserLocation(map, position, false);

                nearest.forEach((place) => {
                    const existingMarker = mapMarkers.get(String(place.id));
                    if (existingMarker) return;

                    const marker = L.marker([Number(place.latitude), Number(place.longitude)], {
                        icon: placeMarkerIcon(false),
                    })
                        .addTo(map)
                        .bindPopup(
                            `<strong>${escapeHtml(place.name)}</strong>`
                            + `${place.city ? `<br>${escapeHtml(place.city)}` : ''}`
                            + `<br><a href="${escapeHtml(place.url)}" style="font-weight:600">${escapeHtml(i18n.openProfile)}</a>`
                        );

                    nearbyTemporaryMarkers.push(marker);
                });

                const bounds = L.latLngBounds([[latitude, longitude]]);
                nearest.forEach((place) => {
                    bounds.extend([Number(place.latitude), Number(place.longitude)]);
                });

                map.fitBounds(bounds, {
                    animate: true,
                    padding: [55, 55],
                    maxZoom: 15,
                });

                const message = nearest.length === 1
                    ? i18n.nearbyFoundOne
                    : i18n.nearbyFoundMany.replace(':count', String(nearest.length));

                setLocationStatus(message, 6000);
            };

            const requestNearbyPlaces = (map, button) => {
                setLocationStatus(i18n.locationFinding, 0);

                getUserPosition(button, async (position) => {
                    button.disabled = true;
                    button.setAttribute('aria-busy', 'true');
                    showUserLocation(map, position, false);
                    setLocationStatus(i18n.nearbySearching, 0);

                    try {
                        const response = await fetch(nearbyCandidatesUrl(), {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                            cache: 'no-store',
                        });

                        if (!response.ok) {
                            throw new Error(`Nearby request failed with status ${response.status}`);
                        }

                        const payload = await response.json();
                        showNearbyPlaces(map, position, Array.isArray(payload.places) ? payload.places : []);
                    } catch (_) {
                        setLocationStatus(i18n.nearbyFailed, 7000);
                    } finally {
                        button.disabled = false;
                        button.removeAttribute('aria-busy');
                    }
                });
            };

            const initMap = () => {
                const mapElement = document.getElementById('place-map');
                if (!mapElement || mapElement.dataset.initialized || typeof L === 'undefined') return;

                mapElement.dataset.initialized = '1';
                const map = L.map(mapElement, { zoomControl: true });
                camperwolfMap = map;
                mapMarkers.clear();

                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);

                const markerRenderStarted = performance.now();
                markers.forEach((place) => {
                    const latLng = [place.latitude, place.longitude];
                    const marker = L.marker(latLng, {
                        icon: placeMarkerIcon(Boolean(place.is_favorite)),
                        favorite: Boolean(place.is_favorite),
                        zIndexOffset: place.is_favorite ? 500 : 0,
                    })
                        .addTo(map)
                        .bindPopup(
                            `<strong>${escapeHtml(place.name)}</strong>`
                            + `${place.city ? `<br>${escapeHtml(place.city)}` : ''}`
                            + `<br><a href="${escapeHtml(place.url)}" style="font-weight:600">${escapeHtml(i18n.openProfile)}</a>`
                        );

                    mapMarkers.set(String(place.id), marker);
                });

                if (browseProfileEnabled) {
                    const markerDuration = performance.now() - markerRenderStarted;
                    const markerProfile = document.getElementById('browse-profile-marker-time');
                    if (markerProfile) markerProfile.textContent = markerDuration.toFixed(2) + ' ms';
                    const markerCount = document.getElementById('browse-profile-marker-count');
                    if (markerCount) markerCount.textContent = String(markers.length);
                }

                if (mapFilterActive && initialMapView) {
                    map.setView(
                        [initialMapView.latitude, initialMapView.longitude],
                        initialMapView.zoom,
                        { animate: false },
                    );
                } else if (mapFilterActive && initialMapBounds) {
                    map.fitBounds([
                        [initialMapBounds.south, initialMapBounds.west],
                        [initialMapBounds.north, initialMapBounds.east]
                    ], { animate: false, padding: [0, 0] });
                } else {
                    // The unfiltered map starts with a stable Germany-wide overview.
                    // Markers outside the viewport remain available when the user pans.
                    map.setView([51.1657, 10.4515], 6);
                }

                if (mapFilterActive) {
                    let mapInteracted = false;
                    const markMapInteraction = () => {
                        mapInteracted = true;
                    };

                    // Leaflet also emits moveend while restoring the saved view.
                    // Only an actual user gesture may trigger the next filtered request.
                    mapElement.addEventListener('pointerdown', markMapInteraction, { passive: true });
                    mapElement.addEventListener('wheel', markMapInteraction, { passive: true });
                    mapElement.addEventListener('keydown', markMapInteraction);

                    map.on('moveend', () => {
                        if (!mapInteracted) return;
                        mapInteracted = false;
                        window.clearTimeout(mapFilterTimer);
                        mapFilterTimer = window.setTimeout(() => applyMapBoundsToUrl(map), 300);
                    });
                }

                document.querySelectorAll('[data-place-id]').forEach((row) => {
                    row.addEventListener('mouseenter', () => {
                        setHoveredMarker(row.dataset.placeId, true);
                    });

                    row.addEventListener('mouseleave', () => {
                        setHoveredMarker(row.dataset.placeId, false);
                    });
                });

                const toggle = document.getElementById('map-filter-toggle');
                if (toggle && !toggle.dataset.initialized) {
                    toggle.dataset.initialized = '1';
                    toggle.addEventListener('change', () => {
                        if (toggle.checked) {
                            applyMapBoundsToUrl(map);
                            return;
                        }

                        const url = new URL(window.location.href);
                        ['map_filter', 'north', 'south', 'east', 'west', 'map_lat', 'map_lng', 'map_zoom', 'page'].forEach((key) => url.searchParams.delete(key));
                        window.location.assign(url.toString());
                    });
                }
            };

            const initMapLocationControl = () => {
                if (document.documentElement.dataset.mapLocationInitialized) return;
                document.documentElement.dataset.mapLocationInitialized = '1';

                document.addEventListener('click', (event) => {
                    const locationButton = event.target.closest?.('#map-location-button');
                    const nearbyButton = event.target.closest?.('#map-nearby-button');
                    const button = locationButton || nearbyButton;
                    if (!button) return;

                    event.preventDefault();
                    event.stopPropagation();

                    if (!camperwolfMap) {
                        setLocationStatus(i18n.locationUnavailable, 7000);
                        return;
                    }

                    if (nearbyButton) {
                        requestNearbyPlaces(camperwolfMap, nearbyButton);
                        return;
                    }

                    requestUserLocation(camperwolfMap, locationButton);
                }, true);
            };

            const initTagGroups = () => {
                document.querySelectorAll('[data-tag-group]').forEach((group) => {
                    if (group.dataset.initialized) return;
                    group.dataset.initialized = '1';

                    const button = group.querySelector('[data-tag-more]');
                    if (!button) return;

                    button.addEventListener('click', () => {
                        const expanded = group.dataset.expanded === '1';
                        group.dataset.expanded = expanded ? '0' : '1';
                        updateTagGroup(group);
                    });
                });
            };

            const updateTagGroup = (group, searchTerm = '') => {
                const expanded = group.dataset.expanded === '1';
                const rows = [...group.querySelectorAll('[data-tag-row]')];
                let visibleRows = 0;

                rows.forEach((row) => {
                    const matchesSearch = searchTerm === '' || row.dataset.tagLabel.includes(searchTerm);
                    const hiddenByLimit = row.dataset.tagExtra === '1' && !expanded && searchTerm === '';
                    row.hidden = !matchesSearch || hiddenByLimit;
                    if (!row.hidden) visibleRows++;
                });

                group.hidden = visibleRows === 0;

                const button = group.querySelector('[data-tag-more]');
                if (button) {
                    const extraCount = Number(button.dataset.extraCount || 0);
                    button.hidden = searchTerm !== '' || extraCount < 1;
                    button.textContent = expanded ? i18n.showLess : i18n.showMore.replace(':count', extraCount);
                }
            };

            const initTagSearch = () => {
                const input = document.getElementById('tag-search');
                if (!input || input.dataset.initialized) return;
                input.dataset.initialized = '1';

                input.addEventListener('input', () => {
                    const term = input.value.trim().toLocaleLowerCase();
                    document.querySelectorAll('[data-tag-group]').forEach((group) => updateTagGroup(group, term));
                });
            };

            const initBrowseResizer = () => {
                const layout = document.getElementById('browse-layout');
                const resizer = document.getElementById('browse-resizer');
                const results = document.getElementById('results-panel');
                const mapPanel = document.getElementById('map-panel');
                if (!layout || !resizer || !results || !mapPanel || resizer.dataset.initialized) return;
                resizer.dataset.initialized = '1';

                const storageKey = 'camperwolf.browse-layout';
                let saved = null;
                try { saved = JSON.parse(localStorage.getItem(storageKey) || 'null'); } catch (_) {}

                const applyState = (state) => {
                    layout.classList.toggle('results-hidden', state.mode === 'results-hidden');
                    layout.classList.toggle('map-hidden', state.mode === 'map-hidden');
                    if (state.mapWidth) layout.style.setProperty('--map-width', `${state.mapWidth}px`);
                    requestAnimationFrame(() => camperwolfMap?.invalidateSize());
                };

                if (saved && typeof saved === 'object') applyState(saved);

                const persist = (state) => {
                    localStorage.setItem(storageKey, JSON.stringify(state));
                    applyState(state);
                };

                let dragging = false;

                resizer.addEventListener('pointerdown', (event) => {
                    dragging = true;
                    resizer.classList.add('is-dragging');
                    resizer.setPointerCapture(event.pointerId);
                    event.preventDefault();
                });

                resizer.addEventListener('pointermove', (event) => {
                    if (!dragging) return;

                    const rect = layout.getBoundingClientRect();
                    const contentStart = rect.left + 280;
                    const available = rect.right - contentStart - resizer.offsetWidth;
                    const pointer = event.clientX - contentStart;
                    const resultsWidth = Math.max(0, Math.min(available, pointer));
                    const mapWidth = Math.max(0, available - resultsWidth);

                    if (resultsWidth < 170) {
                        applyState({ mode: 'results-hidden', mapWidth: null });
                    } else if (mapWidth < 170) {
                        applyState({ mode: 'map-hidden', mapWidth: null });
                    } else {
                        applyState({ mode: 'split', mapWidth });
                    }
                });

                const finishDrag = (event) => {
                    if (!dragging) return;
                    dragging = false;
                    resizer.classList.remove('is-dragging');
                    try { resizer.releasePointerCapture(event.pointerId); } catch (_) {}

                    const mode = layout.classList.contains('results-hidden')
                        ? 'results-hidden'
                        : layout.classList.contains('map-hidden')
                            ? 'map-hidden'
                            : 'split';

                    const mapWidth = mode === 'split' ? Math.round(mapPanel.getBoundingClientRect().width) : null;
                    persist({ mode, mapWidth });
                };

                resizer.addEventListener('pointerup', finishDrag);
                resizer.addEventListener('pointercancel', finishDrag);

                resizer.querySelector('[data-show-results]')?.addEventListener('click', (event) => {
                    event.stopPropagation();
                    persist({ mode: 'split', mapWidth: Math.round(layout.getBoundingClientRect().width * 0.42) });
                });

                resizer.querySelector('[data-show-map]')?.addEventListener('click', (event) => {
                    event.stopPropagation();
                    persist({ mode: 'split', mapWidth: Math.round(layout.getBoundingClientRect().width * 0.42) });
                });
            };

            const initMobileBrowse = () => {
                const layout = document.getElementById('browse-layout');
                if (!layout) return;

                const filterToggle = document.querySelector('[data-mobile-filter-toggle]');
                const filterClose = document.querySelector('[data-mobile-filter-close]');
                const filterBackdrop = document.getElementById('mobile-filter-backdrop');
                const mapToggle = document.getElementById('mobile-map-toggle');

                const filterStorageKey = 'camperwolf-mobile-filter-open';

                const setFilterOpen = (open, persist = true) => {
                    layout.classList.toggle('mobile-filter-open', open);
                    filterToggle?.setAttribute('aria-expanded', open ? 'true' : 'false');
                    document.body.classList.toggle('overflow-hidden', open && window.innerWidth < 768);

                    if (persist && window.innerWidth < 768) {
                        try {
                            if (open) {
                                sessionStorage.setItem(filterStorageKey, '1');
                            } else {
                                sessionStorage.removeItem(filterStorageKey);
                            }
                        } catch (_) {}
                    }
                };

                if (window.innerWidth < 768) {
                    try {
                        if (sessionStorage.getItem(filterStorageKey) === '1') {
                            setFilterOpen(true, false);
                        }
                    } catch (_) {}
                }

                if (filterToggle && !filterToggle.dataset.initialized) {
                    filterToggle.dataset.initialized = '1';
                    filterToggle.addEventListener('click', () => setFilterOpen(!layout.classList.contains('mobile-filter-open')));
                }

                if (filterClose && !filterClose.dataset.initialized) {
                    filterClose.dataset.initialized = '1';
                    filterClose.addEventListener('click', () => setFilterOpen(false));
                }

                if (filterBackdrop && !filterBackdrop.dataset.initialized) {
                    filterBackdrop.dataset.initialized = '1';
                    filterBackdrop.addEventListener('click', () => setFilterOpen(false));
                }

                if (mapToggle && !mapToggle.dataset.initialized) {
                    mapToggle.dataset.initialized = '1';
                    const collapseIcon = mapToggle.querySelector('[data-map-collapse-icon]');
                    const expandIcon = mapToggle.querySelector('[data-map-expand-icon]');
                    mapToggle.addEventListener('click', () => {
                        const collapsed = !layout.classList.contains('mobile-map-collapsed');
                        layout.classList.toggle('mobile-map-collapsed', collapsed);
                        collapseIcon?.classList.toggle('hidden', collapsed);
                        expandIcon?.classList.toggle('hidden', !collapsed);
                        mapToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                        mapToggle.setAttribute('aria-label', collapsed
                            ? @js(__('ui.browse.show_map'))
                            : @js(__('ui.browse.hide_map')));
                        mapToggle.setAttribute('title', collapsed
                            ? @js(__('ui.browse.show_map'))
                            : @js(__('ui.browse.hide_map')));
                        if (!collapsed) requestAnimationFrame(() => camperwolfMap?.invalidateSize());
                    });
                }

                if (!layout.dataset.mobileResizeBound) {
                    layout.dataset.mobileResizeBound = '1';
                    window.addEventListener('resize', () => {
                        if (window.innerWidth >= 768) setFilterOpen(false);
                        requestAnimationFrame(() => camperwolfMap?.invalidateSize());
                    });
                }
            };

            const initRangeFilters = () => {
                document.querySelectorAll('[data-range-filter]').forEach((filter) => {
                    if (filter.dataset.initialized) return;
                    filter.dataset.initialized = '1';

                    const minRange = filter.querySelector('[data-range-min]');
                    const maxRange = filter.querySelector('[data-range-max]');
                    const minNumber = filter.querySelector('[data-number-min]');
                    const maxNumber = filter.querySelector('[data-number-max]');
                    if (!minRange || !maxRange || !minNumber || !maxNumber) return;

                    const sync = (source) => {
                        let min = Number(source === minNumber ? minNumber.value : minRange.value);
                        let max = Number(source === maxNumber ? maxNumber.value : maxRange.value);
                        if (!Number.isFinite(min) || !Number.isFinite(max)) return;

                        if (min > max) {
                            if (source === minRange || source === minNumber) max = min;
                            else min = max;
                        }

                        minRange.value = String(min);
                        maxRange.value = String(max);
                        minNumber.value = String(min);
                        maxNumber.value = String(max);
                    };

                    minRange.addEventListener('input', () => sync(minRange));
                    maxRange.addEventListener('input', () => sync(maxRange));
                    minNumber.addEventListener('change', () => sync(minNumber));
                    maxNumber.addEventListener('change', () => sync(maxNumber));
                });
            };

            const init = () => {
                initMap();
                initMapLocationControl();
                initTagGroups();
                document.querySelectorAll('[data-tag-group]').forEach((group) => updateTagGroup(group));
                initTagSearch();
                initRangeFilters();
                initBrowseResizer();
                initMobileBrowse();
            };

            document.addEventListener('DOMContentLoaded', init, { once: true });
            document.addEventListener('livewire:navigated', init);
            window.addEventListener('camperwolf:leaflet-ready', init);
        })();
    </script>
@endpush

<x-layouts::app
    :title="__('ui.browse.title')"
    social-title="Camperwolf.de"
    :meta-description="__('ui.social.default_description')"
    :social-image="asset('images/camperwolf-placeholder.png')"
    :canonical-url="route('home')"
>
    @php
        $allFeatures = $featureGroups->flatten(1)
            ->concat($extendedFeatureGroups->flatten(1))
            ->concat($priorityFeatures)
            ->unique('id')
            ->values();
        $selectedRows = $allFeatures->whereIn('slug', $selectedFeatures);
        $priorityFacetFeatures = $facetState['priority_feature_groups']
            ->flatMap(fn ($group) => $group->features)
            ->sortBy(fn ($feature) => [$feature->filter_priority ?? PHP_INT_MAX, $feature->label])
            ->keyBy('slug');
        $prioritySimpleSlugs = $priorityFeatures->pluck('slug');
        $mapQuery = $mapFilter && $mapBounds ? [
            'map_filter' => 1,
            'north' => $mapBounds['north'],
            'south' => $mapBounds['south'],
            'east' => $mapBounds['east'],
            'west' => $mapBounds['west'],
            ...($mapView ? [
                'map_lat' => $mapView['latitude'],
                'map_lng' => $mapView['longitude'],
                'map_zoom' => $mapView['zoom'],
            ] : []),
        ] : [];
        $facetQuery = $facetState['query'];
        $placeTypeChangeFacetQuery = array_intersect_key($facetQuery, array_flip(['rating']));
        $placeTypeQuery = $selectedPlaceTypes !== [] ? ['place_types' => $selectedPlaceTypes] : [];
        $vehicleTypeQuery = $selectedVehicleTypes !== [] ? ['vehicle_types' => $selectedVehicleTypes] : [];
        $facetPersistentQuery = array_merge($mapQuery, $placeTypeQuery, $vehicleTypeQuery, $facetQuery);
        $persistentQuery = array_merge($facetPersistentQuery, $favoritesOnly ? ['favorites' => 1] : []);
        $hasFacetFilters = $facetState['active_filters']->isNotEmpty();
        $baseFormQuery = array_merge(
            $mapQuery,
            $placeTypeQuery,
            $vehicleTypeQuery,
            $favoritesOnly ? ['favorites' => 1] : [],
            array_filter(['q' => $q, 'features' => $selectedFeatures, 'sort' => $sort, 'sort_direction' => $sortDirection]),
        );
        $facetFormQuery = function (array $facet) use ($facetQuery): array {
            $query = $facetQuery;

            if ($facet['source'] === 'price') {
                unset($query['price_values'][$facet['product_slug']]);
                if (($query['price_values'] ?? []) === []) unset($query['price_values']);
            } elseif ($facet['source'] === 'rating') {
                unset($query['rating']);
            } elseif ($facet['kind'] === 'number') {
                unset($query['feature_values'][$facet['feature_slug']][$facet['detail_key']]);
                if (($query['feature_values'][$facet['feature_slug']] ?? []) === []) unset($query['feature_values'][$facet['feature_slug']]);
                if (($query['feature_values'] ?? []) === []) unset($query['feature_values']);
            } else {
                unset($query['option_values'][$facet['feature_slug']][$facet['detail_key']]);
                if (($query['option_values'][$facet['feature_slug']] ?? []) === []) unset($query['option_values'][$facet['feature_slug']]);
                if (($query['option_values'] ?? []) === []) unset($query['option_values']);
            }

            return $query;
        };
        $legalLabels = __('ui.browse.legal_status');
        $openingLabels = __('ui.browse.opening_status');
    @endphp

    <div id="browse-layout" class="min-h-[calc(100vh-4rem)]">
        <div id="mobile-filter-backdrop" aria-hidden="true"></div>
        <aside id="filter-panel" class="border-r border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="mb-4 flex items-center justify-between md:hidden">
                <div class="text-base font-semibold">{{ __('ui.browse.filter_tags') }}</div>
                <button type="button" data-mobile-filter-close class="grid size-10 place-items-center rounded-lg text-2xl leading-none text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800" aria-label="{{ __('ui.browse.close_filters') }}">×</button>
            </div>
            <div class="sticky top-20 max-h-[calc(100vh-6rem)] space-y-5 overflow-y-auto pr-1">
                <div>
                    <div class="mb-2 flex items-center justify-between">
                        <h2 class="text-sm font-semibold">{{ __('ui.browse.filter_tags') }}</h2>
                        @if ($q !== '' || $selectedFeatures !== [] || $selectedPlaceTypes !== [] || $mapFilter || $favoritesOnly || $hasFacetFilters)
                            <a href="{{ route('dashboard', ['reset_filters' => 1]) }}" class="text-xs text-zinc-500 hover:text-zinc-950 dark:hover:text-white">{{ __('ui.browse.reset') }}</a>
                        @endif
                    </div>
                    <input id="tag-search" type="search" placeholder="{{ __('ui.browse.search_tags') }}" class="h-9 w-full rounded-md border border-zinc-300 bg-zinc-50 px-3 text-sm outline-none focus:border-zinc-500 dark:border-zinc-700 dark:bg-zinc-800">
                </div>

                <section>
                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-400">{{ __('ui.browse.place_type') }}</h3>
                    <form method="GET" action="{{ route('dashboard') }}" class="space-y-2">
                        <input type="hidden" name="place_types_filter" value="1">
                        @include('partials.query-hidden-fields', ['values' => array_merge($mapQuery, $vehicleTypeQuery, $placeTypeChangeFacetQuery, $favoritesOnly ? ['favorites' => 1] : [], array_filter(['q' => $q, 'sort' => $sort, 'sort_direction' => $sortDirection])), 'prefix' => ''])
                        <details class="rounded-md border border-zinc-300 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                            <summary class="cursor-pointer list-none px-3 py-2 text-sm">
                                {{ $selectedPlaceTypes === []
                                    ? __('ui.browse.all_place_types')
                                    : __('ui.browse.selected_place_types', ['count' => count($selectedPlaceTypes)]) }}
                            </summary>
                            <div class="space-y-1 border-t border-zinc-200 p-2 dark:border-zinc-700">
                                @foreach ($filterPlaceTypes as $placeType)
                                    <label class="flex cursor-pointer items-center gap-2 rounded px-2 py-1.5 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                        <input
                                            type="checkbox"
                                            name="place_types[]"
                                            value="{{ $placeType->slug }}"
                                            @checked($selectedPlaceTypes === [] || in_array($placeType->slug, $selectedPlaceTypes, true))
                                            class="rounded border-zinc-300 dark:border-zinc-600"
                                        >
                                        <span>{{ $placeType->label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </details>
                        <button class="w-full rounded-md border border-zinc-200 bg-transparent px-3 py-1.5 text-[11px] font-medium text-zinc-500 transition hover:border-zinc-300 hover:bg-zinc-50 hover:text-zinc-800 dark:border-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-200">
                            {{ __('ui.browse.apply_selection') }}
                        </button>
                    </form>
                </section>

                <section>
                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-400">{{ __('ui.browse.suitable_for') }}</h3>
                    <form method="GET" action="{{ route('dashboard') }}" class="space-y-2">
                        <input type="hidden" name="vehicle_types_filter" value="1">
                        @include('partials.query-hidden-fields', ['values' => array_merge($mapQuery, $placeTypeQuery, $facetQuery, $favoritesOnly ? ['favorites' => 1] : [], array_filter(['q' => $q, 'sort' => $sort, 'sort_direction' => $sortDirection])), 'prefix' => ''])
                        <details class="rounded-md border border-zinc-300 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                            <summary class="cursor-pointer list-none px-3 py-2 text-sm">
                                {{ $selectedVehicleTypes === []
                                    ? __('ui.browse.all_vehicle_types')
                                    : __('ui.browse.selected_vehicle_types', ['count' => count($selectedVehicleTypes)]) }}
                            </summary>
                            <div class="space-y-1 border-t border-zinc-200 p-2 dark:border-zinc-700">
                                @foreach ($filterVehicleTypes as $vehicleType)
                                    <label class="flex cursor-pointer items-center gap-2 rounded px-2 py-1.5 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                        <input type="checkbox" name="vehicle_types[]" value="{{ $vehicleType->slug }}" @checked(in_array($vehicleType->slug, $selectedVehicleTypes, true)) class="rounded border-zinc-300 dark:border-zinc-600">
                                        <span>{{ $vehicleType->label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </details>
                        <button class="w-full rounded-md border border-zinc-200 bg-transparent px-3 py-1.5 text-[11px] font-medium text-zinc-500 transition hover:border-zinc-300 hover:bg-zinc-50 hover:text-zinc-800 dark:border-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-200">
                            {{ __('ui.browse.apply_selection') }}
                        </button>
                    </form>
                </section>

                <section class="tag-group" data-tag-group data-expanded="1">
                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-400">{{ __('ui.browse.personal') }}</h3>
                    <div class="space-y-1">
                        @auth
                            <a
                                href="{{ route('dashboard', array_merge($facetPersistentQuery, $favoritesOnly ? ['clear_filter' => 'favorites'] : ['favorites' => 1], array_filter(['q' => $q, 'features' => $selectedFeatures, 'sort' => $sort, 'sort_direction' => $sortDirection]))) }}"
                                data-tag-row
                                data-tag-extra="0"
                                data-tag-label="{{ mb_strtolower(__('ui.browse.favorites_only')) }} favorites"
                                class="flex w-full items-center justify-between rounded-md px-2 py-1.5 text-left text-sm {{ $favoritesOnly ? 'bg-zinc-100 font-medium text-zinc-950 dark:bg-zinc-800 dark:text-white' : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800' }}"
                            >
                                <span>{{ $favoritesOnly ? '−' : '+' }} {{ __('ui.browse.favorites_only') }}</span>
                                <span class="text-xs text-zinc-400">{{ $favoriteCount }}</span>
                            </a>
                        @else
                            <button
                                type="button"
                                data-auth-feature
                                data-auth-title="{{ __('ui.browse.favorites_title') }}"
                                data-auth-message="{{ __('ui.browse.favorites_guest_help') }}"
                                data-tag-row
                                data-tag-extra="0"
                                data-tag-label="{{ mb_strtolower(__('ui.browse.favorites_only')) }} favorites"
                                class="flex w-full items-center justify-between rounded-md px-2 py-1.5 text-left text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800"
                            >
                                <span>+ {{ __('ui.browse.favorites_only') }}</span>
                                <span class="text-xs text-zinc-400">{{ __('global.login') }}</span>
                            </button>
                        @endauth
                    </div>
                </section>

                @if ($q !== '' || $selectedRows->isNotEmpty() || $mapFilter || $favoritesOnly || $hasFacetFilters)
                    <section>
                        <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-400">{{ __('ui.browse.active') }}</h3>
                        <div class="flex flex-wrap gap-2">
                            @if ($q !== '')
                                <a href="{{ route('dashboard', array_merge($persistentQuery, array_filter(['features' => $selectedFeatures, 'sort' => $sort, 'sort_direction' => $sortDirection]))) }}" class="rounded-md bg-zinc-900 px-2.5 py-1.5 text-xs font-medium text-white dark:bg-white dark:text-zinc-950">
                                    {{ __('ui.browse.search_filter', ['query' => $q]) }} ×
                                </a>
                            @endif

                            @foreach ($selectedRows as $selected)
                                @php
                                    $remaining = array_values(array_diff($selectedFeatures, [$selected->slug]));
                                @endphp
                                <a href="{{ route('dashboard', array_merge($facetPersistentQuery, $favoritesOnly ? ['favorites' => 1] : [], $remaining !== [] ? ['features' => $remaining] : ['clear_filter' => 'features'], array_filter(['q' => $q, 'sort' => $sort, 'sort_direction' => $sortDirection]))) }}" class="rounded-md bg-zinc-900 px-2.5 py-1.5 text-xs font-medium text-white dark:bg-white dark:text-zinc-950">
                                    {{ $selected->label }} ×
                                </a>
                            @endforeach

                            @foreach ($facetState['active_filters'] as $filter)
                                @php
                                    $facetClearKey = $filter['key'] === 'rating'
                                        ? 'rating'
                                        : (str_starts_with($filter['key'], 'price:') ? 'price_values' : (str_contains($filter['key'], ':') ? (isset($filter['remaining_query']['option_values']) ? 'option_values' : 'feature_values') : null));
                                @endphp
                                <a href="{{ route('dashboard', array_merge($mapQuery, $placeTypeQuery, $vehicleTypeQuery, $favoritesOnly ? ['favorites' => 1] : [], $filter['remaining_query'], $facetClearKey && ! array_key_exists($facetClearKey, $filter['remaining_query']) ? ['clear_filter' => $facetClearKey] : [], array_filter(['q' => $q, 'features' => $selectedFeatures, 'sort' => $sort, 'sort_direction' => $sortDirection]))) }}" class="rounded-md bg-zinc-900 px-2.5 py-1.5 text-xs font-medium text-white dark:bg-white dark:text-zinc-950">
                                    {{ $filter['label'] }} ×
                                </a>
                            @endforeach

                            @if ($favoritesOnly)
                                <a href="{{ route('dashboard', array_merge($facetPersistentQuery, ['clear_filter' => 'favorites'], array_filter(['q' => $q, 'features' => $selectedFeatures, 'sort' => $sort, 'sort_direction' => $sortDirection]))) }}" class="rounded-md bg-red-600 px-2.5 py-1.5 text-xs font-medium text-white">
                                    ♥ {{ __('ui.browse.favorites_only') }} ×
                                </a>
                            @endif

                            @if ($mapFilter)
                                <span class="rounded-md bg-zinc-900 px-2.5 py-1.5 text-xs font-medium text-white dark:bg-white dark:text-zinc-950">{{ __('ui.browse.map_area') }}</span>
                            @endif
                        </div>
                    </section>
                @endif

                @if ($priorityFeatures->isNotEmpty() || $facetState['rating_facet'] || $facetState['priority_feature_groups']->isNotEmpty())
                    <section class="space-y-2">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-zinc-400">{{ __('ui.browse.most_important') }}</h3>

                        @if ($facetState['rating_facet'])
                            @php
                                $facet = $facetState['rating_facet'];
                                $minValue = $facet['selected_min'] ?? $facet['available_min'];
                                $maxValue = $facet['selected_max'] ?? $facet['available_max'];
                                $formQuery = array_merge($baseFormQuery, $facetFormQuery($facet));
                            @endphp
                            <details class="rounded-md border border-transparent open:border-zinc-200 open:bg-zinc-50 dark:open:border-zinc-700 dark:open:bg-zinc-800/50">
                                <summary class="flex cursor-pointer list-none items-center justify-between rounded-md px-2 py-1.5 text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800">
                                    <span>+ {{ __('ui.browse.rating') }}</span>
                                    <span class="text-xs text-zinc-400">{{ $facet['place_count'] }}</span>
                                </summary>
                                <form method="GET" action="{{ route('dashboard') }}" class="space-y-3 px-3 pb-3 pt-2" data-range-filter>
                                    @include('partials.query-hidden-fields', ['values' => $formQuery, 'prefix' => ''])
                                    <div class="grid grid-cols-2 gap-2">
                                        <label class="text-xs text-zinc-500">{{ __('ui.browse.from') }}
                                            <input data-number-min name="rating[min]" type="number" min="1" max="5" step="0.1" value="{{ $minValue }}" class="mt-1 h-8 w-full rounded border border-zinc-300 bg-white px-2 text-sm dark:border-zinc-600 dark:bg-zinc-900">
                                        </label>
                                        <label class="text-xs text-zinc-500">{{ __('ui.browse.to') }}
                                            <input data-number-max name="rating[max]" type="number" min="1" max="5" step="0.1" value="{{ $maxValue }}" class="mt-1 h-8 w-full rounded border border-zinc-300 bg-white px-2 text-sm dark:border-zinc-600 dark:bg-zinc-900">
                                        </label>
                                    </div>
                                    <div class="space-y-1">
                                        <input data-range-min type="range" min="1" max="5" step="0.1" value="{{ $minValue }}" class="w-full accent-blue-600">
                                        <input data-range-max type="range" min="1" max="5" step="0.1" value="{{ $maxValue }}" class="w-full accent-blue-600">
                                    </div>
                                    <button class="w-full rounded-md bg-zinc-900 px-3 py-2 text-xs font-medium text-white dark:bg-white dark:text-zinc-950">{{ __('ui.browse.apply_filter') }}</button>
                                </form>
                            </details>
                        @endif

                        @foreach ($priorityFeatures as $feature)
                            @php
                                $isSelected = in_array($feature->slug, $selectedFeatures, true);
                                $nextFeatures = $isSelected
                                    ? array_values(array_diff($selectedFeatures, [$feature->slug]))
                                    : array_values(array_unique([...$selectedFeatures, $feature->slug]));
                                $priorityFacetFeature = $priorityFacetFeatures->get($feature->slug);
                            @endphp
                            <div>
                                <a
                                    href="{{ route('dashboard', array_merge($persistentQuery, array_filter(['q' => $q, 'features' => $nextFeatures, 'sort' => $sort, 'sort_direction' => $sortDirection]))) }}"
                                    class="flex w-full items-center justify-between rounded-md px-2 py-1.5 text-left text-sm {{ $isSelected ? 'bg-zinc-100 font-medium text-zinc-950 dark:bg-zinc-800 dark:text-white' : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800' }}"
                                >
                                    <span>{{ $isSelected ? '−' : '+' }} {{ $feature->label }}</span>
                                    <span class="text-xs text-zinc-400">{{ $feature->place_count }}</span>
                                </a>

                                @if ($priorityFacetFeature)
                                    <details class="ml-2 mt-1 rounded-md border border-transparent open:border-zinc-200 open:bg-zinc-50 dark:open:border-zinc-700 dark:open:bg-zinc-800/50">
                                        <summary class="cursor-pointer list-none px-2 py-1 text-xs font-medium text-zinc-500 hover:text-zinc-950 dark:hover:text-white">
                                            {{ __('ui.browse.filter_details') }}
                                        </summary>
                                        <div class="space-y-3 px-3 pb-3 pt-2">
                                            @foreach ($priorityFacetFeature->facets as $facet)
                                                @php $formQuery = array_merge($baseFormQuery, $facetFormQuery($facet)); @endphp
                                                @if ($facet['kind'] === 'number')
                                                    @php
                                                        $minValue = $facet['selected_min'] ?? $facet['available_min'];
                                                        $maxValue = $facet['selected_max'] ?? $facet['available_max'];
                                                        $step = ($facet['available_max'] - $facet['available_min']) <= 10 ? 0.1 : 1;
                                                    @endphp
                                                    <form method="GET" action="{{ route('dashboard') }}" class="space-y-2" data-range-filter>
                                                        @include('partials.query-hidden-fields', ['values' => $formQuery, 'prefix' => ''])
                                                        <div class="flex items-center justify-between gap-2 text-xs">
                                                            <span class="font-medium text-zinc-700 dark:text-zinc-200">{{ $facet['detail_label'] }}</span>
                                                            <span class="text-zinc-400">{{ $facet['place_count'] }}</span>
                                                        </div>
                                                        <div class="grid grid-cols-2 gap-2">
                                                            <input data-number-min name="feature_values[{{ $facet['feature_slug'] }}][{{ $facet['detail_key'] }}][min]" type="number" min="{{ $facet['available_min'] }}" max="{{ $facet['available_max'] }}" step="{{ $step }}" value="{{ $minValue }}" class="h-8 w-full rounded border border-zinc-300 bg-white px-2 text-sm dark:border-zinc-600 dark:bg-zinc-900">
                                                            <input data-number-max name="feature_values[{{ $facet['feature_slug'] }}][{{ $facet['detail_key'] }}][max]" type="number" min="{{ $facet['available_min'] }}" max="{{ $facet['available_max'] }}" step="{{ $step }}" value="{{ $maxValue }}" class="h-8 w-full rounded border border-zinc-300 bg-white px-2 text-sm dark:border-zinc-600 dark:bg-zinc-900">
                                                        </div>
                                                        <div class="space-y-1">
                                                            <input data-range-min type="range" min="{{ $facet['available_min'] }}" max="{{ $facet['available_max'] }}" step="{{ $step }}" value="{{ $minValue }}" class="w-full accent-blue-600">
                                                            <input data-range-max type="range" min="{{ $facet['available_min'] }}" max="{{ $facet['available_max'] }}" step="{{ $step }}" value="{{ $maxValue }}" class="w-full accent-blue-600">
                                                        </div>
                                                        <button class="w-full rounded-md bg-zinc-900 px-3 py-2 text-xs font-medium text-white dark:bg-white dark:text-zinc-950">{{ __('ui.browse.apply_filter') }}</button>
                                                    </form>
                                                @else
                                                    <form method="GET" action="{{ route('dashboard') }}" class="space-y-2">
                                                        @include('partials.query-hidden-fields', ['values' => $formQuery, 'prefix' => ''])
                                                        <div class="flex items-center justify-between gap-2 text-xs">
                                                            <span class="font-medium text-zinc-700 dark:text-zinc-200">{{ $facet['detail_label'] }}</span>
                                                            <span class="text-zinc-400">{{ $facet['place_count'] }}</span>
                                                        </div>
                                                        @foreach ($facet['options'] as $option)
                                                            <label class="flex items-center justify-between gap-2 text-xs text-zinc-600 dark:text-zinc-300">
                                                                <span class="flex items-center gap-2"><input type="checkbox" name="option_values[{{ $facet['feature_slug'] }}][{{ $facet['detail_key'] }}][]" value="{{ $option['value'] }}" @checked($facet['selected'] === [] || in_array($option['value'], $facet['selected'], true)) class="rounded border-zinc-300">{{ $option['label'] }}</span>
                                                                <span class="text-zinc-400">{{ $option['count'] }}</span>
                                                            </label>
                                                        @endforeach
                                                        <button class="w-full rounded-md bg-zinc-900 px-3 py-2 text-xs font-medium text-white dark:bg-white dark:text-zinc-950">{{ __('ui.browse.apply_filter') }}</button>
                                                    </form>
                                                @endif
                                            @endforeach
                                        </div>
                                    </details>
                                @endif
                            </div>
                        @endforeach

                        @foreach ($priorityFacetFeatures->except($prioritySimpleSlugs->all())->values() as $feature)
                                <details class="rounded-md border border-transparent open:border-zinc-200 open:bg-zinc-50 dark:open:border-zinc-700 dark:open:bg-zinc-800/50">
                                    <summary class="flex cursor-pointer list-none items-center justify-between rounded-md px-2 py-1.5 text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800">
                                        <span>+ {{ $feature->label }}</span>
                                        <span class="text-xs text-zinc-400">{{ $feature->place_count }}</span>
                                    </summary>
                                    <div class="space-y-3 px-3 pb-3 pt-2">
                                        @foreach ($feature->facets as $facet)
                                            @php $formQuery = array_merge($baseFormQuery, $facetFormQuery($facet)); @endphp
                                            @if ($facet['kind'] === 'number')
                                                @php
                                                    $minValue = $facet['selected_min'] ?? $facet['available_min'];
                                                    $maxValue = $facet['selected_max'] ?? $facet['available_max'];
                                                    $step = ($facet['available_max'] - $facet['available_min']) <= 10 ? 0.1 : 1;
                                                @endphp
                                                <form method="GET" action="{{ route('dashboard') }}" class="space-y-2" data-range-filter>
                                                    @include('partials.query-hidden-fields', ['values' => $formQuery, 'prefix' => ''])
                                                    <div class="text-xs font-medium text-zinc-600 dark:text-zinc-300">{{ $facet['detail_label'] }}</div>
                                                    <div class="grid grid-cols-2 gap-2">
                                                        <input data-number-min name="feature_values[{{ $facet['feature_slug'] }}][{{ $facet['detail_key'] }}][min]" type="number" min="{{ $facet['available_min'] }}" max="{{ $facet['available_max'] }}" step="{{ $step }}" value="{{ $minValue }}" class="h-8 w-full rounded border border-zinc-300 bg-white px-2 text-sm dark:border-zinc-600 dark:bg-zinc-900">
                                                        <input data-number-max name="feature_values[{{ $facet['feature_slug'] }}][{{ $facet['detail_key'] }}][max]" type="number" min="{{ $facet['available_min'] }}" max="{{ $facet['available_max'] }}" step="{{ $step }}" value="{{ $maxValue }}" class="h-8 w-full rounded border border-zinc-300 bg-white px-2 text-sm dark:border-zinc-600 dark:bg-zinc-900">
                                                    </div>
                                                    <div class="space-y-1">
                                                        <input data-range-min type="range" min="{{ $facet['available_min'] }}" max="{{ $facet['available_max'] }}" step="{{ $step }}" value="{{ $minValue }}" class="w-full accent-blue-600">
                                                        <input data-range-max type="range" min="{{ $facet['available_min'] }}" max="{{ $facet['available_max'] }}" step="{{ $step }}" value="{{ $maxValue }}" class="w-full accent-blue-600">
                                                    </div>
                                                    <button class="w-full rounded-md bg-zinc-900 px-3 py-2 text-xs font-medium text-white dark:bg-white dark:text-zinc-950">{{ __('ui.browse.apply_filter') }}</button>
                                                </form>
                                            @else
                                                <form method="GET" action="{{ route('dashboard') }}" class="space-y-2">
                                                    @include('partials.query-hidden-fields', ['values' => $formQuery, 'prefix' => ''])
                                                    @foreach ($facet['options'] as $option)
                                                        <label class="flex items-center justify-between gap-2 text-xs text-zinc-600 dark:text-zinc-300">
                                                            <span class="flex items-center gap-2"><input type="checkbox" name="option_values[{{ $facet['feature_slug'] }}][{{ $facet['detail_key'] }}][]" value="{{ $option['value'] }}" @checked($facet['selected'] === [] || in_array($option['value'], $facet['selected'], true)) class="rounded border-zinc-300">{{ $option['label'] }}</span>
                                                            <span class="text-zinc-400">{{ $option['count'] }}</span>
                                                        </label>
                                                    @endforeach
                                                    <button class="w-full rounded-md bg-zinc-900 px-3 py-2 text-xs font-medium text-white dark:bg-white dark:text-zinc-950">{{ __('ui.browse.apply_filter') }}</button>
                                                </form>
                                            @endif
                                        @endforeach
                                    </div>
                                </details>
                        @endforeach
                    </section>
                @endif

                @forelse ($featureGroups as $group => $features)
                    <section class="tag-group" data-tag-group data-expanded="0">
                        <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-400">{{ $group }}</h3>
                        <div class="space-y-1">
                            @foreach ($features as $feature)
                                @php
                                    $isSelected = in_array($feature->slug, $selectedFeatures, true);
                                    $nextFeatures = $isSelected
                                        ? array_values(array_diff($selectedFeatures, [$feature->slug]))
                                        : array_values(array_unique([...$selectedFeatures, $feature->slug]));
                                @endphp
                                <a
                                    href="{{ route('dashboard', array_merge($persistentQuery, array_filter(['q' => $q, 'features' => $nextFeatures, 'sort' => $sort, 'sort_direction' => $sortDirection]))) }}"
                                    data-tag-row
                                    data-tag-extra="{{ $loop->index >= 5 ? '1' : '0' }}"
                                    data-tag-label="{{ Str::lower($feature->label) }}"
                                    class="flex w-full items-center justify-between rounded-md px-2 py-1.5 text-left text-sm {{ $isSelected ? 'bg-zinc-100 font-medium text-zinc-950 dark:bg-zinc-800 dark:text-white' : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800' }}"
                                >
                                    <span>{{ $isSelected ? '−' : '+' }} {{ $feature->label }}</span>
                                    <span class="text-xs text-zinc-400">{{ $feature->place_count }}</span>
                                </a>
                            @endforeach
                        </div>

                        @if ($features->count() > 5)
                            <button type="button" data-tag-more data-extra-count="{{ $features->count() - 5 }}" class="mt-1 px-2 py-1 text-xs font-medium text-zinc-500 hover:text-zinc-950 dark:hover:text-white">
                                {{ __('ui.browse.show_more', ['count' => $features->count() - 5]) }}
                            </button>
                        @endif
                    </section>
                @empty
                    <div class="rounded-lg border border-dashed border-zinc-300 p-3 text-sm text-zinc-500 dark:border-zinc-700">
                        {{ __('ui.browse.no_tags') }}
                    </div>
                @endforelse

                @foreach ($facetState['feature_groups'] as $group)
                    <section class="tag-group" data-tag-group data-expanded="0">
                        <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-400">{{ $group->label }} · {{ __('ui.browse.values') }}</h3>
                        <div class="space-y-1">
                            @foreach ($group->features as $feature)
                                <details data-tag-row data-tag-extra="{{ $loop->index >= 5 ? '1' : '0' }}" data-tag-label="{{ Str::lower($feature->label.' '.$feature->facets->pluck('detail_label')->implode(' ')) }}" class="rounded-md border border-transparent open:border-zinc-200 open:bg-zinc-50 dark:open:border-zinc-700 dark:open:bg-zinc-800/50">
                                    <summary class="flex cursor-pointer list-none items-center justify-between rounded-md px-2 py-1.5 text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800">
                                        <span>+ {{ $feature->label }}</span>
                                        <span class="text-xs text-zinc-400">{{ $feature->place_count }}</span>
                                    </summary>
                                    <div class="space-y-3 px-3 pb-3 pt-2">
                                        @foreach ($feature->facets as $facet)
                                            @php
                                                $formQuery = array_merge($baseFormQuery, $facetFormQuery($facet));
                                            @endphp
                                            @if ($facet['kind'] === 'number')
                                                @php
                                                    $minValue = $facet['selected_min'] ?? $facet['available_min'];
                                                    $maxValue = $facet['selected_max'] ?? $facet['available_max'];
                                                    $step = ($facet['available_max'] - $facet['available_min']) <= 10 ? 0.1 : 1;
                                                @endphp
                                                <form method="GET" action="{{ route('dashboard') }}" class="space-y-2 border-t border-zinc-200 pt-2 first:border-0 first:pt-0 dark:border-zinc-700" data-range-filter>
                                                    @include('partials.query-hidden-fields', ['values' => $formQuery, 'prefix' => ''])
                                                    <div class="flex items-center justify-between gap-2 text-xs">
                                                        <span class="font-medium text-zinc-700 dark:text-zinc-200">{{ $facet['detail_label'] }}</span>
                                                        <span class="text-zinc-400">{{ $facet['place_count'] }}</span>
                                                    </div>
                                                    <div class="grid grid-cols-2 gap-2">
                                                        <label class="text-xs text-zinc-500">{{ __('ui.browse.from') }}
                                                            <input data-number-min name="feature_values[{{ $facet['feature_slug'] }}][{{ $facet['detail_key'] }}][min]" type="number" min="{{ $facet['available_min'] }}" max="{{ $facet['available_max'] }}" step="{{ $step }}" value="{{ $minValue }}" class="mt-1 h-8 w-full rounded border border-zinc-300 bg-white px-2 text-sm dark:border-zinc-600 dark:bg-zinc-900">
                                                        </label>
                                                        <label class="text-xs text-zinc-500">{{ __('ui.browse.to') }}
                                                            <input data-number-max name="feature_values[{{ $facet['feature_slug'] }}][{{ $facet['detail_key'] }}][max]" type="number" min="{{ $facet['available_min'] }}" max="{{ $facet['available_max'] }}" step="{{ $step }}" value="{{ $maxValue }}" class="mt-1 h-8 w-full rounded border border-zinc-300 bg-white px-2 text-sm dark:border-zinc-600 dark:bg-zinc-900">
                                                        </label>
                                                    </div>
                                                    <div class="space-y-1">
                                                        <input data-range-min type="range" min="{{ $facet['available_min'] }}" max="{{ $facet['available_max'] }}" step="{{ $step }}" value="{{ $minValue }}" class="w-full accent-blue-600">
                                                        <input data-range-max type="range" min="{{ $facet['available_min'] }}" max="{{ $facet['available_max'] }}" step="{{ $step }}" value="{{ $maxValue }}" class="w-full accent-blue-600">
                                                    </div>
                                                    <div class="text-xs text-zinc-500">{{ $facet['unit'] }}</div>
                                                    <button class="w-full rounded-md bg-zinc-900 px-3 py-2 text-xs font-medium text-white dark:bg-white dark:text-zinc-950">{{ __('ui.browse.apply_filter') }}</button>
                                                </form>
                                            @endif

                                            @if ($facet['kind'] === 'option')
                                                <form method="GET" action="{{ route('dashboard') }}" class="space-y-2 border-t border-zinc-200 pt-2 first:border-0 first:pt-0 dark:border-zinc-700">
                                                    @include('partials.query-hidden-fields', ['values' => $formQuery, 'prefix' => ''])
                                                    <div class="flex items-center justify-between gap-2 text-xs">
                                                        <span class="font-medium text-zinc-700 dark:text-zinc-200">{{ $facet['detail_label'] }}</span>
                                                        <span class="text-zinc-400">{{ $facet['place_count'] }}</span>
                                                    </div>
                                                    <div class="space-y-1">
                                                        @foreach ($facet['options'] as $option)
                                                            <label class="flex items-center justify-between gap-2 text-xs text-zinc-600 dark:text-zinc-300">
                                                                <span class="flex items-center gap-2">
                                                                    <input type="checkbox" name="option_values[{{ $facet['feature_slug'] }}][{{ $facet['detail_key'] }}][]" value="{{ $option['value'] }}" @checked($facet['selected'] === [] || in_array($option['value'], $facet['selected'], true)) class="rounded border-zinc-300">
                                                                    {{ $option['label'] }}
                                                                </span>
                                                                <span class="text-zinc-400">{{ $option['count'] }}</span>
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                    <button class="w-full rounded-md bg-zinc-900 px-3 py-2 text-xs font-medium text-white dark:bg-white dark:text-zinc-950">{{ __('ui.browse.apply_filter') }}</button>
                                                </form>
                                            @endif
                                        @endforeach
                                    </div>
                                </details>
                            @endforeach
                        </div>
                        @if ($group->features->count() > 5)
                            <button type="button" data-tag-more data-extra-count="{{ $group->features->count() - 5 }}" class="mt-1 px-2 py-1 text-xs font-medium text-zinc-500 hover:text-zinc-950 dark:hover:text-white">
                                {{ __('ui.browse.show_more', ['count' => $group->features->count() - 5]) }}
                            </button>
                        @endif
                    </section>
                @endforeach

                @if ($extendedFeatureGroups->isNotEmpty() || $facetState['extended_feature_groups']->isNotEmpty())
                    <details class="border-t border-zinc-200 pt-3 dark:border-zinc-800">
                        <summary class="cursor-pointer text-sm font-semibold text-zinc-600 hover:text-zinc-950 dark:text-zinc-300 dark:hover:text-white">{{ __('ui.browse.more_features') }}</summary>
                        <div class="mt-4 space-y-5">
                            @foreach ($extendedFeatureGroups as $group => $features)
                                <section class="tag-group" data-tag-group data-expanded="0">
                                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-400">{{ $group }}</h3>
                                    <div class="space-y-1">
                                        @foreach ($features as $feature)
                                            @php
                                                $isSelected = in_array($feature->slug, $selectedFeatures, true);
                                                $nextFeatures = $isSelected ? array_values(array_diff($selectedFeatures, [$feature->slug])) : array_values(array_unique([...$selectedFeatures, $feature->slug]));
                                            @endphp
                                            <a href="{{ route('dashboard', array_merge($persistentQuery, array_filter(['q' => $q, 'features' => $nextFeatures, 'sort' => $sort, 'sort_direction' => $sortDirection]))) }}" data-tag-row data-tag-extra="{{ $loop->index >= 5 ? '1' : '0' }}" data-tag-label="{{ Str::lower($feature->label) }}" class="flex w-full items-center justify-between rounded-md px-2 py-1.5 text-left text-sm {{ $isSelected ? 'bg-zinc-100 font-medium text-zinc-950 dark:bg-zinc-800 dark:text-white' : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800' }}">
                                                <span>{{ $isSelected ? '−' : '+' }} {{ $feature->label }}</span><span class="text-xs text-zinc-400">{{ $feature->place_count }}</span>
                                            </a>
                                        @endforeach
                                    </div>
                                    @if ($features->count() > 5)
                                        <button type="button" data-tag-more data-extra-count="{{ $features->count() - 5 }}" class="mt-1 px-2 py-1 text-xs font-medium text-zinc-500 hover:text-zinc-950 dark:hover:text-white">{{ __('ui.browse.show_more', ['count' => $features->count() - 5]) }}</button>
                                    @endif
                                </section>
                            @endforeach

                            @foreach ($facetState['extended_feature_groups'] as $group)
                                <section class="tag-group" data-tag-group data-expanded="0">
                                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-400">{{ $group->label }} · {{ __('ui.browse.values') }}</h3>
                                    <div class="space-y-1">
                                        @foreach ($group->features as $feature)
                                            <details data-tag-row data-tag-extra="{{ $loop->index >= 5 ? '1' : '0' }}" data-tag-label="{{ Str::lower($feature->label) }}" class="rounded-md border border-transparent open:border-zinc-200 open:bg-zinc-50 dark:open:border-zinc-700 dark:open:bg-zinc-800/50">
                                                <summary class="flex cursor-pointer list-none items-center justify-between rounded-md px-2 py-1.5 text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800"><span>+ {{ $feature->label }}</span><span class="text-xs text-zinc-400">{{ $feature->place_count }}</span></summary>
                                                <div class="space-y-3 px-3 pb-3 pt-2">
                                                    @foreach ($feature->facets as $facet)
                                                        @php $formQuery = array_merge($baseFormQuery, $facetFormQuery($facet)); @endphp
                                                        @if ($facet['kind'] === 'number')
                                                            @php $minValue = $facet['selected_min'] ?? $facet['available_min']; $maxValue = $facet['selected_max'] ?? $facet['available_max']; $step = ($facet['available_max'] - $facet['available_min']) <= 10 ? 0.1 : 1; @endphp
                                                            <form method="GET" action="{{ route('dashboard') }}" class="space-y-2" data-range-filter>
                                                                @include('partials.query-hidden-fields', ['values' => $formQuery, 'prefix' => ''])
                                                                <div class="text-xs font-medium text-zinc-600 dark:text-zinc-300">{{ $facet['detail_label'] }}</div>
                                                                <div class="grid grid-cols-2 gap-2"><input data-number-min name="feature_values[{{ $facet['feature_slug'] }}][{{ $facet['detail_key'] }}][min]" type="number" min="{{ $facet['available_min'] }}" max="{{ $facet['available_max'] }}" step="{{ $step }}" value="{{ $minValue }}" class="h-8 w-full rounded border border-zinc-300 bg-white px-2 text-sm dark:border-zinc-600 dark:bg-zinc-900"><input data-number-max name="feature_values[{{ $facet['feature_slug'] }}][{{ $facet['detail_key'] }}][max]" type="number" min="{{ $facet['available_min'] }}" max="{{ $facet['available_max'] }}" step="{{ $step }}" value="{{ $maxValue }}" class="h-8 w-full rounded border border-zinc-300 bg-white px-2 text-sm dark:border-zinc-600 dark:bg-zinc-900"></div>
                                                                <div class="space-y-1"><input data-range-min type="range" min="{{ $facet['available_min'] }}" max="{{ $facet['available_max'] }}" step="{{ $step }}" value="{{ $minValue }}" class="w-full accent-blue-600"><input data-range-max type="range" min="{{ $facet['available_min'] }}" max="{{ $facet['available_max'] }}" step="{{ $step }}" value="{{ $maxValue }}" class="w-full accent-blue-600"></div>
                                                                <button class="w-full rounded-md bg-zinc-900 px-3 py-2 text-xs font-medium text-white dark:bg-white dark:text-zinc-950">{{ __('ui.browse.apply_filter') }}</button>
                                                            </form>
                                                        @else
                                                            <form method="GET" action="{{ route('dashboard') }}" class="space-y-2">
                                                                @include('partials.query-hidden-fields', ['values' => $formQuery, 'prefix' => ''])
                                                                @foreach ($facet['options'] as $option)
                                                                    <label class="flex items-center justify-between gap-2 text-xs text-zinc-600 dark:text-zinc-300"><span class="flex items-center gap-2"><input type="checkbox" name="option_values[{{ $facet['feature_slug'] }}][{{ $facet['detail_key'] }}][]" value="{{ $option['value'] }}" @checked($facet['selected'] === [] || in_array($option['value'], $facet['selected'], true)) class="rounded border-zinc-300">{{ $option['label'] }}</span><span class="text-zinc-400">{{ $option['count'] }}</span></label>
                                                                @endforeach
                                                                <button class="w-full rounded-md bg-zinc-900 px-3 py-2 text-xs font-medium text-white dark:bg-white dark:text-zinc-950">{{ __('ui.browse.apply_filter') }}</button>
                                                            </form>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </details>
                                        @endforeach
                                    </div>
                                    @if ($group->features->count() > 5)
                                        <button type="button" data-tag-more data-extra-count="{{ $group->features->count() - 5 }}" class="mt-1 px-2 py-1 text-xs font-medium text-zinc-500 hover:text-zinc-950 dark:hover:text-white">{{ __('ui.browse.show_more', ['count' => $group->features->count() - 5]) }}</button>
                                    @endif
                                </section>
                            @endforeach
                        </div>
                    </details>
                @endif

            </div>
        </aside>

        @guest
            <button
                type="button"
                data-auth-feature
                data-auth-title="{{ __('ui.suggest_place') }}"
                data-auth-message="{{ __('ui.browse.suggest_guest_help') }}"
                class="fixed grid place-items-center text-3xl font-light leading-none text-white shadow-lg transition hover:brightness-110 md:hidden" style="top:4.25rem;right:1rem;z-index:2147481900;width:3rem;height:3rem;border-radius:9999px;background:#2563eb;color:#fff"
                title="{{ __('ui.suggest_place') }}"
                aria-label="{{ __('ui.suggest_place') }}"
            >+</button>
        @else
            @php
                $mobilePermissionService = app(\App\Services\PermissionService::class);
            @endphp
            @if ($mobilePermissionService->can(auth()->user(), 'places.suggest'))
                <a
                    href="{{ route('places.suggest.create') }}"
                    class="fixed grid place-items-center text-3xl font-light leading-none text-white shadow-lg transition hover:brightness-110 md:hidden" style="top:4.25rem;right:1rem;z-index:2147481900;width:3rem;height:3rem;border-radius:9999px;background:#2563eb;color:#fff"
                    title="{{ __('ui.suggest_place') }}"
                    aria-label="{{ __('ui.suggest_place') }}"
                >+</a>
            @endif
        @endguest

        <section id="results-panel" class="min-w-0 bg-zinc-50 dark:bg-zinc-950">
            @if ($profileEnabled)
                <div class="border-b border-amber-300 bg-amber-50 px-5 py-3 text-xs text-amber-950 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-100">
                    <div class="mb-2 font-semibold">Browse Performance Profile</div>
                    <div class="grid gap-x-6 gap-y-1 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($browseProfile as $label => $milliseconds)
                            <div class="flex justify-between gap-3"><span>{{ $label }}</span><strong>{{ number_format($milliseconds, 2, ',', '.') }} ms</strong></div>
                        @endforeach
                        @foreach ($browseProfileFlags as $label => $value)
                            <div class="flex justify-between gap-3"><span>{{ $label }}</span><strong>{{ $value }}</strong></div>
                        @endforeach
                        <div class="flex justify-between gap-3"><span>Leaflet marker render</span><strong id="browse-profile-marker-time">…</strong></div>
                        <div class="flex justify-between gap-3"><span>Marker count</span><strong id="browse-profile-marker-count">…</strong></div>
                    </div>
                </div>
            @endif
            <div class="border-b border-zinc-200 bg-white px-5 py-4 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm text-zinc-500">
                            @if ($filteredPlaceCount > $resultLimit)
                                {{ __('ui.browse.results_limited', [
                                    'shown' => \Illuminate\Support\Number::format($resultLimit, locale: app()->getLocale()),
                                    'total' => \Illuminate\Support\Number::format($filteredPlaceCount, locale: app()->getLocale()),
                                ]) }}
                            @else
                                {{ __('ui.browse.results', ['count' => \Illuminate\Support\Number::format($filteredPlaceCount, locale: app()->getLocale())]) }}
                            @endif
                            @if ($q !== '')
                                · {{ __('ui.browse.searching_for', ['query' => $q]) }}
                            @endif
                            @if ($favoritesOnly)
                                · {{ mb_strtolower(__('ui.browse.favorites_only')) }}
                            @endif
                            @if ($mapFilter)
                                · {{ __('ui.browse.in_map_area') }}
                            @endif
                        </p>
                    </div>

                    <div class="flex shrink-0 items-start gap-2">
                        @guest
                            <button
                                type="button"
                                data-auth-feature
                                data-auth-title="{{ __('ui.suggest_place') }}"
                                data-auth-message="{{ __('ui.browse.suggest_guest_help') }}"
                                class="hidden rounded-lg bg-zinc-900 px-3 py-2 text-sm font-medium text-white hover:bg-zinc-700 md:inline-flex dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200"
                            >
                                {{ __('ui.suggest_place') }}
                            </button>
                        @endguest

                        <form method="GET" action="{{ route('dashboard') }}">
                            @if ($q !== '')
                                <input type="hidden" name="q" value="{{ $q }}">
                            @endif
                            @foreach ($selectedFeatures as $featureSlug)
                                <input type="hidden" name="features[]" value="{{ $featureSlug }}">
                            @endforeach
                            @if ($favoritesOnly)
                                <input type="hidden" name="favorites" value="1">
                            @endif
                            @foreach ($selectedPlaceTypes as $selectedPlaceType)
                                <input type="hidden" name="place_types[]" value="{{ $selectedPlaceType }}">
                            @endforeach
                            @foreach ($selectedVehicleTypes as $selectedVehicleType)
                                <input type="hidden" name="vehicle_types[]" value="{{ $selectedVehicleType }}">
                            @endforeach
                            @foreach ($mapQuery as $key => $value)
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endforeach
                            @include('partials.query-hidden-fields', ['values' => $facetQuery, 'prefix' => ''])
                            <input type="hidden" name="sort" value="{{ $sort }}" data-sort-field>
                            <input type="hidden" name="sort_direction" value="{{ $sortDirection }}" data-sort-direction-field>
                            <select
                                onchange="
                                    const [sort, direction] = this.value.split(':');
                                    this.form.querySelector('[data-sort-field]').value = sort;
                                    this.form.querySelector('[data-sort-direction-field]').value = direction;
                                    this.form.submit();
                                "
                                class="h-9 rounded-md border border-zinc-300 bg-white px-3 text-sm dark:border-zinc-700 dark:bg-zinc-900"
                                aria-label="{{ __('ui.browse.sort_direction') }}"
                            >
                                <option value="standard:" @selected($sort === 'standard')>{{ __('ui.browse.sort_standard') }}</option>
                                @foreach ([
                                    'newest' => __('ui.browse.sort_created'),
                                    'changed' => __('ui.browse.sort_changed'),
                                    'score' => __('ui.browse.sort_score'),
                                    'data_score' => __('ui.browse.sort_data_score'),
                                    'name' => __('ui.browse.sort_name'),
                                    'city' => __('ui.browse.sort_city'),
                                ] as $sortValue => $sortLabel)
                                    <option value="{{ $sortValue }}:asc" @selected($sort === $sortValue && $sortDirection === 'asc')>
                                        {{ $sortLabel }} - {{ __('ui.browse.sort_ascending') }}
                                    </option>
                                    <option value="{{ $sortValue }}:desc" @selected($sort === $sortValue && $sortDirection === 'desc')>
                                        {{ $sortLabel }} - {{ __('ui.browse.sort_descending') }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                </div>
            </div>

            <div class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse ($places as $place)
                    @php
                        $address = trim(implode(' ', array_filter([$place->street, $place->house_number])));
                        $location = trim(implode(' ', array_filter([$place->postal_code, $place->city])));
                        $typeLabel = $place->place_type_label;
                        $placeTypeIcons = \App\Support\PlaceTypeIconMap::iconsFor($place->place_type_slug);
                    @endphp
                    <article class="group bg-white p-5 transition hover:bg-zinc-50 dark:bg-zinc-900 dark:hover:bg-zinc-900/60" data-place-id="{{ $place->id }}">
                        <div class="browse-place-card-body">
                            <a href="{{ route('places.show', $place->slug) }}" class="browse-place-photo text-xs text-zinc-400">
                                @if ($place->thumbnail)
                                    <img
                                        src="{{ route('photos.show', ['uuid' => $place->thumbnail->uuid, 'variant' => 'preview']) }}"
                                        alt="{{ __('ui.browse.photo_alt', ['place' => $place->name]) }}"
                                        loading="lazy"
                                        class="size-full object-cover"
                                    >
                                @else
                                    <span
                                        class="browse-place-photo-placeholder {{ count($placeTypeIcons) > 1 ? 'is-multiple' : '' }}"
                                        aria-hidden="true"
                                        title="{{ $typeLabel }}"
                                    >
                                        @foreach ($placeTypeIcons as $placeTypeIcon)
                                            <x-tabler-icon :name="$placeTypeIcon" />
                                        @endforeach
                                    </span>
                                    <span class="sr-only">{{ $typeLabel }}</span>
                                @endif
                            </a>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-2 sm:gap-4">
                                    <div class="min-w-0">
                                        <h2 class="truncate font-semibold text-zinc-950 dark:text-white">
                                            <a href="{{ route('places.show', $place->slug) }}" class="hover:underline">{{ $place->name }}</a>
                                        </h2>
                                        <div class="mt-0.5 text-xs text-zinc-400">{{ $typeLabel }}</div>
                                        <p class="mt-1 text-sm text-zinc-500">
                                            {{ $location !== '' ? $location : __('ui.browse.location_missing') }}
                                            @if ($address !== '') · {{ $address }} @endif
                                        </p>
                                    </div>
                                    <div class="flex shrink-0 items-start gap-2">
                                        <div class="min-w-[5.5rem] text-right">
                                            <div class="leading-none">
                                                <span class="text-2xl font-bold tabular-nums text-zinc-950 dark:text-white sm:text-3xl">{{ number_format($place->score ?? 0, $place->score === null ? 0 : 1, app()->getLocale() === 'de' ? ',' : '.', app()->getLocale() === 'de' ? '.' : ',') }}</span>
                                                <span class="ml-0.5 text-sm text-zinc-400">/5</span>
                                            </div>
                                            <div class="mt-1 text-[11px] text-zinc-500">
                                                {{ trans_choice('ui.browse.review_count', $place->review_count, ['count' => $place->review_count]) }}
                                            </div>
                                        </div>
                                    @auth
                                        <form method="POST" action="{{ $place->is_favorite ? route('favorites.destroy', $place->slug) : route('favorites.store', $place->slug) }}" class="shrink-0">
                                            @csrf
                                            @if ($place->is_favorite)
                                                @method('DELETE')
                                            @endif
                                            <button
                                                type="submit"
                                                class="inline-flex size-9 items-center justify-center rounded-lg text-2xl leading-none transition {{ $place->is_favorite ? 'text-red-600 hover:bg-red-50 hover:text-red-700 dark:text-red-500 dark:hover:bg-red-950/30 dark:hover:text-red-400' : 'text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200' }}"
                                                title="{{ $place->is_favorite ? __('ui.browse.favorite_remove') : __('ui.browse.favorite_add') }}"
                                                aria-label="{{ $place->is_favorite ? __('ui.browse.favorite_remove') : __('ui.browse.favorite_add') }}"
                                            >
                                                {{ $place->is_favorite ? '♥' : '♡' }}
                                            </button>
                                        </form>
                                    @else
                                        <button
                                            type="button"
                                            data-auth-feature
                                            data-auth-title="{{ __('ui.browse.favorite_guest_title') }}"
                                            data-auth-message="{{ __('ui.browse.favorite_guest_help') }}"
                                            class="inline-flex size-9 shrink-0 items-center justify-center rounded-lg text-2xl leading-none text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
                                            title="{{ __('ui.browse.favorite_save') }}"
                                            aria-label="{{ __('ui.browse.favorite_save') }}"
                                        >♡</button>
                                    @endauth
                                    </div>
                                </div>

                                @if ($place->opening_status !== 'unclear')
                                    <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                                        @if ($place->opening_status === 'open')
                                            <span class="font-medium text-emerald-700 dark:text-emerald-400">{{ __('ui.browse.operating_status.active') }}</span>
                                            @if ($place->current_opening_state)
                                                <span class="text-zinc-300 dark:text-zinc-700">•</span>
                                                @if ($place->current_opening_state['state'] === 'open')
                                                    <span class="font-medium text-emerald-700 dark:text-emerald-400">{{ __('ui.browse.current_opening.open') }}</span>
                                                @elseif ($place->current_opening_state['state'] === 'closing_soon')
                                                    <span class="font-medium text-amber-700 dark:text-amber-400">{{ __('ui.browse.current_opening.closing_soon', ['minutes' => $place->current_opening_state['minutes_until_close']]) }}</span>
                                                @elseif ($place->current_opening_state['state'] === 'opening_soon')
                                                    <span class="font-medium text-amber-700 dark:text-amber-400">{{ __('ui.browse.current_opening.opening_soon', ['minutes' => $place->current_opening_state['minutes_until_open']]) }}</span>
                                                @else
                                                    <span class="font-medium text-red-700 dark:text-red-400">{{ __('ui.browse.current_opening.closed') }}</span>
                                                @endif
                                            @endif
                                        @else
                                            <span class="font-medium text-red-700 dark:text-red-400">{{ $openingLabels[$place->opening_status] ?? Str::headline(str_replace('_', ' ', $place->opening_status)) }}</span>
                                        @endif
                                    </div>
                                @endif

                                @if ($place->features->isNotEmpty())
                                    <div class="mt-3 flex flex-wrap items-center gap-1.5">
                                        @foreach ($place->features->take(3) as $feature)
                                            <span class="rounded-md border border-zinc-200 bg-zinc-50 px-2 py-1 text-xs text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">{{ $feature->label }}</span>
                                        @endforeach
                                        @if ($place->features->count() > 3)
                                            <span class="px-1 py-1 text-xs text-zinc-400">({{ __('ui.browse.more_features', ['count' => $place->features->count() - 3]) }})</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="bg-white p-10 text-center dark:bg-zinc-900">
                        <div class="font-medium">{{ __('ui.browse.no_results') }}</div>
                        <p class="mt-1 text-sm text-zinc-500">{{ __('ui.browse.no_results_help') }}</p>
                        <a href="{{ route('dashboard', ['reset_filters' => 1]) }}" class="mt-4 inline-block text-sm font-medium underline">{{ __('ui.browse.reset_all') }}</a>
                    </div>
                @endforelse
            </div>

            @if ($places->hasPages())
                <div class="border-t border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                    {{ $places->links() }}
                </div>
            @endif
        </section>

        <div id="browse-resizer" class="relative z-20 bg-zinc-100 dark:bg-zinc-950" title="{{ __('ui.browse.resize_help') }}">
            <div class="absolute left-1/2 top-1/2 z-10 flex -translate-x-1/2 -translate-y-1/2 flex-col overflow-hidden rounded-md border border-zinc-300 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <button type="button" data-show-results class="px-1.5 py-1 text-[10px] text-zinc-500 hover:bg-zinc-100 hover:text-zinc-950 dark:hover:bg-zinc-800 dark:hover:text-white" title="{{ __('ui.browse.show_list') }}">‹</button>
                <button type="button" data-show-map class="border-t border-zinc-200 px-1.5 py-1 text-[10px] text-zinc-500 hover:bg-zinc-100 hover:text-zinc-950 dark:border-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-white" title="{{ __('ui.browse.show_map') }}">›</button>
            </div>
        </div>

        <aside id="map-panel" class="relative bg-zinc-200 dark:bg-zinc-800">
            <div id="map" class="sticky top-16 h-[calc(100vh-4rem)] overflow-hidden">
                <div id="place-map" class="h-full w-full"></div>

                <details class="absolute right-3 top-3 min-w-[12rem] text-sm" style="z-index: 1100;">
                    <summary class="cursor-pointer list-none rounded-lg border border-zinc-300 bg-white/95 px-3 py-2 font-medium shadow-sm backdrop-blur dark:border-zinc-700 dark:bg-zinc-900/95">
                        {{ __('ui.browse.map_options') }}
                    </summary>
                    <div class="mt-2 space-y-2 rounded-lg border border-zinc-300 bg-white/95 p-2 shadow-lg backdrop-blur dark:border-zinc-700 dark:bg-zinc-900/95">
                        <button
                            id="map-location-button"
                            type="button"
                            class="flex w-full items-center gap-2 rounded-md px-2.5 py-2 text-left transition hover:bg-zinc-100 disabled:cursor-wait disabled:opacity-60 dark:hover:bg-zinc-800"
                            title="{{ __('ui.browse.my_location') }}"
                        >
                            <svg aria-hidden="true" viewBox="0 0 24 24" class="size-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="3"></circle>
                                <path d="M12 2v3M12 19v3M2 12h3M19 12h3"></path>
                                <circle cx="12" cy="12" r="7"></circle>
                            </svg>
                            <span>{{ __('ui.browse.my_location') }}</span>
                        </button>

                        <button
                            id="map-nearby-button"
                            type="button"
                            class="flex w-full items-center gap-2 rounded-md px-2.5 py-2 text-left transition hover:bg-zinc-100 disabled:cursor-wait disabled:opacity-60 dark:hover:bg-zinc-800"
                            title="{{ __('ui.browse.nearby_places') }}"
                        >
                            <svg aria-hidden="true" viewBox="0 0 24 24" class="size-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="2"></circle>
                                <circle cx="12" cy="12" r="6"></circle>
                                <circle cx="12" cy="12" r="10"></circle>
                            </svg>
                            <span>{{ __('ui.browse.nearby_places') }}</span>
                        </button>

                        <label class="flex cursor-pointer items-center gap-2 rounded-md px-2.5 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-800">
                            <input id="map-filter-toggle" type="checkbox" class="size-4 rounded border-zinc-300" @checked($mapFilter)>
                            <span>{{ __('ui.browse.filter_map_area') }}</span>
                        </label>
                    </div>
                </details>

                <div
                    id="map-location-status"
                    class="absolute right-3 top-14 max-w-[min(20rem,calc(100%-1.5rem))] rounded-lg border border-zinc-300 bg-white/95 px-3 py-2 text-xs text-zinc-700 shadow-sm backdrop-blur dark:border-zinc-700 dark:bg-zinc-900/95 dark:text-zinc-200" style="z-index: 1100;"
                    role="status"
                    aria-live="polite"
                    hidden
                ></div>

                @if ($markers->isEmpty())
                    <div class="pointer-events-none absolute inset-x-4 bottom-4 z-[500] rounded-lg border border-zinc-300 bg-white/95 p-3 text-sm shadow-sm backdrop-blur dark:border-zinc-700 dark:bg-zinc-900/95">
                        <div class="font-medium">{{ __('ui.browse.no_map_results') }}</div>
                        <div class="mt-1 text-xs text-zinc-500">{{ __('ui.browse.no_map_results_help') }}</div>
                    </div>
                @endif
            </div>
            <button
                id="mobile-map-toggle"
                type="button"
                class="hidden h-6 w-full items-center justify-center border-t border-zinc-200 bg-white text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400"
                aria-expanded="true"
                aria-label="{{ __('ui.browse.hide_map') }}"
                title="{{ __('ui.browse.hide_map') }}"
            >
                <span data-map-collapse-icon class="cw-double-chevron-up" aria-hidden="true"></span>
                <span data-map-expand-icon class="cw-double-chevron-down hidden" aria-hidden="true"></span>
            </button>
        </aside>
    </div>
</x-layouts::app>
