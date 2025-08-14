@php
    $lat = isset($lat) ? (float) $lat : 9.005401;
    $lng = isset($lng) ? (float) $lng : 38.763611;
    $mapId = 'map_' . uniqid();
@endphp

<div wire:ignore>
    @once
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
            integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        <style>
            .map-picker-container {
                position: relative;
            }

            .map-picker {
                height: 320px;
                border-radius: 0.5rem;
                overflow: hidden;
            }

            .leaflet-container {
                z-index: 0;
            }
        </style>
    @endonce

    <div id="{{ $mapId }}" class="map-picker"></div>

    <div class="mt-2 text-sm text-gray-600">
        Click the map or drag the marker to set the coordinates.
    </div>

    <script>
        (function initMapPicker() {
            const elId = @json($mapId);
            const initialLat = {{ $lat }};
            const initialLng = {{ $lng }};

            // ensure Leaflet is loaded and element exists before initializing
            function ready() {
                return !!(window.L && document.getElementById(elId));
            }

            function findInputByNames(names) {
                for (const name of names) {
                    const el = document.querySelector(`[name="${name}"]`);
                    if (el) return el;
                }
                return null;
            }

            function setField(names, value) {
                const input = findInputByNames(names);
                if (!input) return;
                input.value = value;
                input.dispatchEvent(new Event('input', {
                    bubbles: true
                }));
                input.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
            }

            function getField(names, fallback) {
                const input = findInputByNames(names);
                if (!input) return fallback;
                const v = parseFloat(input.value);
                return isNaN(v) ? fallback : v;
            }

            function init() {
                if (!ready()) {
                    return setTimeout(init, 100);
                }

                const el = document.getElementById(elId);
                if (!el || el.dataset.initialized) return;
                el.dataset.initialized = 'true';

                const map = L.map(el).setView([initialLat, initialLng], 13);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);

                const marker = L.marker([initialLat, initialLng], {
                    draggable: true
                }).addTo(map);

                function updateCoords(lat, lng) {
                    setField(['location.lat', 'location[lat]'], lat.toFixed(6));
                    setField(['location.long', 'location[long]'], lng.toFixed(6));
                }

                map.on('click', function(e) {
                    marker.setLatLng(e.latlng);
                    updateCoords(e.latlng.lat, e.latlng.lng);
                });

                marker.on('dragend', function(e) {
                    const {
                        lat,
                        lng
                    } = e.target.getLatLng();
                    updateCoords(lat, lng);
                });

                // Sync marker if inputs change outside the map
                const latInput = findInputByNames(['location.lat', 'location[lat]']);
                const lngInput = findInputByNames(['location.long', 'location[long]']);
                [latInput, lngInput].forEach((el) => {
                    if (!el) return;
                    el.addEventListener('input', () => {
                        const lat = getField(['location.lat', 'location[lat]'], initialLat);
                        const lng = getField(['location.long', 'location[long]'], initialLng);
                        marker.setLatLng([lat, lng]);
                    });
                });

                // Fix sizing after render and on container visibility changes
                setTimeout(() => map.invalidateSize(), 150);
                window.addEventListener('resize', () => map.invalidateSize());
            }

            init();
        })();
    </script>
</div>
