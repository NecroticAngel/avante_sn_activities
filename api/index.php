<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$config = avante_load_config();
$action = avante_request_action();
require_once __DIR__ . '/stock-booking.php';

try {
    switch ($action) {
        case 'config':
            avante_json([
                'ok' => true,
                'hasToken' => avante_has_token($config),
                'min_price' => (float) ($config['min_price'] ?? 0),
                'main_color' => $config['main_color'] ?? '#0dcdc2',
                'secondary_color' => $config['secondary_color'] ?? '#172d72',
                'form_bg_color' => $config['form_bg_color'] ?? '#ffffff',
                'button_color' => $config['button_color'] ?? '#0dcdc2',
                'hide_more_filters' => !empty($config['hide_more_filters']),
            ]);
            break;

        case 'options':
            $options = avante_get_search_options($config);
            avante_json([
                'ok' => true,
                'cached' => !empty($options['_cached']),
                'siteCode' => $options['siteCode'] ?? null,
                'unitSizes' => $options['unitSizes'] ?? [],
                'amenities' => $options['amenities'] ?? [],
                'experiences' => $options['experiences'] ?? [],
                'activities' => $options['activities'] ?? [],
            ]);
            break;

        case 'locations':
            $term = trim((string) ($_GET['term'] ?? ''));
            $options = avante_get_search_options($config);
            avante_json(avante_filter_locations($options['locations'] ?? [], $term));
            break;

        case 'search':
            avante_json(avante_handle_search($config));
            break;

        case 'additional_info':
            avante_json(avante_handle_additional_info($config));
            break;

        case 'accommodation_booking':
            avante_json(avante_handle_accommodation_booking($config));
            break;

        case 'activities':
            require_once __DIR__ . '/activities-store.php';
            avante_json([
                'ok' => true,
                'activities' => avante_activities_published(),
                'categories' => avante_activity_categories(),
            ]);
            break;

        case 'activity_booking':
            require_once __DIR__ . '/activities-store.php';
            avante_json(avante_handle_activity_booking($config));
            break;

        default:
            http_response_code(400);
            avante_json(['ok' => false, 'error' => 'Unknown action.']);
    }
} catch (Throwable $e) {
    http_response_code($e instanceof RuntimeException ? 400 : 500);
    avante_json(['ok' => false, 'error' => $e->getMessage()]);
}

function avante_request_action(): string
{
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    if ($action === '' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $body = avante_json_body();
        $action = (string) ($body['action'] ?? 'search');
    }
    return preg_replace('/[^a-z_]/', '', strtolower((string) $action));
}

function avante_json_body(): array
{
    static $body = null;
    if ($body !== null) {
        return $body;
    }
    $raw = file_get_contents('php://input') ?: '';
    $decoded = json_decode($raw, true);
    $body = is_array($decoded) ? $decoded : [];
    return $body;
}

function avante_json(array $payload): void
{
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function avante_load_config(): array
{
    $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config.php';
    if (!is_file($path)) {
        throw new RuntimeException('config.php is missing. Copy config.example.php to config.php.');
    }
    $config = require $path;
    if (!is_array($config)) {
        throw new RuntimeException('config.php must return an array.');
    }
    return $config;
}

function avante_has_token(array $config): bool
{
    return trim((string) ($config['auth_token'] ?? '')) !== '';
}

function avante_bearer_token(array $config): string
{
    $token = trim((string) ($config['auth_token'] ?? ''));
    if ($token === '') {
        throw new RuntimeException('Add your Stock Network bearer token to config.php to search.');
    }
    if (stripos($token, 'Bearer ') !== 0) {
        $token = 'Bearer ' . $token;
    }
    return $token;
}

function avante_api_base(array $config): string
{
    return rtrim((string) ($config['api_base'] ?? 'https://api.stocknetwork.co.za/api/2.0'), '/');
}

function avante_http_request(string $method, string $url, array $headers, ?string $body = null, int $timeout = 120): array
{
    $headerLines = [];
    foreach ($headers as $name => $value) {
        $headerLines[] = $name . ': ' . $value;
    }

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        $caBundle = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'cacert.pem';
        $curlOpts = [
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => $headerLines,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_FOLLOWLOCATION => true,
        ];
        if (is_file($caBundle)) {
            $curlOpts[CURLOPT_CAINFO] = $caBundle;
        }
        curl_setopt_array($ch, $curlOpts);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $responseBody = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if ($responseBody === false) {
            throw new RuntimeException('API request failed: ' . $error);
        }
        return ['status' => $status, 'body' => (string) $responseBody];
    }

    $context = stream_context_create([
        'http' => [
            'method' => strtoupper($method),
            'header' => implode("\r\n", $headerLines),
            'content' => $body ?? '',
            'timeout' => $timeout,
            'ignore_errors' => true,
        ],
    ]);
    $responseBody = @file_get_contents($url, false, $context);
    if ($responseBody === false) {
        $last = error_get_last();
        $detail = is_array($last) && !empty($last['message']) ? $last['message'] : 'unknown error';
        throw new RuntimeException('API request failed: ' . $detail);
    }
    $status = 0;
    if (!empty($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
        $status = (int) $m[1];
    }
    return ['status' => $status, 'body' => $responseBody];
}

function avante_cache_path(): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'search-options.json';
}

function avante_plugin_options_fallback(): ?array
{
    $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'avante-travel-search' . DIRECTORY_SEPARATOR . 'response.json';
    if (!is_file($path)) {
        return null;
    }
    $data = json_decode((string) file_get_contents($path), true);
    return is_array($data) ? $data : null;
}

function avante_read_cache(): ?array
{
    $path = avante_cache_path();
    if (!is_file($path)) {
        return null;
    }
    if (filemtime($path) < time() - 3600) {
        return null;
    }
    $data = json_decode((string) file_get_contents($path), true);
    return is_array($data) ? $data : null;
}

function avante_write_cache(array $data): void
{
    $dir = dirname(avante_cache_path());
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    file_put_contents(avante_cache_path(), json_encode($data));
}

function avante_get_search_options(array $config): array
{
    $cached = avante_read_cache();
    if ($cached) {
        $cached['_cached'] = true;
        return $cached;
    }

    if (avante_has_token($config)) {
        $url = avante_api_base($config) . '/search/options';
        $response = avante_http_request('GET', $url, avante_api_headers($config), null, 30);
        $data = json_decode($response['body'], true);
        if ($response['status'] === 200 && is_array($data) && !empty($data)) {
            avante_write_cache($data);
            $data['_cached'] = false;
            return $data;
        }
    }

    $fallback = avante_plugin_options_fallback();
    if ($fallback) {
        avante_write_cache($fallback);
        $fallback['_cached'] = true;
        return $fallback;
    }

    throw new RuntimeException('Could not load search options. Check the API token in config.php.');
}

function avante_api_headers(array $config): array
{
    return [
        'Host' => 'api.stocknetwork.co.za',
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
        'Accept-Charset' => 'utf-8',
        'Authorization' => avante_bearer_token($config),
    ];
}

function avante_location_field(array $location, string $field): string
{
    $candidates = [$field, strtolower($field), ucfirst(strtolower($field))];
    if (strtolower($field) === 'city2') {
        $candidates[] = 'City2';
    }
    if (strtolower($field) === 'state') {
        $candidates[] = 'State';
    }
    foreach (array_unique($candidates) as $key) {
        if (!empty($location[$key]) && is_string($location[$key])) {
            return $location[$key];
        }
    }
    return '';
}

function avante_is_accommodation(string $name): bool
{
    $haystack = strtolower($name);
    $needles = ['hotel', 'lodge', 'resort', 'guesthouse', 'bnb', 'cottage', 'villa', 'apartment', 'suite', 'inn', 'hostel', 'camp', 'estate'];
    foreach ($needles as $needle) {
        if (str_contains($haystack, $needle)) {
            return true;
        }
    }
    return false;
}

function avante_filter_locations(array $locations, string $term): array
{
    if ($term === '' || strlen($term) < 2) {
        return [];
    }

    $searchTerm = strtolower($term);
    $filtered = [];
    $seen = [];

    foreach ($locations as $location) {
        if (!is_array($location) || empty($location['name'])) {
            continue;
        }

        $name = (string) $location['name'];
        $searchable = strtolower(implode(' ', array_filter([
            avante_location_field($location, 'name'),
            avante_location_field($location, 'city'),
            avante_location_field($location, 'city2'),
            avante_location_field($location, 'state'),
            avante_location_field($location, 'area'),
            avante_location_field($location, 'region'),
            avante_location_field($location, 'country'),
        ])));

        if (!str_contains($searchable, $searchTerm)) {
            continue;
        }

        if (avante_is_accommodation($name)) {
            $display = $name;
            $searchText = $name;
            $type = 'accommodation';
            $uniqueKey = 'accommodation_' . strtolower($display);
        } else {
            $city = avante_location_field($location, 'city');
            $city2 = avante_location_field($location, 'city2');
            $state = avante_location_field($location, 'state');
            $area = avante_location_field($location, 'area');
            $region = avante_location_field($location, 'region');
            $country = avante_location_field($location, 'country');
            $display = $city ?: ($city2 ?: ($state ?: ($area ?: ($region ?: $country))));
            $searchText = $display;
            $type = 'location';
            $uniqueKey = 'location_' . strtolower($display);
        }

        if (isset($seen[$uniqueKey])) {
            continue;
        }
        $seen[$uniqueKey] = true;

        $locationParts = array_filter([
            avante_location_field($location, 'city'),
            avante_location_field($location, 'city2'),
            avante_location_field($location, 'state'),
            avante_location_field($location, 'region'),
            avante_location_field($location, 'country'),
        ]);

        $filtered[] = [
            'value' => $searchText,
            'label' => $display,
            'location' => implode(', ', $locationParts),
            'type' => $type,
        ];
    }

    usort($filtered, static function (array $a, array $b): int {
        if ($a['type'] !== $b['type']) {
            return $a['type'] === 'location' ? -1 : 1;
        }
        return strcmp((string) $a['label'], (string) $b['label']);
    });

    return $filtered;
}

function avante_handle_search(array $config): array
{
    $input = $_POST;
    if (empty($input['checkin_date'])) {
        $input = array_merge($input, avante_json_body());
    }

    $checkin = trim((string) ($input['checkin_date'] ?? ''));
    $checkout = trim((string) ($input['checkout_date'] ?? ''));
    $destination = trim((string) ($input['destination'] ?? ''));
    $unitSize = trim((string) ($input['unit_size'] ?? ''));

    if ($checkin === '' || $checkout === '' || $destination === '') {
        throw new RuntimeException('Check-in, check-out, and destination are required.');
    }

    $amenities = [];
    foreach (['amenities', 'experiences', 'activities'] as $filterName) {
        $values = $input[$filterName] ?? [];
        if (!is_array($values)) {
            continue;
        }
        foreach ($values as $value) {
            if ($value === '' || $value === null) {
                continue;
            }
            $amenities[] = ['amenityTypeId' => (string) $value];
        }
    }

    $payload = [
        'CheckInDate' => $checkin . 'T00:00:00+02:00',
        'CheckOutDate' => $checkout . 'T00:00:00+02:00',
        'Region' => [
            'RegionID' => null,
            'Name' => '',
            'Country' => 'South Africa',
            'CountryID' => null,
            'IsRCIRegion' => false,
            'RegionCode' => null,
        ],
        'Amenities' => $amenities,
        'unitSizes' => [
            ['unitSizeId' => $unitSize],
        ],
        'Geocoordinates' => null,
        'Pricing' => [
            'MinPrice' => (float) ($config['min_price'] ?? 0),
            'MaxPrice' => 50000.0,
        ],
        'searchText' => $destination,
        'ResortID' => null,
        'GroupStockToMatchDates' => true,
        'ExtendDatesIfNoMatchFound' => true,
        'IgnoreLocationData' => false,
    ];

    $url = avante_api_base($config) . '/search?limit=30&offset=1';
    $response = avante_http_request(
        'POST',
        $url,
        avante_api_headers($config),
        json_encode($payload),
        120
    );

    $data = json_decode($response['body'], true);
    if ($response['status'] !== 200) {
        $message = is_array($data) && !empty($data['message']) ? $data['message'] : $response['body'];
        throw new RuntimeException('Stock Network error (HTTP ' . $response['status'] . '): ' . substr((string) $message, 0, 300));
    }

    if (!is_array($data)) {
        throw new RuntimeException('Invalid search response from Stock Network.');
    }

    return [
        'ok' => true,
        'stockAvailability' => $data['stockAvailability'] ?? [],
        'checkin_date' => $checkin,
        'checkout_date' => $checkout,
    ];
}
