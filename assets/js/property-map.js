(function () {
    'use strict';

    const config = window.AVANTE_MAP || {};
    const storageKey = 'avante_property_map_data_v2';
    const propertySearch = document.getElementById('property-map-search');
    const activitySearch = document.getElementById('property-activity-search');
    const propertyResults = document.getElementById('property-search-results');
    const activityResults = document.getElementById('activity-search-results');
    const zoneSelect = document.getElementById('property-map-zone');
    const count = document.getElementById('property-map-count');
    const status = document.getElementById('property-map-status');
    const updateButton = document.getElementById('property-map-update');
    const resetButton = document.getElementById('property-map-reset');
    const fileInput = document.getElementById('property-map-file');
    const legend = document.getElementById('property-map-legend');
    const regionList = document.getElementById('property-map-regions');
    const cards = document.getElementById('property-map-cards');
    const viewCount = document.getElementById('property-map-view-count');
    const refreshAreaButton = document.getElementById('property-map-refresh-area');
    const nearbyButton = document.getElementById('property-map-nearby');
    const fullscreenButton = document.getElementById('property-map-fullscreen');
    const markerModeSelect = document.getElementById('property-map-marker-mode');
    const loading = document.getElementById('property-map-loading');
    const mapShell = document.querySelector('.property-map-shell');

    const regionColors = {
        'Western Cape (Cape Town & Winelands)': '#7f77dd',
        'Eastern Cape & Garden Route': '#d85a30',
        'Northern Cape (West Coast & Karoo)': '#888780',
        'Free State': '#ba7517',
        'KwaZulu-Natal': '#1d9e75',
        'Gauteng & North West': '#378add',
        'Mpumalanga': '#639922',
        'Limpopo': '#d4537e',
    };

    if (!window.L || !document.getElementById('property-map')) {
        showStatus('The map library could not be loaded. Please refresh and try again.', 'error');
        return;
    }

    const map = L.map('property-map', { zoomControl: true }).setView([-29, 25], 6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 18,
    }).addTo(map);

    const propertyLayer = L.markerClusterGroup({ maxClusterRadius: 45, disableClusteringAtZoom: 15, chunkedLoading: true });
    const activityLayer = L.markerClusterGroup({
        maxClusterRadius: 45,
        disableClusteringAtZoom: 13,
        chunkedLoading: true,
        iconCreateFunction(cluster) {
            return L.divIcon({
                className: '',
                html: '<div class="activity-cluster">' + cluster.getChildCount() + '</div>',
                iconSize: [34, 34],
            });
        },
    });
    map.addLayer(propertyLayer);

    let shippedProperties = [];
    let properties = [];
    let propertyMarkers = [];
    let activityMarkers = [];
    let activitiesVisible = false;
    let visiblePropertyMarkers = [];
    let markerMode = 'link';
    let userMarker = null;
    let areaRefreshPending = false;
    const maxCards = 60;
    const iconCache = new Map();

    function escapeHtml(value) {
        const node = document.createElement('span');
        node.textContent = value == null ? '' : String(value);
        return node.innerHTML;
    }

    function normalize(value) {
        return String(value || '').toLowerCase().replace(/[^a-z0-9]/g, '');
    }

    function propertyText(record) {
        if (!record._search) record._search = normalize([record.n, record.a, record.c, record.z, record.s].filter(Boolean).join(' '));
        return record._search;
    }

    function activityText(record) {
        if (!record._search) record._search = normalize([record.n, record.co, record.t, record.p, record.ph].filter(Boolean).join(' '));
        return record._search;
    }

    function markerIcon(color, size) {
        const width = size || 22;
        const height = Math.round(width * 30 / 22);
        const key = color + ':' + width;
        if (iconCache.has(key)) return iconCache.get(key);
        const icon = L.divIcon({
            className: '',
            html: '<svg width="' + width + '" height="' + height + '" viewBox="0 0 22 30" aria-hidden="true">' +
                '<path d="M11 0C4.9 0 0 4.9 0 11c0 8.3 11 19 11 19s11-10.7 11-19C22 4.9 17.1 0 11 0z" fill="' + color + '" stroke="#fff" stroke-width="1.5"/>' +
                '<circle cx="11" cy="11" r="4" fill="#fff"/></svg>',
            iconSize: [width, height],
            iconAnchor: [width / 2, height],
            popupAnchor: [0, -height + 2],
        });
        iconCache.set(key, icon);
        return icon;
    }

    function resortUrl(record) {
        if (!record.sid || !record.id) return '';
        return 'https://old.stocknetwork.co.za/ResortInfo.aspx?ResortID=' +
            encodeURIComponent(record.id) + '&SiteID=' + encodeURIComponent(record.sid);
    }

    function markerColor(record) {
        if (markerMode === 'region') return regionColors[record.z] || '#697383';
        if (markerMode === 'rating') {
            const rating = Number.parseInt(record.s, 10);
            if (rating >= 5) return '#7f3fbf';
            if (rating >= 4) return '#087f73';
            if (rating >= 3) return '#378add';
            return '#888780';
        }
        return resortUrl(record) ? '#378add' : '#ef9f27';
    }

    function validHttpUrl(value) {
        try {
            const url = new URL(value);
            return url.protocol === 'http:' || url.protocol === 'https:' ? url.href : '';
        } catch (error) {
            return '';
        }
    }

    function createPropertyMarker(record) {
        const url = resortUrl(record);
        const marker = L.marker([record.lat, record.lon], { icon: markerIcon(markerColor(record)) });
        const place = record.a || record.c || '';
        const meta = escapeHtml(place) + (record.s ? ' &middot; ' + escapeHtml(record.s) : '');
        const action = url
            ? '<a class="property-popup-link" href="' + escapeHtml(url) + '" target="_blank" rel="noopener noreferrer">View on StockNetwork &rarr;</a>'
            : '<div class="property-popup-note">No direct link on file. Search for this property on <a href="https://stock.stocknetwork.co.za/" target="_blank" rel="noopener noreferrer">StockNetwork</a>.</div>';
        marker.bindPopup('<div class="property-popup-name">' + escapeHtml(record.n) + '</div>' +
            '<div class="property-popup-meta">' + meta + '</div>' + action);
        marker.record = record;
        return marker;
    }

    function buildPropertyMarkers(records) {
        propertyLayer.clearLayers();
        propertyMarkers = records.map(createPropertyMarker);
        populateZones(records);
        renderRegions(records);
        applyPropertyFilters();
    }

    function populateZones(records) {
        const selected = zoneSelect.value;
        const zones = [...new Set(records.map(record => record.z).filter(Boolean))].sort();
        zoneSelect.replaceChildren(new Option('All zones', ''));
        zones.forEach(zone => zoneSelect.add(new Option(zone, zone)));
        zoneSelect.value = zones.includes(selected) ? selected : '';
    }

    function applyPropertyFilters() {
        const query = normalize(propertySearch.value);
        const zone = zoneSelect.value;
        propertyLayer.clearLayers();
        visiblePropertyMarkers = propertyMarkers.filter(marker => {
            return (!zone || marker.record.z === zone) && (!query || propertyText(marker.record).includes(query));
        });
        propertyLayer.addLayers(visiblePropertyMarkers);
        count.textContent = visiblePropertyMarkers.length.toLocaleString() + ' of ' + propertyMarkers.length.toLocaleString() + ' properties';
        updateUrlState();
        renderCardsInView();
    }

    function renderRegions(records) {
        const counts = records.reduce((totals, record) => {
            if (record.z) totals[record.z] = (totals[record.z] || 0) + 1;
            return totals;
        }, {});
        regionList.replaceChildren();
        Object.entries(counts).sort((a, b) => b[1] - a[1]).forEach(([region, total]) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'property-map-region';
            button.style.setProperty('--region-color', regionColors[region] || '#697383');
            button.setAttribute('aria-pressed', String(zoneSelect.value === region));
            const name = document.createElement('strong');
            name.textContent = region.replace(/\s*\(.+\)$/, '');
            const amount = document.createElement('span');
            amount.textContent = total.toLocaleString() + ' properties';
            button.append(name, amount);
            button.addEventListener('click', () => {
                zoneSelect.value = zoneSelect.value === region ? '' : region;
                propertySearch.value = '';
                applyPropertyFilters();
                renderRegions(properties);
                fitMarkers(visiblePropertyMarkers);
            });
            regionList.appendChild(button);
        });
    }

    function distanceKm(first, second) {
        const radians = degrees => degrees * Math.PI / 180;
        const latitude = radians(second.lat - first.lat);
        const longitude = radians(second.lon - first.lon);
        const value = Math.sin(latitude / 2) ** 2 + Math.cos(radians(first.lat)) *
            Math.cos(radians(second.lat)) * Math.sin(longitude / 2) ** 2;
        return 6371 * 2 * Math.atan2(Math.sqrt(value), Math.sqrt(1 - value));
    }

    function nearbyActivities(record, radiusKm) {
        return activityMarkers.filter(marker => distanceKm(record, marker.record) <= radiusKm);
    }

    function focusProperty(marker) {
        propertyLayer.zoomToShowLayer(marker, () => {
            map.setView(marker.getLatLng(), Math.max(map.getZoom(), 15));
            marker.openPopup();
        });
    }

    function createPropertyCard(marker) {
        const record = marker.record;
        const card = document.createElement('article');
        card.className = 'property-map-card';
        card.tabIndex = 0;
        const accent = document.createElement('span');
        accent.className = 'property-map-card-accent';
        accent.style.backgroundColor = markerColor(record);
        const content = document.createElement('div');
        content.className = 'property-map-card-content';
        const title = document.createElement('h3');
        title.textContent = record.n || 'Unnamed property';
        const location = document.createElement('p');
        location.textContent = [record.c, record.a].filter(Boolean).join(' · ') || record.z || '';
        const footer = document.createElement('div');
        footer.className = 'property-map-card-footer';
        const rating = document.createElement('span');
        rating.className = 'property-map-card-rating';
        rating.textContent = record.s || 'Not graded';
        footer.appendChild(rating);

        const activities = nearbyActivities(record, 30);
        if (activities.length) {
            const activityButton = document.createElement('button');
            activityButton.type = 'button';
            activityButton.textContent = activities.length + ' nearby activit' + (activities.length === 1 ? 'y' : 'ies');
            activityButton.addEventListener('click', event => {
                event.stopPropagation();
                setActivitiesVisible(true);
                fitMarkers([marker, ...activities]);
            });
            footer.appendChild(activityButton);
        }
        content.append(title, location, footer);
        card.append(accent, content);
        card.addEventListener('click', () => focusProperty(marker));
        card.addEventListener('keydown', event => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                focusProperty(marker);
            }
        });
        return card;
    }

    function renderCardsInView() {
        if (!map || !visiblePropertyMarkers.length) {
            cards.innerHTML = '<p class="property-map-empty">No properties match these filters.</p>';
            viewCount.textContent = 'Try another search or region';
            return;
        }
        const bounds = map.getBounds();
        const center = map.getCenter();
        const inView = visiblePropertyMarkers
            .filter(marker => bounds.contains(marker.getLatLng()))
            .sort((first, second) => center.distanceTo(first.getLatLng()) - center.distanceTo(second.getLatLng()));
        cards.replaceChildren(...inView.slice(0, maxCards).map(createPropertyCard));
        viewCount.textContent = inView.length.toLocaleString() + ' visible' +
            (inView.length > maxCards ? ' · showing nearest ' + maxCards : '');
        if (!inView.length) cards.innerHTML = '<p class="property-map-empty">No matching properties in this map area.</p>';
        areaRefreshPending = false;
        refreshAreaButton.classList.remove('is-ready');
    }

    function updateUrlState() {
        const params = new URLSearchParams();
        if (propertySearch.value.trim()) params.set('q', propertySearch.value.trim());
        if (zoneSelect.value) params.set('zone', zoneSelect.value);
        if (markerMode !== 'link') params.set('markers', markerMode);
        if (activitySearch.value.trim()) params.set('activity', activitySearch.value.trim());
        if (activitiesVisible) params.set('activities', '1');
        const center = map.getCenter();
        params.set('lat', center.lat.toFixed(4));
        params.set('lng', center.lng.toFixed(4));
        params.set('zoom', String(map.getZoom()));
        const query = params.toString();
        history.replaceState(null, '', location.pathname + (query ? '?' + query : ''));
    }

    function applyUrlState() {
        const params = new URLSearchParams(location.search);
        propertySearch.value = params.get('q') || '';
        const requestedZone = params.get('zone') || '';
        if ([...zoneSelect.options].some(option => option.value === requestedZone)) zoneSelect.value = requestedZone;
        const requestedMode = params.get('markers');
        if (['link', 'region', 'rating'].includes(requestedMode)) {
            markerMode = requestedMode;
            markerModeSelect.value = markerMode;
        }
        activitySearch.value = params.get('activity') || '';
        activitiesVisible = params.get('activities') === '1';
        if (params.has('lat') && params.has('lng') && params.has('zoom')) {
            const lat = Number(params.get('lat'));
            const lng = Number(params.get('lng'));
            const zoom = Number(params.get('zoom'));
            if (Number.isFinite(lat) && Number.isFinite(lng) && Number.isFinite(zoom) &&
                lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180 && zoom >= 2 && zoom <= 18) {
                map.setView([lat, lng], zoom);
            }
        }
    }

    function createActivityMarker(record) {
        const marker = L.marker([record.lat, record.lon], { icon: markerIcon('#d64545', 20) });
        const imageUrl = validHttpUrl(record.img);
        const bookingUrl = validHttpUrl(record.u);
        const price = record.p === '' || record.p == null ? '' :
            (isNaN(Number(record.p)) ? String(record.p) : 'R' + Number(record.p).toLocaleString());
        const image = imageUrl ? '<img class="property-popup-image" src="' + escapeHtml(imageUrl) + '" alt="" loading="lazy" onerror="this.remove()">' : '';
        const meta = [price, record.t].filter(Boolean).map(escapeHtml).join(' &middot; ');
        const action = bookingUrl
            ? '<a class="property-popup-link" href="' + escapeHtml(bookingUrl) + '" target="_blank" rel="noopener noreferrer">Book or view &rarr;</a>'
            : (record.ph ? '<div class="property-popup-note">Call ' + escapeHtml(record.ph) + ' to book.</div>' : '');
        marker.bindPopup('<div class="property-activity-popup">' + image +
            '<div class="property-popup-name">' + escapeHtml(record.n) + '</div>' +
            '<div class="property-popup-meta"><strong>' + escapeHtml(record.co) + '</strong>' +
            (meta ? '<br>' + meta : '') + '</div>' + action + '</div>');
        marker.record = record;
        return marker;
    }

    function buildActivityMarkers(records) {
        activityLayer.clearLayers();
        activityMarkers = records.filter(hasCoordinates).map(createActivityMarker);
        activityLayer.addLayers(activityMarkers);
    }

    function hasCoordinates(record) {
        return Number.isFinite(Number(record.lat)) && Number.isFinite(Number(record.lon));
    }

    function renderLegend() {
        let propertyLegend = '<div class="property-map-legend-row"><span class="property-map-swatch" style="background:#378add"></span>Direct link available</div>' +
            '<div class="property-map-legend-row"><span class="property-map-swatch" style="background:#ef9f27"></span>No direct link</div>';
        if (markerMode === 'rating') {
            propertyLegend = '<div class="property-map-legend-row"><span class="property-map-swatch" style="background:#7f3fbf"></span>5 star</div>' +
                '<div class="property-map-legend-row"><span class="property-map-swatch" style="background:#087f73"></span>4 star</div>' +
                '<div class="property-map-legend-row"><span class="property-map-swatch" style="background:#378add"></span>3 star</div>' +
                '<div class="property-map-legend-row"><span class="property-map-swatch" style="background:#888780"></span>Other / not graded</div>';
        } else if (markerMode === 'region') {
            propertyLegend = Object.entries(regionColors).map(([name, color]) =>
                '<div class="property-map-legend-row"><span class="property-map-swatch" style="background:' + color + '"></span>' + escapeHtml(name.replace(/\s*\(.+\)$/, '')) + '</div>'
            ).join('');
        }
        legend.innerHTML = propertyLegend + '<button id="property-map-activity-toggle" type="button" aria-pressed="' + String(activitiesVisible) + '"><span class="property-map-swatch" style="background:#d64545"></span>Activities &amp; excursions <span>' + (activitiesVisible ? '(on)' : '(off)') + '</span></button>';
        document.getElementById('property-map-activity-toggle').addEventListener('click', toggleActivities);
    }

    function setActivitiesVisible(visible) {
        activitiesVisible = visible;
        if (visible) map.addLayer(activityLayer);
        else map.removeLayer(activityLayer);
        const toggle = document.getElementById('property-map-activity-toggle');
        toggle.setAttribute('aria-pressed', String(visible));
        toggle.lastElementChild.textContent = visible ? '(on)' : '(off)';
    }

    function toggleActivities() {
        setActivitiesVisible(!activitiesVisible);
    }

    function fitMarkers(markers) {
        if (!markers.length) return;
        if (markers.length === 1) {
            map.setView(markers[0].getLatLng(), 16);
            return;
        }
        map.fitBounds(L.latLngBounds(markers.map(marker => marker.getLatLng())).pad(0.15));
    }

    function closeResults(element) {
        element.replaceChildren();
        element.classList.remove('is-open');
    }

    function renderSearchResults(input, resultsElement, markers, textGetter, ensureLayer) {
        const query = normalize(input.value);
        closeResults(resultsElement);
        if (!query) return;
        const matches = markers.filter(marker => textGetter(marker.record).includes(query));

        if (!matches.length) {
            const empty = document.createElement('span');
            empty.className = 'property-map-results-empty';
            empty.textContent = 'No matches found';
            resultsElement.appendChild(empty);
        } else {
            matches.slice(0, 10).forEach(marker => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'property-map-result';
                const name = document.createElement('span');
                name.className = 'property-map-result-name';
                name.textContent = marker.record.n;
                const meta = document.createElement('span');
                meta.className = 'property-map-result-meta';
                meta.textContent = marker.record.a || marker.record.c || marker.record.co || marker.record.t || '';
                button.append(name, meta);
                button.addEventListener('click', () => {
                    input.value = marker.record.n;
                    closeResults(resultsElement);
                    if (ensureLayer) ensureLayer();
                    map.setView(marker.getLatLng(), 16);
                    marker.openPopup();
                });
                resultsElement.appendChild(button);
            });

            if (matches.length > 1) {
                const showAll = document.createElement('button');
                showAll.type = 'button';
                showAll.className = 'property-map-result';
                showAll.textContent = 'Show all ' + matches.length.toLocaleString() + ' matches on map';
                showAll.addEventListener('click', () => {
                    closeResults(resultsElement);
                    if (ensureLayer) ensureLayer();
                    fitMarkers(matches);
                });
                resultsElement.appendChild(showAll);
            }
        }
        resultsElement.classList.add('is-open');
    }

    function showStatus(message, type) {
        status.textContent = message;
        status.className = 'property-map-status' + (type ? ' is-' + type : '');
        status.hidden = false;
    }

    const provinces = new Set(['Western Cape', 'Eastern Cape', 'Northern Cape', 'Free State', 'KwaZulu-Natal', 'Gauteng', 'North West', 'Mpumalanga', 'Limpopo']);

    function effectiveState(row) {
        const state = String(row.State || '').trim();
        if (provinces.has(state)) return state;
        if (state === 'Garden Route' || state === 'Cape - Garden Route') return 'Western Cape';
        const district = String(row.District || '').trim();
        return provinces.has(district) ? district : '';
    }

    function assignZone(row) {
        const state = effectiveState(row);
        const area = String(row.Area || '').trim();
        const rawState = String(row.State || '').trim();
        if (state === 'Eastern Cape') return 'Eastern Cape & Garden Route';
        if (state === 'Western Cape') {
            return area === 'Cape - Garden Route' || area === 'Wild Coast' || rawState === 'Garden Route' || rawState === 'Cape - Garden Route'
                ? 'Eastern Cape & Garden Route' : 'Western Cape (Cape Town & Winelands)';
        }
        if (state === 'Northern Cape') return 'Northern Cape (West Coast & Karoo)';
        if (state === 'Gauteng' || state === 'North West') return 'Gauteng & North West';
        return state;
    }

    function parsePropertiesCsv(text) {
        const parsed = Papa.parse(text, { header: true, skipEmptyLines: true });
        if (parsed.errors.length && !parsed.data.length) throw new Error(parsed.errors[0].message);
        const skipped = { zone: 0, coordinates: 0, id: 0 };
        const records = [];
        parsed.data.forEach(row => {
            const zone = assignZone(row);
            const lat = Number.parseFloat(row.Latitude);
            const lon = Number.parseFloat(row.Longitude);
            const id = String(row.ResortID || '').trim();
            if (!zone) { skipped.zone += 1; return; }
            if (!Number.isFinite(lat) || !Number.isFinite(lon) || lat > -20 || lat < -36 || lon < 15 || lon > 34) { skipped.coordinates += 1; return; }
            if (id.length <= 10) { skipped.id += 1; return; }
            const siteId = String(row.SiteID || '').trim();
            records.push({
                n: String(row.Resort || '').trim(), lat: Math.round(lat * 100000) / 100000,
                lon: Math.round(lon * 100000) / 100000, id, sid: !siteId || siteId.toLowerCase() === 'nan' ? null : siteId,
                z: zone, a: String(row.Area || '').trim(), c: String(row.City || '').trim(),
                s: String(row['Supplier Rating'] || '').trim(),
            });
        });
        return { records, total: parsed.data.length, skipped };
    }

    function loadSavedProperties() {
        try {
            const saved = JSON.parse(localStorage.getItem(storageKey));
            if (saved && Array.isArray(saved.records) && saved.records.length) {
                resetButton.hidden = false;
                showStatus('Using locally imported data from ' + (saved.filename || 'CSV') + '.', 'success');
                return saved.records;
            }
        } catch (error) {
            localStorage.removeItem(storageKey);
        }
        return shippedProperties;
    }

    propertySearch.addEventListener('input', () => {
        applyPropertyFilters();
        renderSearchResults(propertySearch, propertyResults, propertyMarkers, propertyText);
    });
    propertySearch.addEventListener('focus', () => renderSearchResults(propertySearch, propertyResults, propertyMarkers, propertyText));
    zoneSelect.addEventListener('change', () => {
        applyPropertyFilters();
        renderRegions(properties);
        fitMarkers(visiblePropertyMarkers);
    });
    activitySearch.addEventListener('input', () => {
        renderSearchResults(activitySearch, activityResults, activityMarkers, activityText, () => setActivitiesVisible(true));
        updateUrlState();
    });
    activitySearch.addEventListener('focus', () => renderSearchResults(activitySearch, activityResults, activityMarkers, activityText, () => setActivitiesVisible(true)));
    document.addEventListener('click', event => {
        if (!event.target.closest('.property-map-search')) {
            closeResults(propertyResults);
            closeResults(activityResults);
        }
    });

    refreshAreaButton.addEventListener('click', renderCardsInView);
    map.on('movestart', () => {
        areaRefreshPending = true;
        refreshAreaButton.classList.add('is-ready');
    });
    map.on('moveend', updateUrlState);

    markerModeSelect.addEventListener('change', () => {
        markerMode = markerModeSelect.value;
        buildPropertyMarkers(properties);
        renderLegend();
    });

    nearbyButton.addEventListener('click', () => {
        if (!navigator.geolocation) {
            showStatus('Your browser does not support location lookup.', 'error');
            return;
        }
        nearbyButton.disabled = true;
        nearbyButton.textContent = 'Finding you…';
        navigator.geolocation.getCurrentPosition(position => {
            const locationPoint = L.latLng(position.coords.latitude, position.coords.longitude);
            if (userMarker) userMarker.remove();
            userMarker = L.circleMarker(locationPoint, {
                radius: 8, color: '#fff', weight: 3, fillColor: '#0dcdc2', fillOpacity: 1,
            }).addTo(map).bindPopup('You are here');
            const nearby = visiblePropertyMarkers
                .filter(marker => locationPoint.distanceTo(marker.getLatLng()) <= 100000)
                .sort((first, second) => locationPoint.distanceTo(first.getLatLng()) - locationPoint.distanceTo(second.getLatLng()));
            if (nearby.length) {
                map.fitBounds(L.featureGroup([userMarker, ...nearby.slice(0, 30)]).getBounds().pad(0.15));
                showStatus(nearby.length.toLocaleString() + ' properties found within 100 km of you.', 'success');
            } else {
                map.setView(locationPoint, 10);
                showStatus('No matching properties were found within 100 km. The map is centred on your location.', 'error');
            }
            nearbyButton.disabled = false;
            nearbyButton.textContent = 'Near me';
        }, error => {
            showStatus(error.code === 1 ? 'Location access was not granted.' : 'Your location could not be determined.', 'error');
            nearbyButton.disabled = false;
            nearbyButton.textContent = 'Near me';
        }, { enableHighAccuracy: false, timeout: 10000, maximumAge: 300000 });
    });

    fullscreenButton.addEventListener('click', () => {
        if (!document.fullscreenElement && mapShell.requestFullscreen) mapShell.requestFullscreen().catch(() => showStatus('Full-screen mode is not available in this browser.', 'error'));
        else if (!document.fullscreenElement) showStatus('Full-screen mode is not available in this browser.', 'error');
        else document.exitFullscreen();
    });
    document.addEventListener('fullscreenchange', () => {
        const active = document.fullscreenElement === mapShell;
        mapShell.classList.toggle('is-fullscreen', active);
        fullscreenButton.setAttribute('aria-pressed', String(active));
        fullscreenButton.textContent = active ? 'Exit full screen' : 'Full screen';
        setTimeout(() => map.invalidateSize(), 0);
    });

    updateButton.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', () => {
        const file = fileInput.files[0];
        if (!file) return;
        showStatus('Reading ' + file.name + '…');
        const reader = new FileReader();
        reader.onload = () => {
            try {
                const result = parsePropertiesCsv(reader.result);
                if (!result.records.length) throw new Error('No usable property rows were found.');
                properties = result.records;
                buildPropertyMarkers(properties);
                try {
                    localStorage.setItem(storageKey, JSON.stringify({ records: properties, filename: file.name, savedAt: new Date().toISOString() }));
                } catch (error) {
                    showStatus('Data loaded, but the browser could not save it for future visits.', 'error');
                    return;
                }
                resetButton.hidden = false;
                showStatus('Loaded ' + properties.length.toLocaleString() + ' of ' + result.total.toLocaleString() + ' rows from ' + file.name + '.', 'success');
            } catch (error) {
                showStatus('Could not import that file: ' + error.message, 'error');
            } finally {
                fileInput.value = '';
            }
        };
        reader.onerror = () => showStatus('Could not read that file.', 'error');
        reader.readAsText(file);
    });

    resetButton.addEventListener('click', () => {
        localStorage.removeItem(storageKey);
        properties = shippedProperties;
        resetButton.hidden = true;
        buildPropertyMarkers(properties);
        showStatus('Reset to the property data shipped with the app.', 'success');
    });

    renderLegend();
    Promise.all([
        fetch(config.propertiesUrl).then(response => {
            if (!response.ok) throw new Error('Property data returned HTTP ' + response.status + '.');
            return response.json();
        }),
        fetch(config.activitiesUrl).then(response => {
            if (!response.ok) throw new Error('Activity data returned HTTP ' + response.status + '.');
            return response.json();
        }),
    ]).then(([propertyData, activityData]) => {
        if (!Array.isArray(propertyData) || !Array.isArray(activityData)) throw new Error('Map data has an invalid format.');
        shippedProperties = propertyData.filter(hasCoordinates);
        properties = loadSavedProperties();
        populateZones(properties);
        applyUrlState();
        buildPropertyMarkers(properties);
        buildActivityMarkers(activityData);
        if (activitiesVisible) map.addLayer(activityLayer);
        renderLegend();
        renderCardsInView();
        loading.hidden = true;
        mapShell.classList.add('is-ready');
    }).catch(error => {
        count.textContent = 'Map data unavailable';
        showStatus('The map data could not be loaded: ' + error.message, 'error');
    });
}());
