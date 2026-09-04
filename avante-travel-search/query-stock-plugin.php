<?php
/**
 * Plugin name: Avante Travel Search
 * Plugin URI: https://d-zine.org.za
 * Description: Get booking information from Avante
 * Author: Jo Whitehouse
 * Author URI: https://d-zine.org.za
 * Version: 01122025
 * License: GPL2 or later.
 */

// If this file is accessed directly, abort!!!
defined('ABSPATH') or die('Unauthorized Access');
require_once plugin_dir_path(__FILE__) . 'admin-interface.php';

add_action('wp_enqueue_scripts', 'avante_travel_enqueue_assets');
function avante_travel_enqueue_assets()
{
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_script('jquery-ui-autocomplete');
    wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
    wp_enqueue_style('avante-travel-styles', plugins_url('/base-stock-styles.css', __FILE__));
    wp_enqueue_style('avante-travel-dynamic-styles', plugins_url('/dynamic-styles.css', __FILE__), array('avante-travel-styles'));
    wp_enqueue_script('custom-datepicker', plugins_url('/custom-datepicker.js', __FILE__), array('jquery', 'jquery-ui-datepicker'), null, true);
    wp_enqueue_script('avante-travel-autocomplete', plugins_url('/autocomplete.js', __FILE__), array('jquery', 'jquery-ui-autocomplete'), null, true);
    wp_enqueue_script('avante-travel-script', plugins_url('/avante-travel.js', __FILE__), array('jquery', 'avante-travel-autocomplete'), null, true);
    // Get plugin version from header
    $plugin_data = get_file_data(__FILE__, array('Version' => 'Version'), 'plugin');
    $plugin_version = !empty($plugin_data['Version']) ? $plugin_data['Version'] : '01122025';

    wp_localize_script('avante-travel-autocomplete', 'avante_travel_autocomplete_data', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
    ));
    
    wp_localize_script('avante-travel-script', 'avante_travel_data', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'auth_token' => get_auth_token(),
        'min_price' => floatval(get_option('avante_travel_min_price', 0.0)),
        'booking_mode' => get_option('avante_travel_booking_mode', 'email'),
        'site_code' => get_option('avante_travel_site_code', 'Not fetched yet'),
        'version' => $plugin_version
    ));
    // Expose search options so the front-end can render selected names in the search parameters
    $avante_search_options = get_option('avante_travel_search_options', array());
    if (!is_array($avante_search_options)) {
        $avante_search_options = array();
    }
    wp_localize_script('avante-travel-script', 'avante_travel_search_options', $avante_search_options);
    $main_color = get_option('avante_travel_main_color', '#0dcdc2');
    $secondary_color = get_option('avante_travel_secondary_color', '#172d72');
    $form_bg_color = get_option('avante_travel_form_bg_color', '#ffffff');
    $button_color = get_option('avante_travel_button_color', '#0dcdc2');
    $hide_more_filters = get_option('avante_travel_hide_more_filters', '0');
    // Only inject the CSS variables - all styles are now in separate CSS files
    $custom_css = "
        :root {
            --avante-main-color: {$main_color};
            --avante-secondary-color: {$secondary_color};
            --avante-form-bg-color: {$form_bg_color};
            --avante-button-color: {$button_color};
        }
    ";
    // Add CSS to hide more filters button if enabled
    if ($hide_more_filters === '1') {
        $custom_css .= "
        a#more-options-toggle {
            display: none;
        }
        ";
    }
    wp_add_inline_style('avante-travel-styles', $custom_css);
}
add_shortcode('query_stock_form', 'render_query_stock_form');
add_shortcode('stock_voucher_form', 'render_stock_voucher_form');
add_shortcode('auto_search_destination', 'render_auto_search_destination');

// Helper function for rendering filter sections
if (!function_exists('render_filter_section')) {
    function render_filter_section($title, $items, $filter_name) {
        if (!empty($items)) {
            echo '<div class="filter-section">';
            echo '<h4>' . esc_html($title) . '</h4>';
            echo '<div class="filter-grid">';
            // Sort items alphabetically by name
            usort($items, function($a, $b) {
                return strcmp($a['name'], $b['name']);
            });
            foreach ($items as $item) {
                if (empty($item['name'])) continue;
                echo '<div class="filter-item">';
                echo '<label><input type="checkbox" name="' . esc_attr($filter_name) . '[]" value="' . esc_attr($item['amenityTypeId']) . '"> ' . esc_html($item['name']) . '</label>';
                echo '</div>';
            }
            echo '</div>';
            echo '</div>';
        }
    }
}
function render_query_stock_form($atts = [])
{
    // Support custom results container via shortcode attribute
    $atts = shortcode_atts(array(
        'results_target' => '', // CSS selector for an external results container
        'site' => 'primary' // Site identifier: 'primary' (default) or 'secondary'
    ), $atts, 'query_stock_form');
    $results_target = sanitize_text_field($atts['results_target']);
    $site = sanitize_text_field($atts['site']);
    ob_start();
?>
    <div id="avante-travel-search-wrapper" <?php if (!empty($results_target)) { echo 'data-results-target="' . esc_attr($results_target) . '"'; } ?>>
        <div class="horizontal-form-container">
            <form id="query-stock-form" method="post" class="horizontal-form">
                <input type="hidden" name="site" value="<?php echo esc_attr($site); ?>">
                <div class="input-container date-range">
                    <svg aria-hidden="true" class="e-font-icon-svg e-far-calendar-alt" viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg">
                        <path d="M148 288h-40c-6.6 0-12-5.4-12-12v-40c0-6.6 5.4-12 12-12h40c6.6 0 12 5.4 12 12v40c0 6.6-5.4 12-12 12zm108-12v-40c0-6.6-5.4-12-12-12h-40c-6.6 0-12 5.4-12 12v40c0 6.6 5.4 12 12 12h40c6.6 0 12-5.4 12-12zm96 0v-40c0-6.6-5.4-12-12-12h-40c-6.6 0-12 5.4-12 12v40c0 6.6 5.4 12 12 12h40c6.6 0 12-5.4 12-12zm-96 96v-40c0-6.6-5.4-12-12-12h-40c-6.6 0-12 5.4-12 12v40c0 6.6 5.4 12 12 12h40c6.6 0 12-5.4 12-12zm-96 0v-40c0-6.6-5.4-12-12-12h-40c-6.6 0-12 5.4-12 12v40c0 6.6 5.4 12 12 12h40c6.6 0 12-5.4 12-12zm192 0v-40c0-6.6-5.4-12-12-12h-40c-6.6 0-12 5.4-12 12v40c0 6.6 5.4 12 12 12h40c6.6 0 12-5.4 12-12zm96-260v352c0 26.5-21.5 48-48 48H48c-26.5 0-48-21.5-48-48V112c0-26.5 21.5-48 48-48h48V12c0-6.6 5.4-12 12-12h40c6.6 0 12 5.4 12 12v52h128V12c0-6.6 5.4-12 12-12h40c6.6 0 12 5.4 12 12v52h48c26.5 0 48 21.5 48 48zm-48 346V160H48v298c0 3.3 2.7 6 6 6h340c3.3 0 6-2.7 6-6z"></path>
                    </svg>
                    <input type="text" id="daterange" name="daterange" placeholder="Check-In - Check-Out" class="daterange-input" required autocomplete="off">
                    <input type="hidden" id="checkin_date" name="checkin_date">
                    <input type="hidden" id="checkout_date" name="checkout_date">
                </div>
                <div class="input-container destination">
                    <svg aria-hidden="true" class="e-font-icon-svg e-fas-map-marker-alt" viewBox="0 0 384 512" xmlns="http://www.w3.org/2000/svg">
                        <path d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0zM192 272c44.183 0 80-35.817 80-80s-35.817-80-80-80-80 35.817-80 80 35.817 80 80 80z"></path>
                    </svg>
                    <input type="text" id="destination" name="destination" placeholder="Destination" class="large" required>
                    <button type="button" id="clear-destination" class="clear-button">x</button>
                </div>
                <div class="input-container2">
                    <select name="unit_size" required>
                        <?php
                        $search_options = get_option('avante_travel_search_options');
                        if (!empty($search_options['unitSizes'])) {
                            // Find the "All" option to select it by default
                            $selected_unit_id = '';
                            foreach ($search_options['unitSizes'] as $size) {
                                if (isset($size['description']) && strpos(strtolower($size['description']), 'all unit sizes') !== false) {
                                    $selected_unit_id = $size['unitSizeId'];
                                    break;
                                }
                            }

                            foreach ($search_options['unitSizes'] as $size) {
                                $selected = ($size['unitSizeId'] === $selected_unit_id) ? 'selected' : '';
                                echo '<option value="' . esc_attr($size['unitSizeId']) . '" ' . $selected . '>' . esc_html($size['name']) . '</option>';
                            }
                        } else {
                            // Fallback to hardcoded values if API data is not available
                            echo '<option value="all" selected>All Unit Sizes</option>';
                            echo '<option value="d3bd7e8f-0276-4a90-8f1b-818662f0fcf1">2 Sleeper</option>';
                            echo '<option value="7ea1f601-0932-4c1e-9917-6311b132a0c1">1 Sleeper</option>';
                            echo '<option value="f6f68ead-b0f9-42fe-b367-b0f802a8fc23">3 Sleeper</option>';
                            echo '<option value="639b55c9-f056-4bca-bf2b-d68ba3459490">4 Sleeper</option>';
                            echo '<option value="3a3da04b-8342-423e-89c0-6b37ee5fdf2b">5 Sleeper</option>';
                            echo '<option value="aa216d9c-a4d5-45bd-b7a7-836c20e3b188">6 Sleeper</option>';
                            echo '<option value="6d96cbc7-45a7-42f8-ba34-b1a4ca1ecf73">7 Sleeper</option>';
                            echo '<option value="79e9eb0a-1d67-4201-91eb-59ca5347f355">8 Sleeper</option>';
                            echo '<option value="988868c3-be4d-4625-97c0-c484aa7e39ae">9 Sleeper</option>';
                            echo '<option value="eb95dcbc-98fa-4870-b48f-54102d89e3fe">10 Sleeper</option>';
                            echo '<option value="91acab95-5767-4807-a4aa-d5ff80a6860c">11 Sleeper</option>';
                            echo '<option value="d7b1e301-3849-4fb1-aa21-fdb29cfbe1e0">12+ Sleeper</option>';
                        }
                        ?>
                    </select>
                    <svg aria-hidden="true" class="e-font-icon-svg e-fas-user-plus" viewBox="0 0 640 512" xmlns="http://www.w3.org/2000/svg">
                        <path d="M624 208h-64v-64c0-8.8-7.2-16-16-16h-32c-8.8 0-16 7.2-16 16v64h-64c-8.8 0-16 7.2-16 16v32c0 8.8 7.2 16 16 16h64v64c0 8.8 7.2 16 16 16h32c8.8 0 16-7.2 16-16v-64h64c8.8 0 16-7.2 16-16v-32c0-8.8-7.2-16-16-16zm-400 48c70.7 0 128-57.3 128-128S294.7 0 224 0 96 57.3 96 128s57.3 128 128 128zm89.6 32h-16.7c-22.2 10.2-46.9 16-72.9 16s-50.6-5.8-72.9-16h-16.7C60.2 288 0 348.2 0 422.4V464c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48v-41.6c0-74.2-60.2-134.4-134.4-134.4z"></path>
                    </svg>
                    
                </div>
                <div class="submit-button-container">
                        <a href="#" id="more-options-toggle">More Filters</a>
                        <input type="submit" name="submit_query" value="Search">
                    </div>
                <div class="more-options-container" id="more-options-container" style="display: none;">
                    <?php
                    $search_options = get_option('avante_travel_search_options');
                    if (!empty($search_options)) {
                        render_filter_section('Amenities', $search_options['amenities'] ?? [], 'amenities');
                        render_filter_section('Experiences', $search_options['experiences'] ?? [], 'experiences');
                        render_filter_section('Activities', $search_options['activities'] ?? [], 'activities');
                    }
                    ?>
                </div>

            </form>
        </div>
        <div id="search-parameters" class="debug-info" style="text-align: right;"></div>
        
        <!-- Modal for iframe content (resort info, unit info, etc.) -->
        <div id="iframeModal" class="modal-overlay">
            <div class="modal-content iframe-modal">
                <span class="modal-close">&times;</span>
                <div class="iframe-container">
                    <h3 id="iframeModalTitle">Information</h3>
                    <iframe id="iframeContent" src="" frameborder="0"></iframe>
                </div>
            </div>
        </div>
        
        <div id="query-stock-results">
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_query'])) {
                handle_query_stock_form_submission();
            }
            ?>
        </div>
        <!-- Modal for booking form -->
        <div id="bookNowModal" class="modal-overlay">
            <div class="modal-content booking-form-modal">
                <span class="modal-close">&times;</span>
                <div class="booking-form-container">
                    <h3>Book Your Accommodation</h3>
                    
                    <!-- Overview section showing what they're enquiring about -->
                    <div id="booking-overview" class="booking-overview" style="display: none;">
                        <h4>Accommodation Details</h4>
                        <div class="overview-content">
                            <div class="overview-item">
                                <strong>Resort:</strong> <span id="overview-resort">-</span>
                            </div>
                            <div class="overview-item">
                                <strong>Unit Type:</strong> <span id="overview-unit">-</span>
                            </div>
                            <div class="overview-item">
                                <strong>Price:</strong> <span id="overview-price">-</span>
                            </div>
                            <div class="overview-item">
                                <strong>Check-in:</strong> <span id="overview-checkin">-</span>
                            </div>
                            <div class="overview-item">
                                <strong>Check-out:</strong> <span id="overview-checkout">-</span>
                            </div>
                        </div>
                    </div>
                    
                    <form id="booking-form" class="booking-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="booking_full_name">Full Name *</label>
                                <input type="text" id="booking_full_name" name="full_name" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="booking_email">Email Address *</label>
                                <input type="email" id="booking_email" name="email" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="booking_phone">Phone Number</label>
                                <input type="tel" id="booking_phone" name="phone">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="booking_adults">Number of occupants older than 17*</label>
                                <select id="booking_adults" name="adults" required>
                                    <option value="">Select</option>
                                    <option value="1">1</option>
                                    <option value="2" selected>2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                    <option value="5">5</option>
                                    <option value="6">6</option>
                                    <option value="7">7</option>
                                    <option value="8">8</option>
                                    <option value="9">9</option>
                                    <option value="10">10</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="booking_young_children">Number of occupants aged between 0 and 6*</label>
                                <select id="booking_young_children" name="young_children" required>
                                    <option value="">Select</option>
                                    <option value="0" selected>0</option>
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                    <option value="5">5</option>
                                    <option value="6">6</option>
                                    <option value="7">7</option>
                                    <option value="8">8</option>
                                    <option value="9">9</option>
                                    <option value="10">10</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="booking_older_children">Number of occupants aged between 6 and 17</label>
                                <select id="booking_older_children" name="older_children">
                                    <option value="">Select</option>
                                    <option value="0" selected>0</option>
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                    <option value="5">5</option>
                                    <option value="6">6</option>
                                    <option value="7">7</option>
                                    <option value="8">8</option>
                                    <option value="9">9</option>
                                    <option value="10">10</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group full-width">
                                <label for="booking_message">Additional Message (Optional)</label>
                                <textarea id="booking_message" name="message" rows="3" placeholder="Any special requests or questions..."></textarea>
                            </div>
                        </div>
                        <div class="form-row" style="display: none;">
                            <div class="form-group full-width">
                                <label><strong>Booking URL:</strong></label>
                                <div id="booking-url-display" style="background: #f5f5f5; padding: 8px; border-radius: 4px; word-break: break-all; font-family: monospace; font-size: 12px; color: #666; margin-top: 5px;">
                                    URL will appear here when you select a property
                                </div>
                            </div>
                        </div>
                        <div style="height:10px; overflow:hidden;">
                        <input type="hidden" id="booking_url" name="booking_url" value="">
                        <input type="hidden" id="resort_name" name="resort_name" value="">
                        <input type="hidden" id="unit_name" name="unit_name" value="">
                        <input type="hidden" id="unit_price" name="unit_price" value="">
                        <input type="hidden" id="checkin_date" name="checkin_date" value="">
                        <input type="hidden" id="checkout_date" name="checkout_date" value="">
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn-cancel">Cancel</button>
                            <button type="submit" class="btn-submit">Send Booking Request</button>
                        </div>
                    </form>
                    <div id="booking-form-message" class="form-message" style="display: none;"></div>
                </div>
            </div>
        </div>
    </div>
<?php
    return ob_get_clean();
}

function handle_query_stock_form_submission()
{
    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    error_log('AJAX request received: ' . print_r($_POST, true));

    try {
        $checkin_date = sanitize_text_field($_POST['checkin_date']);
        $checkout_date = sanitize_text_field($_POST['checkout_date']);
        $destination = sanitize_text_field($_POST['destination']);
        $unit_size = sanitize_text_field($_POST['unit_size']);

        // Sanitize and combine all amenities, experiences, and activities
        $amenities = [];
        $filters = ['amenities', 'experiences', 'activities'];
        foreach ($filters as $filter_name) {
            if (isset($_POST[$filter_name]) && is_array($_POST[$filter_name])) {
                foreach ($_POST[$filter_name] as $value) {
                    $amenities[] = ['amenityTypeId' => sanitize_text_field($value)];
                }
            }
        }

        $site_id = isset($_POST['site_id']) ? sanitize_text_field($_POST['site_id']) : '36';

        $api_url = 'http://api.stocknetwork.co.za/api/2.0/search?limit=30&offset=1';
        $request_body = json_encode([
            'CheckInDate' => $checkin_date . 'T00:00:00+02:00',
            'CheckOutDate' => $checkout_date . 'T00:00:00+02:00',
            'Region' => [
                'RegionID' => null,
                'Name' => '',
                'Country' => 'South Africa',
                'CountryID' => null,
                'IsRCIRegion' => false,
                'RegionCode' => null
            ],
            'Amenities' => $amenities,
            'unitSizes' => [
                [
                    'unitSizeId' => $unit_size
                ]
            ],
            'Geocoordinates' => null,
            'Pricing' => [
                'MinPrice' => floatval(get_option('avante_travel_min_price', 0.0)),
                'MaxPrice' => 50000.0
            ],
            'searchText' => $destination,
            'ResortID' => null,
            'GroupStockToMatchDates' => true,
            'ExtendDatesIfNoMatchFound' => true,
            'IgnoreLocationData' => false
        ]);

        // Get the site identifier from form submission
        $site = isset($_POST['site']) ? sanitize_text_field($_POST['site']) : 'primary';
        $auth_token = get_auth_token($site);

        $headers = [
            'Host' => 'api.stocknetwork.co.za',
            'Content-type' => 'application/json',
            'Accept' => 'application/json',
            'Accept-Charset' => 'utf-8',
            'Authorization' => $auth_token
        ];

        error_log('API Request URL: ' . $api_url);
        error_log('API Request Body: ' . $request_body);
        error_log('Using token: ' . substr($auth_token, 0, 20) . '...');

        $response = wp_remote_post($api_url, [
            'body' => $request_body,
            'headers' => $headers,
            'timeout' => 120
        ]);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            error_log('API Request Error: ' . $error_message);
            echo '<div class="error-message-api">An error occurred fetching your properties, please try again. Error: ' . esc_html($error_message) . '</div>';
        } else {
            $body = wp_remote_retrieve_body($response);
            $response_code = wp_remote_retrieve_response_code($response);
            $data = json_decode($body, true);

            error_log('API Response Code: ' . $response_code);
            error_log('API Response Body (partial): ' . substr($body, 0, 500) . '...');

            if ($response_code !== 200) {
                echo '<div class="error-message-api">Error from API (code ' . esc_html($response_code) . '): ' . esc_html($body) . '</div>';
            } else if (empty($data['stockAvailability'])) {
                echo '<div class="no_results">It seems there are no available accommodations that fit your search. How about trying different dates or locations?</div>';
            } else {
                display_search_results($data, $unit_size);
            }
        }
    } catch (Exception $e) {
        error_log('Exception in AJAX handler: ' . $e->getMessage());
        error_log('Exception trace: ' . $e->getTraceAsString());
        echo '<div class="error-message">An error occurred processing your request: ' . esc_html($e->getMessage()) . '</div>';
    }
    if (defined('DOING_AJAX') && DOING_AJAX) {
        wp_die();
    }
}

function display_search_results($data, $unit_size = '')
{
    try {
        error_log('Data received in display_search_results: ' . print_r(array_keys($data), true));

        if (empty($data['stockAvailability'])) {
            echo '<div class="no_results">It seems there are no available accommodations that fit your search. How about trying different dates or locations?</div>';
            return;
        }
        foreach ($data['stockAvailability'] as $index => $resort) {
            $min_rate_for_resort = PHP_INT_MAX;
            $has_valid_rate = false;

            if (!empty($resort['unitTypes'])) {
                foreach ($resort['unitTypes'] as $unit) {
                    if (isset($unit['rate']['rate']) && is_numeric($unit['rate']['rate'])) {
                        $current_rate = floatval($unit['rate']['rate']);
                        if ($current_rate < $min_rate_for_resort) {
                            $min_rate_for_resort = $current_rate;
                            $has_valid_rate = true;
                        }
                    }
                }
            }
            $data['stockAvailability'][$index]['min_rate_for_sorting'] = $has_valid_rate ? $min_rate_for_resort : PHP_INT_MAX;
        }
        usort($data['stockAvailability'], function ($a, $b) {
            // First, sort by isFeaturedResort (featured resorts come first)
            $featuredA = isset($a['isFeaturedResort']) ? $a['isFeaturedResort'] : false;
            $featuredB = isset($b['isFeaturedResort']) ? $b['isFeaturedResort'] : false;
            
            if ($featuredA && !$featuredB) {
                return -1; // A is featured, B is not - A comes first
            } elseif (!$featuredA && $featuredB) {
                return 1; // B is featured, A is not - B comes first
            }
            
            // If both are featured or both are not featured, sort by price
            $rateA = $a['min_rate_for_sorting'];
            $rateB = $b['min_rate_for_sorting'];

            if ($rateA == $rateB) {
                return 0;
            }
            return ($rateA < $rateB) ? -1 : 1;
        });
        error_log('Number of properties found: ' . count($data['stockAvailability']));

        echo '<div class="stock_wrapper">';

        foreach ($data['stockAvailability'] as $index => $resort) {
            try {
                if (!isset($resort['name']) || !isset($resort['unitTypes']) || empty($resort['unitTypes'])) {
                    error_log('Invalid resort data at index ' . $index . ': Missing name or unitTypes');
                    continue;
                }
                if (!empty($resort['unitTypes'])) {
                    usort($resort['unitTypes'], function ($a, $b) {
                        $rateA = isset($a['rate']['rate']) && is_numeric($a['rate']['rate']) ? floatval($a['rate']['rate']) : PHP_INT_MAX;
                        $rateB = isset($b['rate']['rate']) && is_numeric($b['rate']['rate']) ? floatval($b['rate']['rate']) : PHP_INT_MAX;

                        if ($rateA == $rateB) {
                            return 0;
                        }
                        return ($rateA < $rateB) ? -1 : 1;
                    });
                }
                $resortName = $resort['name'];
                $location = $resort['location'] ?? 'Location not available';
                $description = $resort['description'] ?? 'No description available';
                $imageUrl = '';
                if (isset($resort['imageLinks']) && !empty($resort['imageLinks'])) {
                    $imageUrl = $resort['imageLinks'][0]['url'] ?? '';
                }
                echo '<div class="stock_item">';
                
                // Add featured banner if this is a featured resort
                if (isset($resort['isFeaturedResort']) && $resort['isFeaturedResort'] === true) {
                    echo '<div class="featured-banner">Featured</div>';
                }
                
                echo '<div class="stock_item_header">';
                if (isset($resort['imageLinks']) && !empty($resort['imageLinks'])) {
                    echo '<div class="sn_imagebar">';
                    echo '<section class="slideshow">';
                    echo '<div class="slideshow-container">';
                    $counter = 0;
                    foreach ($resort['imageLinks'] as $imagelink) {
                        if ($counter < 4) {
                            echo '<div class="res_img"><img src="' . esc_url($imagelink['url']) . '" alt="' . esc_attr($resortName) . ' image ' . ($counter + 1) . '"/></div>';
                            $counter++;
                        } else {
                            break;
                        }
                    }
                    echo '</div>';
                    echo '</section>';
                    echo '</div><hr>';
                }

                echo '<div class="stock_details">';
                echo '<h3 class="stock_title">';
                if (!empty($resort['resortInfoUrl'])) {
                    echo '<a href="#" data-url="' . esc_url($resort['resortInfoUrl']) . '" title="More information about ' . esc_attr($resortName) . '" class="resort-info-link open-modal-button">';
                    echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="16" height="16">';
                    echo '<path fill="navy" d="M256 0C114.6 0 0 114.6 0 256s114.6 256 256 256s256-114.6 256-256S397.4 0 256 0z"/>';
                    echo '<path fill="white" d="M232 152c0-13.3 10.7-24 24-24s24 10.7 24 24v24c0 13.3-10.7 24-24 24s-24-10.7-24-24v-24zm0 96h48c13.3 0 24 10.7 24 24v88c0 13.3-10.7 24-24 24h-48c13.3 0-24-10.7-24-24V272c0-13.3 10.7-24 24-24z"/>';
                    echo '</svg>';
                    echo '</a>';
                }
                echo esc_html($resortName) . '</h3>';
                echo '<div class="stock_location">';
                echo '  <svg class="location-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" width="11" height="14"><path fill="currentColor" d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0zM192 272c44.183 0 80-35.817 80-80s-35.817-80-80-80-80 35.817-80 80 35.817 80 80 80z"/></svg>';
                echo '  ' . esc_html($location) . '</div>';
                echo '<div class="stock_description">' . $description . '</div>';
                echo '</div></div>';
                echo '<div class="stock_availability">';
                $unit_count = count($resort['unitTypes']);
                $units_html = '';
                $hidden_units_html = '';

                foreach ($resort['unitTypes'] as $unitIndex => $unit) {
                    ob_start();
                    try {
                        $unitName = $unit['name'] ?? 'Room';
                        $unitDescription = $unit['description'] ?? 'No description available';
                        $sleeper = $unit['sleeper'] ?? 'N/A';
                        $unitsAvailable = isset($unit['unitsAvailable']) ? intval($unit['unitsAvailable']) : 0;
                        $rate = isset($unit['rate']['rate']) ? $unit['rate']['rate'] : 'Price on request';
                        $normalRate = isset($unit['rate']['normalRate']) && $unit['rate']['normalRate'] > 0 ?
                        $unit['rate']['normalRate'] : $rate;
                        $nights = $unit['nights'] ?? '';
                        $checkInDate = isset($unit['checkInDate']) ? (new DateTime($unit['checkInDate']))->format('M d') : '';
                        $checkOutDate = isset($unit['checkOutDate']) ? (new DateTime($unit['checkOutDate']))->format('M d') : '';
                        echo '<div class="sn_list_item_details">';
                        echo '<div class="sn_room_size">';
                        if (!empty($unit['url'])) {
                            echo '<a href="#" data-url="' . esc_url($unit['url']) . '" title="More information about ' . esc_attr($unitName) . '" class="unit-info-link open-modal-button">';
                            echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="14" height="14">';
                            echo '<path fill="orange" d="M256 0C114.6 0 0 114.6 0 256s114.6 256 256 256s256-114.6 256-256S397.4 0 256 0z"/>';
                            echo '<path fill="white" d="M232 152c0-13.3 10.7-24 24-24s24 10.7 24 24v24c0 13.3-10.7 24-24 24s-24-10.7-24-24v-24zm0 96h48c13.3 0 24 10.7 24 24v88c0 13.3-10.7 24-24 24h-48c-13.3 0-24-10.7-24-24V272c0-13.3 10.7-24 24-24z"/>';
                            echo '</svg>';
                            echo '</a>';
                        }
                        echo '<span class="sn_roomtype">' . esc_html($unitName) . '</span>';
                        echo '<span class="sn_roomsize"> Sleeps: ' . esc_html($sleeper) . '</span>';
                        if ($unitsAvailable > 0) {
                            echo '<span class="sn_units_available">Units Available: ' . esc_html($unitsAvailable) . '</span>';
                        }
                        echo '</div>';
                        echo '<div class="sn_room_size">';
                        echo '<span class="sn_roomdesc">' . ($unitDescription) . '</span>';
                        echo '</div>';
                        echo '<div class="sn_item_details">';

                        if (!empty($checkInDate) && !empty($checkOutDate)) {
                            echo '<div class="sn_dates"><span>' . $checkInDate . '</span><span> - ' . $checkOutDate . '</span></div>';
                        }
                        if (!empty($nights)) {
                            echo '<div class="sn_nights">Nights: ' . esc_html($nights) . '</div>';
                        }
                        echo '<div class="sn_price">';
                        if ($normalRate > $rate) {
                            echo '<span class="strike">R ' . esc_html($normalRate) . '</span> ';
                        }
                        echo 'R ' . esc_html($rate) . '</div>';
                        echo '</div>';
                        echo '<div class="sn_cashback">';
                                                    if (isset($resort['referenceUrl'])) {
                                // Get the dates from the search form
                                $checkin_date = isset($_POST['checkin_date']) ? sanitize_text_field($_POST['checkin_date']) : '';
                                $checkout_date = isset($_POST['checkout_date']) ? sanitize_text_field($_POST['checkout_date']) : '';
                                
                                // Manipulate the referenceUrl to use ResortID instead of Resort name
                                $original_url = $resort['referenceUrl'];
                                
                                // Extract the base URL (everything before the ?)
                                $url_parts = parse_url($original_url);
                                $base_url = $url_parts['scheme'] . '://' . $url_parts['host'] . $url_parts['path'];
                                
                                // Get the resortId from the resort data
                                $resort_id = isset($resort['resortId']) ? $resort['resortId'] : '';
                                
                                // Build the new URL with ResortID and our form dates
                                if (!empty($resort_id)) {
                                    $booking_url = $base_url . '?ResortID=' . urlencode($resort_id) . '&CheckInDT=' . $checkin_date . '&CheckOutDT=' . $checkout_date;
                                } else {
                                    // Fallback to original URL if no resortId
                                    $booking_url = $original_url;
                                }
                                
                                echo '<span class="sn_book_now_single">';
                                echo '<a class="stock_external open-modal-button" href="#" data-url="' . esc_url($booking_url) . '" data-resort-name="' . esc_attr($resortName) . '" data-unit-name="' . esc_attr($unitName) . '" data-price="' . esc_attr($rate) . '" data-checkin="' . esc_attr($checkin_date) . '" data-checkout="' . esc_attr($checkout_date) . '">';
                                echo '<span style="color: #fff; font-size: 0.6em;">Book Now</span></a></span>';
                            }
                        echo '</div>';
                        echo '</div>';
                    } catch (Exception $e) {
                        error_log('Error processing unit type at index ' . $unitIndex . ': ' . $e->getMessage());
                        ob_end_clean();
                        continue;
                    }
                    $current_unit_html = ob_get_clean();
                    if ($unitIndex === 0) {
                        $units_html .= $current_unit_html;
                    } else {
                        $hidden_units_html .= $current_unit_html;
                    }
                }
                echo $units_html;
                if ($unit_count > 1) {
                    echo '<div class="show-more-container">';
                    echo '<button class="show-more-button" data-target="hidden_units_' . esc_attr($index) . '">More options</button>';
                    echo '</div>';
                    echo '<div class="hidden_units" id="hidden_units_' . esc_attr($index) . '">' . $hidden_units_html . '</div>';
                }
                echo '</div></div>';
            } catch (Exception $e) {
                error_log('Error processing resort at index ' . $index . ': ' . $e->getMessage());
                continue;
            }
        }
        echo '</div>';
        echo '<div class="avante-powered-by" style="text-align: right; font-size: 0.8em; margin-top: 20px;">';
        echo '  <a href="https://avantetravel.co.za/" target="_blank" rel="noopener noreferrer" style="color: #888; text-decoration: none;">Powered by Avante Travel</a>';
        echo '</div>';
    } catch (Exception $e) {
        error_log('Exception in display_search_results: ' . $e->getMessage());
        error_log('Exception trace: ' . $e->getTraceAsString());
        echo '<div class="error-message">An error occurred displaying the search results. Please try again later.</div>';
    }
}
function get_auth_token($site = 'primary')
{
    if ($site === 'secondary' || $site === '2') {
        return get_option('avante_travel_secondary_auth_token', '');
    }
    if ($site === 'tertiary' || $site === 'third' || $site === '3') {
        return get_option('avante_travel_tertiary_auth_token', '');
    }
    return get_option('avante_travel_auth_token', '');
}
add_action('wp_ajax_handle_query_stock', 'handle_query_stock_form_submission');
add_action('wp_ajax_nopriv_handle_query_stock', 'handle_query_stock_form_submission');
add_filter('frm_api_request_args', 'my_custom_frm_api_request_header', 10, 2);
function my_custom_frm_api_request_header($arg_array, $args)
{
    if ($args['url'] == 'http://api.stocknetwork.co.za/api/2.0/persondiscount/member') {
        $arg_array['headers']['Host'] = 'api.stocknetwork.co.za';
        $arg_array['headers']['Content-type'] = 'application/json';
        $arg_array['headers']['Accept'] = 'application/json';
        $arg_array['headers']['Accept-Charset'] = 'utf-8';
        $arg_array['headers']['Authorization'] = 'Bearer ' . get_auth_token(); // Use the single token
    }
    if ($args['url'] == 'http://api.stocknetwork.co.za/api/2.0/persondiscount/voucher') {
        $arg_array['headers']['Host'] = 'api.stocknetwork.co.za';
        $arg_array['headers']['Content-type'] = 'application/json';
        $arg_array['headers']['Accept'] = 'application/json';
        $arg_array['headers']['Accept-Charset'] = 'utf-8';
        $arg_array['headers']['Authorization'] = 'Bearer ' . get_auth_token(); // Use the single token
    }
    return $arg_array;
}
function render_stock_voucher_form($atts = [])
{
    $form_result = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_voucher'])) {
        $form_result = process_voucher_form_submission();
    }
    ob_start();
?>

    <?php if (!empty($form_result)): ?>
        <div id="voucher-form-results"><?php echo $form_result; ?></div>
    <?php endif; ?>
    <div class="voucher-form-container">
        <form id="voucher-form" method="post" class="horizontal-form">
            <div class="input_container_voucher">
                <label for="voucher_code">Voucher Code</label>
                <input type="text" id="voucher_code" name="voucher_code" placeholder="Voucher Code" required>
            </div>
            <div class="input_container_voucher half-width">
                <label for="amount">Amount</label>
                <input type="number" id="amount" name="amount" placeholder="Amount" required>
            </div>
            <div class="input_container_voucher">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" placeholder="Full Name" required>
            </div>
            <div class="input_container_voucher">
                <label for="email_address">Email Address</label>
                <input type="email" id="email_address" name="email_address" placeholder="Email Address" required>
            </div>
            <div class="input_container_voucher">
                <label for="cellphone">Cellphone</label>
                <input type="tel" id="cellphone" name="cellphone" placeholder="Cellphone Number" required>
            </div>
            <input type="submit" name="submit_voucher" value="Submit Voucher">
        </form>
    </div>
<?php
    return ob_get_clean();
}

function render_auto_search_destination($atts = [])
{
    // Parse shortcode attributes
    $atts = shortcode_atts(array(
        'destination' => '', // Required: destination name
        'unit_size' => '', // Optional: unit size ID, defaults to "All Unit Sizes"
        'site' => 'primary' // Site identifier: 'primary' (default) or 'secondary'
    ), $atts, 'auto_search_destination');
    
    $destination = sanitize_text_field($atts['destination']);
    $site = sanitize_text_field($atts['site']);
    
    // Validate destination is provided
    if (empty($destination)) {
        return '<div class="error-message">Error: Destination parameter is required. Usage: [auto_search_destination destination="Cape Town"]</div>';
    }
    
    // Calculate dates: 1 month from now, checkout 3 days after checkin
    $checkin_date_obj = new DateTime();
    $checkin_date_obj->modify('+1 month');
    $checkin_date = $checkin_date_obj->format('Y-m-d');
    
    $checkout_date_obj = clone $checkin_date_obj;
    $checkout_date_obj->modify('+3 days');
    $checkout_date = $checkout_date_obj->format('Y-m-d');
    
    // Format dates for datepicker display (mm/dd/yyyy format)
    $checkin_display = $checkin_date_obj->format('m/d/Y');
    $checkout_display = $checkout_date_obj->format('m/d/Y');
    $date_range_display = $checkin_display . ' - ' . $checkout_display;
    
    // Get unit size - default to "All Unit Sizes" if not specified
    $unit_size = sanitize_text_field($atts['unit_size']);
    $selected_unit_id = '';
    if (empty($unit_size)) {
        $search_options = get_option('avante_travel_search_options');
        if (!empty($search_options['unitSizes'])) {
            // Find the "All" option
            foreach ($search_options['unitSizes'] as $size) {
                if (isset($size['description']) && strpos(strtolower($size['description']), 'all unit sizes') !== false) {
                    $unit_size = $size['unitSizeId'];
                    $selected_unit_id = $size['unitSizeId'];
                    break;
                }
            }
        }
        // Fallback if not found
        if (empty($unit_size)) {
            $unit_size = 'all';
        }
    } else {
        $selected_unit_id = $unit_size;
    }
    
    ob_start();
    ?>
    <div id="avante-travel-search-wrapper" data-auto-search="true" data-destination="<?php echo esc_attr($destination); ?>" data-checkin="<?php echo esc_attr($checkin_date); ?>" data-checkout="<?php echo esc_attr($checkout_date); ?>">
        <div class="horizontal-form-container">
            <form id="query-stock-form" method="post" class="horizontal-form">
                <input type="hidden" name="site" value="<?php echo esc_attr($site); ?>">
                <div class="input-container date-range">
                    <svg aria-hidden="true" class="e-font-icon-svg e-far-calendar-alt" viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg">
                        <path d="M148 288h-40c-6.6 0-12-5.4-12-12v-40c0-6.6 5.4-12 12-12h40c6.6 0 12 5.4 12 12v40c0 6.6-5.4 12-12 12zm108-12v-40c0-6.6-5.4-12-12-12h-40c-6.6 0-12 5.4-12 12v40c0 6.6 5.4 12 12 12h40c6.6 0 12-5.4 12-12zm96 0v-40c0-6.6-5.4-12-12-12h-40c-6.6 0-12 5.4-12 12v40c0 6.6 5.4 12 12 12h40c6.6 0 12-5.4 12-12zm-96 96v-40c0-6.6-5.4-12-12-12h-40c-6.6 0-12 5.4-12 12v40c0 6.6 5.4 12 12 12h40c6.6 0 12-5.4 12-12zm-96 0v-40c0-6.6-5.4-12-12-12h-40c-6.6 0-12 5.4-12 12v40c0 6.6 5.4 12 12 12h40c6.6 0 12-5.4 12-12zm192 0v-40c0-6.6-5.4-12-12-12h-40c-6.6 0-12 5.4-12 12v40c0 6.6 5.4 12 12 12h40c6.6 0 12-5.4 12-12zm96-260v352c0 26.5-21.5 48-48 48H48c-26.5 0-48-21.5-48-48V112c0-26.5 21.5-48 48-48h48V12c0-6.6 5.4-12 12-12h40c6.6 0 12 5.4 12 12v52h128V12c0-6.6 5.4-12 12-12h40c6.6 0 12 5.4 12 12v52h48c26.5 0 48 21.5 48 48zm-48 346V160H48v298c0 3.3 2.7 6 6 6h340c3.3 0 6-2.7 6-6z"></path>
                    </svg>
                    <input type="text" id="daterange" name="daterange" placeholder="Check-In - Check-Out" class="daterange-input" value="<?php echo esc_attr($date_range_display); ?>" required autocomplete="off">
                    <input type="hidden" id="checkin_date" name="checkin_date" value="<?php echo esc_attr($checkin_date); ?>">
                    <input type="hidden" id="checkout_date" name="checkout_date" value="<?php echo esc_attr($checkout_date); ?>">
                </div>
                <div class="input-container destination">
                    <svg aria-hidden="true" class="e-font-icon-svg e-fas-map-marker-alt" viewBox="0 0 384 512" xmlns="http://www.w3.org/2000/svg">
                        <path d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0zM192 272c44.183 0 80-35.817 80-80s-35.817-80-80-80-80 35.817-80 80 35.817 80 80 80z"></path>
                    </svg>
                    <input type="text" id="destination" name="destination" placeholder="Destination" class="large" value="<?php echo esc_attr($destination); ?>" required>
                    <button type="button" id="clear-destination" class="clear-button">x</button>
                </div>
                <div class="input-container2">
                    <select name="unit_size" required>
                        <?php
                        $search_options = get_option('avante_travel_search_options');
                        if (!empty($search_options['unitSizes'])) {
                            foreach ($search_options['unitSizes'] as $size) {
                                $selected = ($size['unitSizeId'] === $selected_unit_id) ? 'selected' : '';
                                echo '<option value="' . esc_attr($size['unitSizeId']) . '" ' . $selected . '>' . esc_html($size['name']) . '</option>';
                            }
                        } else {
                            // Fallback to hardcoded values if API data is not available
                            echo '<option value="all" ' . ($selected_unit_id === 'all' ? 'selected' : '') . '>All Unit Sizes</option>';
                            echo '<option value="d3bd7e8f-0276-4a90-8f1b-818662f0fcf1">2 Sleeper</option>';
                            echo '<option value="7ea1f601-0932-4c1e-9917-6311b132a0c1">1 Sleeper</option>';
                            echo '<option value="f6f68ead-b0f9-42fe-b367-b0f802a8fc23">3 Sleeper</option>';
                            echo '<option value="639b55c9-f056-4bca-bf2b-d68ba3459490">4 Sleeper</option>';
                            echo '<option value="3a3da04b-8342-423e-89c0-6b37ee5fdf2b">5 Sleeper</option>';
                            echo '<option value="aa216d9c-a4d5-45bd-b7a7-836c20e3b188">6 Sleeper</option>';
                            echo '<option value="6d96cbc7-45a7-42f8-ba34-b1a4ca1ecf73">7 Sleeper</option>';
                            echo '<option value="79e9eb0a-1d67-4201-91eb-59ca5347f355">8 Sleeper</option>';
                            echo '<option value="988868c3-be4d-4625-97c0-c484aa7e39ae">9 Sleeper</option>';
                            echo '<option value="eb95dcbc-98fa-4870-b48f-54102d89e3fe">10 Sleeper</option>';
                            echo '<option value="91acab95-5767-4807-a4aa-d5ff80a6860c">11 Sleeper</option>';
                            echo '<option value="d7b1e301-3849-4fb1-aa21-fdb29cfbe1e0">12+ Sleeper</option>';
                        }
                        ?>
                    </select>
                    <svg aria-hidden="true" class="e-font-icon-svg e-fas-user-plus" viewBox="0 0 640 512" xmlns="http://www.w3.org/2000/svg">
                        <path d="M624 208h-64v-64c0-8.8-7.2-16-16-16h-32c-8.8 0-16 7.2-16 16v64h-64c-8.8 0-16 7.2-16 16v32c0 8.8 7.2 16 16 16h64v64c0 8.8 7.2 16 16 16h32c8.8 0 16-7.2 16-16v-64h64c8.8 0 16-7.2 16-16v-32c0-8.8-7.2-16-16-16zm-400 48c70.7 0 128-57.3 128-128S294.7 0 224 0 96 57.3 96 128s57.3 128 128 128zm89.6 32h-16.7c-22.2 10.2-46.9 16-72.9 16s-50.6-5.8-72.9-16h-16.7C60.2 288 0 348.2 0 422.4V464c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48v-41.6c0-74.2-60.2-134.4-134.4-134.4z"></path>
                    </svg>
                    
                </div>
                <div class="submit-button-container">
                    <a href="#" id="more-options-toggle">More Filters</a>
                    <input type="submit" name="submit_query" value="Search">
                </div>
                <div class="more-options-container" id="more-options-container" style="display: none;">
                    <?php
                    $search_options = get_option('avante_travel_search_options');
                    if (!empty($search_options)) {
                        render_filter_section('Amenities', $search_options['amenities'] ?? [], 'amenities');
                        render_filter_section('Experiences', $search_options['experiences'] ?? [], 'experiences');
                        render_filter_section('Activities', $search_options['activities'] ?? [], 'activities');
                    }
                    ?>
                </div>

            </form>
        </div>
        <div id="search-parameters" class="debug-info" style="text-align: right;"></div>
        
        <!-- Modal for iframe content (resort info, unit info, etc.) -->
        <div id="iframeModal" class="modal-overlay">
            <div class="modal-content iframe-modal">
                <span class="modal-close">&times;</span>
                <div class="iframe-container">
                    <h3 id="iframeModalTitle">Information</h3>
                    <iframe id="iframeContent" src="" frameborder="0"></iframe>
                </div>
            </div>
        </div>
        
        <div id="query-stock-results">
            <!-- Results will appear here after auto-search -->
        </div>
        <!-- Modal for booking form -->
        <div id="bookNowModal" class="modal-overlay">
            <div class="modal-content booking-form-modal">
                <span class="modal-close">&times;</span>
                <div class="booking-form-container">
                    <h3>Book Your Accommodation</h3>
                    
                    <!-- Overview section showing what they're enquiring about -->
                    <div id="booking-overview" class="booking-overview" style="display: none;">
                        <h4>Accommodation Details</h4>
                        <div class="overview-content">
                            <div class="overview-item">
                                <strong>Resort:</strong> <span id="overview-resort">-</span>
                            </div>
                            <div class="overview-item">
                                <strong>Unit Type:</strong> <span id="overview-unit">-</span>
                            </div>
                            <div class="overview-item">
                                <strong>Price:</strong> <span id="overview-price">-</span>
                            </div>
                            <div class="overview-item">
                                <strong>Check-in:</strong> <span id="overview-checkin">-</span>
                            </div>
                            <div class="overview-item">
                                <strong>Check-out:</strong> <span id="overview-checkout">-</span>
                            </div>
                        </div>
                    </div>
                    
                    <form id="booking-form" class="booking-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="booking_full_name">Full Name *</label>
                                <input type="text" id="booking_full_name" name="full_name" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="booking_email">Email Address *</label>
                                <input type="email" id="booking_email" name="email" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="booking_phone">Phone Number</label>
                                <input type="tel" id="booking_phone" name="phone">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="booking_adults">Number of occupants older than 17*</label>
                                <select id="booking_adults" name="adults" required>
                                    <option value="">Select</option>
                                    <option value="1">1</option>
                                    <option value="2" selected>2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                    <option value="5">5</option>
                                    <option value="6">6</option>
                                    <option value="7">7</option>
                                    <option value="8">8</option>
                                    <option value="9">9</option>
                                    <option value="10">10</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="booking_young_children">Number of occupants aged between 0 and 6*</label>
                                <select id="booking_young_children" name="young_children" required>
                                    <option value="">Select</option>
                                    <option value="0" selected>0</option>
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                    <option value="5">5</option>
                                    <option value="6">6</option>
                                    <option value="7">7</option>
                                    <option value="8">8</option>
                                    <option value="9">9</option>
                                    <option value="10">10</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="booking_older_children">Number of occupants aged between 6 and 17</label>
                                <select id="booking_older_children" name="older_children">
                                    <option value="">Select</option>
                                    <option value="0" selected>0</option>
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                    <option value="5">5</option>
                                    <option value="6">6</option>
                                    <option value="7">7</option>
                                    <option value="8">8</option>
                                    <option value="9">9</option>
                                    <option value="10">10</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group full-width">
                                <label for="booking_message">Additional Message (Optional)</label>
                                <textarea id="booking_message" name="message" rows="3" placeholder="Any special requests or questions..."></textarea>
                            </div>
                        </div>
                        <div class="form-row" style="display: none;">
                            <div class="form-group full-width">
                                <label><strong>Booking URL:</strong></label>
                                <div id="booking-url-display" style="background: #f5f5f5; padding: 8px; border-radius: 4px; word-break: break-all; font-family: monospace; font-size: 12px; color: #666; margin-top: 5px;">
                                    URL will appear here when you select a property
                                </div>
                            </div>
                        </div>
                        <div style="height:10px; overflow:hidden;">
                        <input type="hidden" id="booking_url" name="booking_url" value="">
                        <input type="hidden" id="resort_name" name="resort_name" value="">
                        <input type="hidden" id="unit_name" name="unit_name" value="">
                        <input type="hidden" id="unit_price" name="unit_price" value="">
                        <input type="hidden" id="checkin_date" name="checkin_date" value="<?php echo esc_attr($checkin_date); ?>">
                        <input type="hidden" id="checkout_date" name="checkout_date" value="<?php echo esc_attr($checkout_date); ?>">
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn-cancel">Cancel</button>
                            <button type="submit" class="btn-submit">Send Booking Request</button>
                        </div>
                    </form>
                    <div id="booking-form-message" class="form-message" style="display: none;"></div>
                </div>
            </div>
        </div>
    </div>
    <script type="text/javascript">
    // Wait for window load to ensure all scripts are loaded
    window.addEventListener('load', function() {
        setTimeout(function() {
            var wrapper = document.getElementById('avante-travel-search-wrapper');
            if (!wrapper || wrapper.getAttribute('data-auto-search') !== 'true') {
                return;
            }
            
            // Check if already triggered
            if (wrapper.dataset.autoSearchDone === 'true') {
                return;
            }
            wrapper.dataset.autoSearchDone = 'true';
            
            var form = wrapper.querySelector('#query-stock-form');
            if (!form) {
                return;
            }
            
            var checkinDate = wrapper.getAttribute('data-checkin');
            var checkoutDate = wrapper.getAttribute('data-checkout');
            
            // Set the dates
            if (checkinDate && checkoutDate) {
                var checkinInput = wrapper.querySelector('#checkin_date');
                var checkoutInput = wrapper.querySelector('#checkout_date');
                if (checkinInput) checkinInput.value = checkinDate;
                if (checkoutInput) checkoutInput.value = checkoutDate;
            }
            
            // Trigger the form submit - the handler in avante-travel.js should catch it
            // Use native event dispatch to ensure it works with addEventListener
            var submitEvent = new Event('submit', {
                bubbles: true,
                cancelable: true
            });
            
            // Dispatch the event
            form.dispatchEvent(submitEvent);
        }, 500); // Small delay after window load to ensure handlers are ready
    });
    </script>
    <?php
    
    return ob_get_clean();
}

function process_voucher_form_submission()
{
    try {
        $voucher_code = sanitize_text_field($_POST['voucher_code']);
        $full_name = sanitize_text_field($_POST['full_name']);
        $email_address = sanitize_email($_POST['email_address']);
        $cellphone = sanitize_text_field($_POST['cellphone']);
        $amount = isset($_POST['amount']) ? (int)$_POST['amount'] : 100;
        $api_url = 'http://api.stocknetwork.co.za/api/2.0/persondiscount/voucher';
        $request_body = json_encode([
            [
                'code' => $voucher_code,
                'amount' => $amount,
                'fullName' => $full_name,
                'emailAddress' => $email_address,
                'cellphone' => $cellphone,
                'isActive' => true
            ]
        ]);
        $auth_token = get_auth_token();
        $headers = [
            'Host' => 'api.stocknetwork.co.za',
            'Content-type' => 'application/json',
            'Accept' => 'application/json',
            'Accept-Charset' => 'utf-8',
            'Authorization' => 'Bearer ' . $auth_token
        ];
        error_log('API Request URL: ' . $api_url);
        error_log('API Request Body: ' . $request_body);
        error_log('Using token: ' . substr($auth_token, 0, 20) . '...');
        error_log('Request body: ' . $request_body);
        $response = wp_remote_post($api_url, [
            'body' => $request_body,
            'headers' => $headers,
            'timeout' => 30
        ]);
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            error_log('API Request Error: ' . $error_message);
            return '<div class="error-message-api">An error occurred submitting your voucher, please try again. Error: ' . esc_html($error_message) . '</div>';
        } else {
            $body = wp_remote_retrieve_body($response);
            $response_code = wp_remote_retrieve_response_code($response);
            error_log('API Response Code: ' . $response_code);
            error_log('API Response Body: ' . $body);
            if ($response_code >= 200 && $response_code < 300) {
                return '<div class="success-message">
                    <h4>Voucher Submitted Successfully!</h4>
                    <p>Your voucher code <strong>' . esc_html($voucher_code) . '</strong> for R' . esc_html($amount) . ' has been processed.</p>
                    <p>confirmation emails not yet set</p>
                </div>';
            } else {
                $error_message = $body;
                $json_body = json_decode($body, true);
                if (json_last_error() === JSON_ERROR_NONE && isset($json_body['message'])) {
                    $error_message = $json_body['message'];
                }
                return '<div class="error-message-api">
                    <h4>Error Processing Voucher</h4>
                    <p>We encountered a problem while submitting your voucher.</p>
                    <p>Error code: ' . esc_html($response_code) . '</p>
                    <p>' . esc_html($error_message) . '</p>
                </div>';
            }
        }
    } catch (Exception $e) {
        error_log('Exception in voucher handler: ' . $e->getMessage());
        error_log('Exception trace: ' . $e->getTraceAsString());
        return '<div class="error-message">
            <h4>Error Processing Request</h4>
            <p>An error occurred: ' . esc_html($e->getMessage()) . '</p>
            <p>Please try again later.</p>
        </div>';
    }
}
add_action('wp_ajax_handle_query_stock', 'handle_query_stock_form_submission');
add_action('wp_ajax_nopriv_handle_query_stock', 'handle_query_stock_form_submission');
add_action('wp_ajax_handle_stock_voucher', 'handle_stock_voucher_submission');
add_action('wp_ajax_nopriv_handle_stock_voucher', 'handle_stock_voucher_submission');
add_action('wp_ajax_handle_booking_request', 'handle_booking_request_submission');
add_action('wp_ajax_nopriv_handle_booking_request', 'handle_booking_request_submission');

function handle_booking_request_submission()
{
    try {
        // Sanitize and validate form data
        $full_name = sanitize_text_field($_POST['full_name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $adults = intval($_POST['adults'] ?? 0);
        $young_children = intval($_POST['young_children'] ?? 0);
        $older_children = intval($_POST['older_children'] ?? 0);
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $booking_url = esc_url_raw($_POST['booking_url'] ?? '');
        $resort_name = sanitize_text_field($_POST['resort_name'] ?? '');
        $unit_name = sanitize_text_field($_POST['unit_name'] ?? '');
        $unit_price = sanitize_text_field($_POST['unit_price'] ?? '');
        $checkin_date = sanitize_text_field($_POST['checkin_date'] ?? '');
        $checkout_date = sanitize_text_field($_POST['checkout_date'] ?? '');

        // Validation
        $errors = [];
        if (empty($full_name)) {
            $errors[] = 'Full name is required.';
        }
        if (empty($email) || !is_email($email)) {
            $errors[] = 'Valid email address is required.';
        }
        if ($adults < 1) {
            $errors[] = 'Number of adults is required (minimum 1).';
        }
        if ($young_children < 0) {
            $errors[] = 'Number of young children (0-6) cannot be negative.';
        }
        if ($older_children < 0) {
            $errors[] = 'Number of older children (6-17) cannot be negative.';
        }
        if (empty($booking_url)) {
            $errors[] = 'Booking information is missing.';
        }

        if (!empty($errors)) {
            wp_send_json_error(implode(' ', $errors));
            return;
        }

        // Prepare email content
        $to = 'admin@avantehospitality.co.za, liason@avantehospitality.co.za';
        $additional = trim((string) get_option('avante_travel_additional_email', ''));
        if (!empty($additional)) {
            // Validate and append additional emails (comma-separated)
            $emails = array_filter(array_map('trim', explode(',', $additional)));
            $valids = array();
            foreach ($emails as $em) {
                $san = sanitize_email($em);
                if (!empty($san) && is_email($san)) {
                    $valids[] = $san;
                }
            }
            if (!empty($valids)) {
                $to .= ', ' . implode(', ', array_unique($valids));
            }
        }
        $subject = 'New Booking Request - ' . $resort_name;
        
        $email_body = "New booking request received from the Avante Travel website.\n\n";
        $email_body .= "CUSTOMER DETAILS:\n";
        $email_body .= "Name: " . $full_name . "\n";
        $email_body .= "Email: " . $email . "\n";
        $email_body .= "Phone: " . ($phone ? $phone : 'Not provided') . "\n";
        $email_body .= "Number of Adults (17+): " . $adults . "\n";
        $email_body .= "Number of Young Children (0-6): " . $young_children . "\n";
        $email_body .= "Number of Older Children (6-17): " . $older_children . "\n\n";
        
        $email_body .= "ACCOMMODATION DETAILS:\n";
        $email_body .= "Resort: " . $resort_name . "\n";
        $email_body .= "Unit Type: " . $unit_name . "\n";
        $email_body .= "Price: " . ($unit_price ? 'R ' . $unit_price : 'Not specified') . "\n";
        $email_body .= "Check-in Date: " . ($checkin_date ? $checkin_date : 'Not specified') . "\n";
        $email_body .= "Check-out Date: " . ($checkout_date ? $checkout_date : 'Not specified') . "\n";
        $email_body .= "Booking URL: " . $booking_url . "\n\n";
        
        if (!empty($message)) {
            $email_body .= "CUSTOMER MESSAGE:\n";
            $email_body .= $message . "\n\n";
        }
        
        $email_body .= "This booking request was submitted on " . current_time('mysql') . "\n";
        $email_body .= "Please contact the customer directly to complete the booking.";

        // Email headers
        $headers = [
            'Content-Type: text/plain; charset=UTF-8',
            'From: Avante Travel <noreply@' . $_SERVER['HTTP_HOST'] . '>',
            'Reply-To: ' . $email
        ];

        // Send email
        $mail_sent = wp_mail($to, $subject, $email_body, $headers);

        if ($mail_sent) {
            // Log successful booking request
            error_log('Booking request sent successfully for: ' . $email . ' - Resort: ' . $resort_name);
            
            wp_send_json_success('Booking request sent successfully!');
        } else {
            error_log('Failed to send booking request email for: ' . $email . ' - Resort: ' . $resort_name);
            wp_send_json_error('Failed to send booking request. Please try again or contact us directly.');
        }

    } catch (Exception $e) {
        error_log('Exception in booking request handler: ' . $e->getMessage());
        wp_send_json_error('An error occurred processing your request. Please try again.');
    }
}


function avante_travel_get_location_field($location, $field)
{
    if (!is_array($location)) {
        return '';
    }

    $candidates = array(
        $field,
        strtolower($field),
        ucfirst(strtolower($field)),
    );

    if (strtolower($field) === 'city2') {
        $candidates[] = 'City2';
    }
    if (strtolower($field) === 'state') {
        $candidates[] = 'State';
    }

    foreach (array_unique($candidates) as $key) {
        if (!empty($location[$key])) {
            return $location[$key];
        }
    }

    return '';
}

function avante_travel_fetch_and_save_search_options($site = 'primary', $force = false)
{
    $cache_key = 'avante_travel_search_options_fetched';
    if (!$force && get_transient($cache_key)) {
        $search_options = get_option('avante_travel_search_options', array());
        $site_code = get_option('avante_travel_site_code', 'unknown');
        if (empty($site_code) && is_array($search_options) && isset($search_options['siteCode'])) {
            $site_code = $search_options['siteCode'];
        }
        return array(
            'success' => true,
            'cached' => true,
            'siteCode' => $site_code,
            'message' => 'Search options already refreshed recently.',
        );
    }

    $token = get_auth_token($site);
    if (empty($token)) {
        return new WP_Error('missing_token', 'Auth token is missing.');
    }

    if (stripos($token, 'Bearer ') !== 0) {
        $token = 'Bearer ' . $token;
    }

    $response = wp_remote_get('http://api.stocknetwork.co.za/api/2.0/search/options', array(
        'headers' => array(
            'Authorization' => $token,
            'Accept' => 'application/json',
            'Accept-Charset' => 'utf-8',
            'Content-Type' => 'application/json',
            'Host' => 'api.stocknetwork.co.za',
        ),
        'timeout' => 30,
    ));

    if (is_wp_error($response)) {
        return $response;
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if ($code !== 200 || empty($data)) {
        $msg_detail = !empty($data['message']) ? $data['message'] : 'Invalid response from search options endpoint.';
        return new WP_Error('search_options_failed', sprintf('%s (HTTP %d)', $msg_detail, $code));
    }

    update_option('avante_travel_search_options', $data);

    $site_code = isset($data['siteCode']) ? $data['siteCode'] : 'Not found';
    update_option('avante_travel_site_code', $site_code);
    set_transient($cache_key, time(), HOUR_IN_SECONDS);

    error_log('Avante Travel - Search options refreshed. Site Code: ' . $site_code);

    return array(
        'success' => true,
        'cached' => false,
        'siteCode' => $site_code,
        'locationCount' => !empty($data['locations']) && is_array($data['locations']) ? count($data['locations']) : 0,
    );
}

add_action('wp_ajax_avante_refresh_search_options', 'avante_travel_ajax_refresh_search_options');
add_action('wp_ajax_nopriv_avante_refresh_search_options', 'avante_travel_ajax_refresh_search_options');

function avante_travel_ajax_refresh_search_options()
{
    $site = isset($_POST['site']) ? sanitize_text_field(wp_unslash($_POST['site'])) : 'primary';
    $result = avante_travel_fetch_and_save_search_options($site);

    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }

    wp_send_json_success($result);
}

add_action('wp_ajax_avante_get_locations', 'avante_travel_ajax_get_locations');
add_action('wp_ajax_nopriv_avante_get_locations', 'avante_travel_ajax_get_locations');

function avante_travel_ajax_get_locations() {
    $search_options = get_option('avante_travel_search_options');
    $search_term = sanitize_text_field($_GET['term'] ?? '');
    
    if (empty($search_options['locations'])) {
        wp_send_json([]);
        return;
    }
    
    $filtered_locations = [];
    $search_term_lower = strtolower($search_term);
    $seen_locations = []; // Track unique locations to prevent duplicates
    
    foreach ($search_options['locations'] as $location) {
        if (empty($location['name'])) continue;
        
        // Create searchable text from all fields
        $searchable_text = strtolower(implode(' ', array_filter([
            avante_travel_get_location_field($location, 'name'),
            avante_travel_get_location_field($location, 'city'),
            avante_travel_get_location_field($location, 'city2'),
            avante_travel_get_location_field($location, 'state'),
            avante_travel_get_location_field($location, 'area'),
            avante_travel_get_location_field($location, 'region'),
            avante_travel_get_location_field($location, 'country'),
        ])));
        
        // Determine the type first
        $is_accommodation = !empty($location['name']) && (
            strpos(strtolower($location['name']), 'hotel') !== false ||
            strpos(strtolower($location['name']), 'lodge') !== false ||
            strpos(strtolower($location['name']), 'resort') !== false ||
            strpos(strtolower($location['name']), 'guesthouse') !== false ||
            strpos(strtolower($location['name']), 'bnb') !== false ||
            strpos(strtolower($location['name']), 'cottage') !== false ||
            strpos(strtolower($location['name']), 'villa') !== false ||
            strpos(strtolower($location['name']), 'apartment') !== false ||
            strpos(strtolower($location['name']), 'suite') !== false ||
            strpos(strtolower($location['name']), 'inn') !== false ||
            strpos(strtolower($location['name']), 'hostel') !== false ||
            strpos(strtolower($location['name']), 'camp') !== false ||
            strpos(strtolower($location['name']), 'estate') !== false
        );
        
        // Determine what to display and what to search with
        if ($is_accommodation) {
            $display_text = $location['name'];
            $search_text = $location['name'];
            $type = 'accommodation';
            $unique_key = 'accommodation_' . strtolower($display_text);
            
            $should_include = strpos($searchable_text, $search_term_lower) !== false;
        } else {
            // For locations, prioritize city > city2 > state > area > region > country
            $city = avante_travel_get_location_field($location, 'city');
            $city2 = avante_travel_get_location_field($location, 'city2');
            $state = avante_travel_get_location_field($location, 'state');
            $area = avante_travel_get_location_field($location, 'area');
            $region = avante_travel_get_location_field($location, 'region');
            $country = avante_travel_get_location_field($location, 'country');

            if (!empty($city)) {
                $display_text = $city;
                $search_text = $city;
            } elseif (!empty($city2)) {
                $display_text = $city2;
                $search_text = $city2;
            } elseif (!empty($state)) {
                $display_text = $state;
                $search_text = $state;
            } elseif (!empty($area)) {
                $display_text = $area;
                $search_text = $area;
            } elseif (!empty($region)) {
                $display_text = $region;
                $search_text = $region;
            } else {
                $display_text = $country;
                $search_text = $country;
            }
            $type = 'location';
            $unique_key = 'location_' . strtolower($display_text);
            
            $should_include = strpos($searchable_text, $search_term_lower) !== false;
        }
        
        // Only include if the search term actually matches the relevant field
        if ($should_include) {
            // Create location string for display
            $location_parts = array_filter([
                avante_travel_get_location_field($location, 'city'),
                avante_travel_get_location_field($location, 'city2'),
                avante_travel_get_location_field($location, 'state'),
                avante_travel_get_location_field($location, 'region'),
                avante_travel_get_location_field($location, 'country'),
            ]);
            $location_string = implode(', ', $location_parts);
            
            // Check for duplicates - only add if we haven't seen this combination before
            if (!isset($seen_locations[$unique_key])) {
                $seen_locations[$unique_key] = true;
                
                $filtered_locations[] = [
                    'value' => $search_text,
                    'label' => $display_text,
                    'location' => $location_string,
                    'type' => $type,
                    'full_data' => $location
                ];
            }
        }
    }
    
    // Sort results: locations first, then accommodations, both alphabetically
    usort($filtered_locations, function($a, $b) {
        if ($a['type'] !== $b['type']) {
            return $a['type'] === 'location' ? -1 : 1;
        }
        return strcmp($a['label'], $b['label']);
    });
    
    wp_send_json($filtered_locations);
}
?>