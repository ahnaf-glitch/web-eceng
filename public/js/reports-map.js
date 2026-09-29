(() => {
    const center = [-7.45, 112.72];
    const sidoarjoBounds = L.latLngBounds([-7.7, 112.45], [-7.18, 113.15]);

    function createMap(element) {
        const map = L.map(element, {
            maxBounds: sidoarjoBounds,
            maxBoundsViscosity: 0.8,
            scrollWheelZoom: false,
        }).setView(center, 11);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19,
        }).addTo(map);

        return map;
    }

    function addPopup(map, report) {
        const latitude = Number(report.latitude);
        const longitude = Number(report.longitude);
        const colors = { ringan: '#4d9862', sedang: '#d89c39', parah: '#d95e4b' };
        const marker = L.circleMarker([latitude, longitude], {
            radius: report.density === 'parah' ? 10 : report.density === 'sedang' ? 8 : 7,
            color: '#fffefa',
            weight: 2,
            fillColor: colors[report.density] || '#24745c',
            fillOpacity: 0.92,
        }).addTo(map);
        const popup = document.createElement('div');
        const title = document.createElement('strong');
        const detail = document.createElement('span');
        const reporter = document.createElement('span');
        const googleLink = document.createElement('a');

        popup.className = 'map-popup';
        title.textContent = report.location;
        detail.textContent = `Kepadatan ${report.density} · ${report.status}`;
        reporter.textContent = `${report.reporter} · ${report.created_at}`;
        googleLink.href = `https://www.google.com/maps/search/?api=1&query=${latitude},${longitude}`;
        googleLink.target = '_blank';
        googleLink.rel = 'noopener';
        googleLink.textContent = 'Buka titik di Google Maps →';
        popup.append(title, detail, reporter, googleLink);
        marker.bindPopup(popup);
    }

    function setupPicker(element) {
        const map = createMap(element);
        const latitudeInput = document.getElementById('latitude');
        const longitudeInput = document.getElementById('longitude');
        const selection = document.getElementById('map-selection');
        let pin;

        function setPin(latitude, longitude) {
            if (!sidoarjoBounds.contains([latitude, longitude])) {
                selection.textContent = 'Pilih titik di dalam wilayah Sidoarjo.';
                return;
            }

            latitudeInput.value = latitude.toFixed(7);
            longitudeInput.value = longitude.toFixed(7);
            selection.textContent = `Titik terpilih: ${latitude.toFixed(5)}, ${longitude.toFixed(5)}. Geser peta atau pilih titik lain untuk menyesuaikan.`;

            if (pin) {
                pin.setLatLng([latitude, longitude]);
            } else {
                pin = L.marker([latitude, longitude]).addTo(map);
            }
        }

        map.on('click', (event) => setPin(event.latlng.lat, event.latlng.lng));

        if (latitudeInput.value && longitudeInput.value) {
            const latitude = Number(latitudeInput.value);
            const longitude = Number(longitudeInput.value);
            setPin(latitude, longitude);
            map.setView([latitude, longitude], 15);
        }

        window.setTimeout(() => map.invalidateSize(), 100);
    }

    function setupReportMap(element) {
        const map = createMap(element);
        const data = JSON.parse(document.getElementById('map-report-data')?.textContent || '[]');

        data.forEach((report) => addPopup(map, report));

        if (data.length === 1) {
            map.setView([data[0].latitude, data[0].longitude], 14);
        } else if (data.length > 1) {
            const points = data.map((report) => [report.latitude, report.longitude]);
            map.fitBounds(L.latLngBounds(points).pad(0.15), { maxZoom: 14 });
        }

        window.setTimeout(() => map.invalidateSize(), 100);
    }

    document.querySelectorAll('[data-map-mode="pick"]').forEach(setupPicker);
    document.querySelectorAll('[data-map-mode="markers"]').forEach(setupReportMap);
})();