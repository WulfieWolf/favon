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

function initFavonPlacePickers() {
    document.querySelectorAll('[data-favon-place-picker]').forEach((element) => {
        if (element.dataset.ready === '1') {
            return;
        }

        const latInput = document.getElementById(element.dataset.latInput || '');
        const lngInput = document.getElementById(element.dataset.lngInput || '');

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

        let marker = hasExisting ? L.marker(start, { draggable: true }).addTo(map) : null;

        const setPoint = (latlng) => {
            if (!marker) {
                marker = L.marker(latlng, { draggable: true }).addTo(map);
                marker.on('dragend', () => setPoint(marker.getLatLng()));
            } else {
                marker.setLatLng(latlng);
            }

            latInput.value = Number(latlng.lat).toFixed(7);
            lngInput.value = Number(latlng.lng).toFixed(7);
        };

        map.on('click', (event) => setPoint(event.latlng));

        if (marker) {
            marker.on('dragend', () => setPoint(marker.getLatLng()));
        }

        requestAnimationFrame(() => map.invalidateSize());
    });
}

document.addEventListener('DOMContentLoaded', initFavonPlacePickers, { once: true });
document.addEventListener('livewire:navigated', initFavonPlacePickers);
initFavonPlacePickers();

