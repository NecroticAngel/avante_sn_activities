<?php
$pageTitle = 'Property map — Avante Travel';
$headerTitle = 'Explore our properties';
$activeNav = 'map';
$extraCss = [
    'assets/vendor/leaflet/leaflet.css',
    'assets/vendor/leaflet.markercluster/MarkerCluster.css',
    'assets/vendor/leaflet.markercluster/MarkerCluster.Default.css',
    'assets/css/property-map.css',
];
require __DIR__ . '/includes/header.php';
?>
        <p class="avante-lede">Browse Avante properties across South Africa. Search by property, town or area, or narrow the map to an affiliate zone.</p>

        <section class="property-map-shell" aria-label="Avante property map">
            <div id="property-map-regions" class="property-map-regions" aria-label="Browse by region"></div>

            <div class="property-map-toolbar">
                <label class="property-map-search">
                    <span class="visually-hidden">Search properties</span>
                    <input id="property-map-search" type="search" placeholder="Search property, town or area…" autocomplete="off">
                    <span id="property-search-results" class="property-map-results" role="listbox"></span>
                </label>

                <label class="property-map-search">
                    <span class="visually-hidden">Search activities</span>
                    <input id="property-activity-search" type="search" placeholder="Search activities…" autocomplete="off">
                    <span id="activity-search-results" class="property-map-results" role="listbox"></span>
                </label>

                <label class="property-map-zone">
                    <span class="visually-hidden">Filter by affiliate zone</span>
                    <select id="property-map-zone">
                        <option value="">All zones</option>
                    </select>
                </label>

                <span id="property-map-count" class="property-map-count" aria-live="polite">Loading properties…</span>
                <div class="property-map-actions">
                    <button id="property-map-nearby" class="property-map-button" type="button">Near me</button>
                    <button id="property-map-fullscreen" class="property-map-button property-map-button-secondary" type="button" aria-pressed="false">Full screen</button>
                    <details class="property-map-more">
                        <summary class="property-map-button property-map-button-secondary">More</summary>
                        <div class="property-map-more-menu">
                            <label for="property-map-marker-mode">Colour markers by</label>
                            <select id="property-map-marker-mode">
                                <option value="link">Link availability</option>
                                <option value="region">Region</option>
                                <option value="rating">Rating</option>
                            </select>
                            <button id="property-map-update" type="button">Import property CSV</button>
                            <button id="property-map-reset" type="button" hidden>Reset imported data</button>
                        </div>
                    </details>
                </div>
                <input id="property-map-file" type="file" accept=".csv,.tsv,text/csv" hidden>
            </div>

            <div id="property-map-status" class="property-map-status" role="status" aria-live="polite" hidden></div>
            <div class="property-map-explorer property-map-layout">
                <aside class="property-map-sidebar" aria-label="Properties in view">
                    <div class="property-map-sidebar-heading">
                        <div>
                            <h2>Properties in view</h2>
                            <p id="property-map-view-count">Move the map to explore</p>
                        </div>
                        <button id="property-map-refresh-area" type="button">Search this area</button>
                    </div>
                    <div id="property-map-cards" class="property-map-cards" aria-live="polite"></div>
                </aside>
                <div class="property-map-stage">
                    <div id="property-map" class="property-map-canvas" aria-label="Interactive map of Avante properties"></div>
                    <div id="property-map-loading" class="property-map-loading" role="status">
                        <span class="property-map-spinner" aria-hidden="true"></span>
                        <span>Preparing the map…</span>
                    </div>
                    <div id="property-map-legend" class="property-map-legend" aria-label="Map legend"></div>
                </div>
            </div>
        </section>

        <noscript><p class="property-map-status is-error">JavaScript is required to display the property map.</p></noscript>
    </div>

    <script>
        window.AVANTE_MAP = {
            propertiesUrl: 'data/properties.json',
            activitiesUrl: 'data/map-activities.json'
        };
    </script>
    <script src="assets/vendor/leaflet/leaflet.js"></script>
    <script src="assets/vendor/leaflet.markercluster/leaflet.markercluster.js"></script>
    <script src="assets/vendor/papaparse/papaparse.min.js"></script>
    <script src="assets/js/property-map.js"></script>
</body>
</html>
