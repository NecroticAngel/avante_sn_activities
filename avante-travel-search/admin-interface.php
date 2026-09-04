<?php
add_action('admin_enqueue_scripts', 'avante_travel_admin_enqueue_scripts');
function avante_travel_admin_enqueue_scripts($hook_suffix)
{
    if ($hook_suffix !== 'toplevel_page_avante-travel-settings') {
        return;
    }
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('avante-travel-admin-script', plugins_url('/avante-travel-admin.js', __FILE__), array('wp-color-picker', 'jquery'), false, true);
}
add_action('admin_menu', 'avante_travel_add_admin_menu');
function avante_travel_add_admin_menu()
{
    add_menu_page(
        'Avante Travel Settings',
        'Avante Travel',
        'manage_options',
        'avante-travel-settings',
        'avante_travel_settings_page_html',
        'dashicons-airplane',
        20
    );
}
function avante_travel_settings_page_html()
{
    if (!current_user_can('manage_options')) {
        return;
    }
?>
    <div class="wrap" style="position: relative; min-height: 400px;">
        <div style="width: 66%; float: left; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); padding: 17px 32px 24px 32px; min-height: 180px; box-sizing: border-box;">
            <div style="display: flex; align-items: center; margin-bottom: 16px;">
                <img src="<?php echo plugins_url('avantetravel.png', __FILE__); ?>" alt="Avante Travel Logo" style="height: 60px; margin-bottom: 0; display: block; margin-right: 24px;">
                <h1 style="margin: 0; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; font-size: 1.5rem; flex: 1; text-align: right;">Avante Travel Settings</h1>
            </div>
            <hr style="border: none; border-top: 1px solid #e5e5e5; margin: 0 0 24px 0;">
            <style>
                #avante-travel-settings-form h2 {
                    margin: 4px 0 12px 0 !important;
                    padding-top: 12px;
                    border-top: 1px solid #e5e5e5;
                }
                #avante-travel-settings-form h2:first-of-type {
                    border-top: none;
                    padding-top: 0;
                }
                #avante-travel-settings-form table.form-table {
                    margin-top: 0;
                }
                /* Color grid styles */
                .avante-color-grid {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 20px 40px;
                    margin: 20px 0;
                }
                .avante-color-option {
                    display: flex;
                    flex-direction: column;
                }
                .avante-color-option label {
                    font-weight: 600;
                    margin-bottom: 8px;
                    color: #23282d;
                }
                .avante-color-option .description {
                    margin-top: 5px;
                    font-size: 12px;
                    color: #666;
                }
                /* Fix for color options grid layout */
                #avante-travel-settings-form .form-table tr:has(.avante-color-grid) th {
                    display: none;
                }
                #avante-travel-settings-form .form-table tr:has(.avante-color-grid) td {
                    padding-left: 0;
                    width: 100%;
                    colspan: 2;
                }
                /* Fallback for browsers that don't support :has() */
                #avante-travel-settings-form .form-table .avante-color-grid {
                    margin-left: 0;
                }
                #avante-travel-settings-form .form-table td > .avante-color-grid:first-child {
                    /* margin-left: -200px; */
                    width: calc(100% + 200px);
                }
            </style>
            <form id="avante-travel-settings-form" action="options.php" method="post">
                <?php
                settings_fields('avante_travel_options');
                do_settings_sections('avante_travel_options');
                ?>
                
                <?php submit_button('Save Settings'); ?>
            </form>
        </div>
        <div style="width: 32%; float: right; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); padding: 24px; margin-top: 0px; min-height: 180px; box-sizing: border-box;">
        <div style="display: flex; align-items: center; margin-bottom: 16px;">
             
                <h1 style="margin: 0; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; font-size: 1.5rem; flex: 1; text-align: Left;">How to Use</h1>
            </div>
           
            <hr style="border: none; border-top: 1px solid #e5e5e5; margin: 0 0 24px 0;">
            <h2 style="margin-top: 0;">Get your logins</h2>
            <div style="margin: 20px 0 0; font-weight: 600;">Contact the Avante Team to get credentials.</div><br>
            <h2 style="margin-top: 0;">First Time Run</h2>
            <ul style="margin: 12px 0 10px 18px; padding-left: 18px; list-style: disc; font-size: 14px; line-height: 1.5;">
                <li>Enter your login credentials</li>
                <li>Click "Fetch Token" to retrieve and save your access token</li>
                <li>Click "Update Search Options" to get the latest search options</li>
                <li>Click "Save Settings" to store your configuration</li>
                <li>Use the shortcode below to insert the search into your site</li>
            </ul>
            <h2 style="margin-top: 0;">Insert into your site</h2>
            <p style="font-size: 15px;">To put our search on your site, simply insert (click button to copy shortcode):</p>
            <div style="display:flex; align-items:center; gap:8px; margin:6px 0 10px 0;">
                <code id="avante-shortcode-text" style="background: #f5f5f5; padding: 2px 6px; border-radius: 4px;">[query_stock_form]</code>
                <button type="button" id="avante-copy-shortcode" class="button button-small" title="Copy shortcode">
                    <span class="dashicons dashicons-clipboard"></span>
                </button>
                <span id="avante-copy-shortcode-status" style="display:none; font-size:12px; color:#2c7;">Copied to clipboard</span>
            </div>
            <p style="font-size: 15px;">anywhere you like. The search will appear where you put it, and the results will come up under the form.</p>
            <p style="font-size: 15px;">If you don't want the results to appear directly under the search form, you can target any selector (#id, .class, section .child, etc.) and use the shortcode: <code>[query_stock_form results_target=".my-results"]</code> (replace <code>.my-results</code> with your target).</p>
            <p style="font-size: 15px;">To use additional bearer auth tokens, use: <code>[query_stock_form site="secondary"]</code> / <code>[query_stock_form site="2"]</code> for the second token, and <code>[query_stock_form site="tertiary"]</code> / <code>[query_stock_form site="3"]</code> for the third token.</p>

            <div style="margin-top: 32px;">
                <div style="font-weight: bold; font-size: 1.1rem; margin-bottom: 10px;">Contact Us</div>
                <div style="display: flex; align-items: center; color: #222; margin-bottom: 6px;">
                    <span style="color: #18ccc2; font-size: 1.2em; margin-right: 8px;">&#128222;</span>
                    <span style="font-size: 1.05em;">0764519401</span>
                </div>
                <div style="display: flex; align-items: center; color: #222;">
                    <span style="color: #18ccc2; font-size: 1.2em; margin-right: 8px;">&#9993;</span>
                    <span style="font-size: 1.05em;">marketing@avantehospitality.co.za</span>
                </div>
            </div>
        </div>
        <div style="clear: both;"></div>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Copy shortcode button
        $('#avante-copy-shortcode').on('click', function(e) {
            e.preventDefault();
            var text = $('#avante-shortcode-text').text().trim();
            if (!text) { return; }
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(function(){
                    var status = $('#avante-copy-shortcode-status');
                    status.fadeIn(150);
                    setTimeout(function(){ status.fadeOut(200); }, 1500);
                });
            } else {
                // Fallback
                var temp = $('<textarea>').val(text).appendTo('body').select();
                try { document.execCommand('copy'); } catch(err) {}
                temp.remove();
                var status = $('#avante-copy-shortcode-status');
                status.fadeIn(150);
                setTimeout(function(){ status.fadeOut(200); }, 1500);
            }
        });
        $('#avante-fetch-token').on('click', function(e) {
            e.preventDefault();
            var clientid = $('input[name="avante_travel_clientid"]').val();
            var clientsecret = $('input[name="avante_travel_clientsecret"]').val();
            var username = $('input[name="avante_travel_username"]').val();
            var status = $('#avante-fetch-token-status');
            status.text('Fetching...');
            var payload = {
                action: 'avante_fetch_token',
                clientid: clientid,
                clientsecret: clientsecret,
                username: username,
                _ajax_nonce: '<?php echo wp_create_nonce('avante_fetch_token_nonce'); ?>'
            };
            console.log('Fetch token request payload:', payload);
            $.post(ajaxurl, payload, function(response) {
                console.log('Fetch token response:', response);
                if (response.success) {
                    $('input[name="avante_travel_auth_token"]').val(response.data.token);
                    status.text('Token fetched and saved!');
                } else {
                    status.text('Error: ' + response.data);
                }
            });
        });

        $('#avante-fetch-search-options').on('click', function(e) {
            e.preventDefault();
            var status = $('#avante-fetch-options-status');
            status.text('Fetching...');
            var payload = {
                action: 'avante_fetch_search_options',
                _ajax_nonce: '<?php echo wp_create_nonce("avante_fetch_search_options_nonce"); ?>'
            };
            $.post(ajaxurl, payload, function(response) {
                if (response.success) {
                    status.text('Search options fetched and saved!');
                    // Console log the siteCode for verification
                    if (response.data && response.data.siteCode) {
                        console.log('Avante Travel - Site Code:', response.data.siteCode);
                    }
                } else {
                    status.text('Error: ' + response.data);
                }
            });
        });

        // Toggle additional email field visibility based on booking mode
        function updateAdditionalEmailVisibility() {
            var mode = $('input[name="avante_travel_booking_mode"]:checked').val();
            var row = $('#additional-email-container').closest('tr');
            if (mode === 'email') {
                $('#additional-email-container').slideDown(150);
                if (row.length) { row.show(); }
            } else {
                $('#additional-email-container').slideUp(150);
                if (row.length) { row.hide(); }
            }
        }
        $(document).on('change', 'input[name="avante_travel_booking_mode"]', updateAdditionalEmailVisibility);
        // Initialize on load
        updateAdditionalEmailVisibility();
    });
    </script>
<?php
}
add_action('admin_init', 'avante_travel_settings_init');
function avante_travel_settings_init()
{
    register_setting('avante_travel_options', 'avante_travel_main_color', 'sanitize_hex_color');
    register_setting('avante_travel_options', 'avante_travel_secondary_color', 'sanitize_hex_color');
    register_setting('avante_travel_options', 'avante_travel_form_bg_color', 'sanitize_hex_color');
    register_setting('avante_travel_options', 'avante_travel_button_color', 'sanitize_hex_color');
    register_setting('avante_travel_options', 'avante_travel_auth_token', 'sanitize_text_field');
    register_setting('avante_travel_options', 'avante_travel_primary_site_name', 'sanitize_text_field');
    register_setting('avante_travel_options', 'avante_travel_secondary_auth_token', 'sanitize_text_field');
    register_setting('avante_travel_options', 'avante_travel_secondary_site_name', 'sanitize_text_field');
    register_setting('avante_travel_options', 'avante_travel_tertiary_auth_token', 'sanitize_text_field');
    register_setting('avante_travel_options', 'avante_travel_tertiary_site_name', 'sanitize_text_field');
    register_setting('avante_travel_options', 'avante_travel_clientid', 'sanitize_text_field');
    register_setting('avante_travel_options', 'avante_travel_clientsecret', 'sanitize_text_field');
    register_setting('avante_travel_options', 'avante_travel_username', 'sanitize_text_field');
    register_setting('avante_travel_options', 'avante_travel_min_price', array('sanitize_callback' => 'avante_travel_sanitize_min_price'));
    register_setting('avante_travel_options', 'avante_travel_booking_mode', array('sanitize_callback' => 'avante_travel_sanitize_booking_mode'));
    register_setting('avante_travel_options', 'avante_travel_hide_more_filters', array('sanitize_callback' => 'avante_travel_sanitize_hide_more_filters'));
    
    add_settings_section(
        'avante_travel_design_section',
        'Basic Design',
        'avante_travel_design_section_callback',
        'avante_travel_options'
    );
    
    add_settings_field(
        'color_options_field',
        '',
        'avante_travel_color_options_field_render',
        'avante_travel_options',
        'avante_travel_design_section'
    );
    
    add_settings_section(
        'avante_travel_api_section',
        'API Credentials',
        'avante_travel_api_section_callback',
        'avante_travel_options'
    );
    add_settings_field(
        'clientid_field',
        'Client ID',
        'avante_travel_clientid_field_render',
        'avante_travel_options',
        'avante_travel_api_section'
    );
    add_settings_field(
        'clientsecret_field',
        'Client Secret',
        'avante_travel_clientsecret_field_render',
        'avante_travel_options',
        'avante_travel_api_section'
    );
    add_settings_field(
        'username_field',
        'Username',
        'avante_travel_username_field_render',
        'avante_travel_options',
        'avante_travel_api_section'
    );
    // Inline actions and token field under API Credentials section (placed after credentials inputs)
    add_settings_field(
        'fetch_token_buttons',
        '',
        'avante_travel_fetch_controls_field_render',
        'avante_travel_options',
        'avante_travel_api_section'
    );
    add_settings_field(
        'secondary_site_name_field',
        'Second Search Form Site Name',
        'avante_travel_secondary_site_name_field_render',
        'avante_travel_options',
        'avante_travel_api_section'
    );
    add_settings_field(
        'secondary_auth_token_field',
        'Second Search Form Bearer Auth',
        'avante_travel_secondary_auth_token_field_render',
        'avante_travel_options',
        'avante_travel_api_section'
    );
    add_settings_field(
        'tertiary_site_name_field',
        'Third Search Form Site Name',
        'avante_travel_tertiary_site_name_field_render',
        'avante_travel_options',
        'avante_travel_api_section'
    );
    add_settings_field(
        'tertiary_auth_token_field',
        'Third Search Form Bearer Auth',
        'avante_travel_tertiary_auth_token_field_render',
        'avante_travel_options',
        'avante_travel_api_section'
    );
    
    add_settings_section(
        'avante_travel_search_section',
        'Search Options',
        'avante_travel_search_section_callback',
        'avante_travel_options'
    );
    add_settings_field(
        'min_price_field',
        'Minimum Price (R)',
        'avante_travel_min_price_field_render',
        'avante_travel_options',
        'avante_travel_search_section'
    );
    add_settings_field(
        'hide_more_filters_field',
        'Hide More Filters',
        'avante_travel_hide_more_filters_field_render',
        'avante_travel_options',
        'avante_travel_search_section'
    );

    add_settings_section(
        'avante_travel_booking_section',
        'Booking Settings',
        'avante_travel_booking_section_callback',
        'avante_travel_options'
    );
    add_settings_field(
        'booking_mode_field',
        'Booking Mode',
        'avante_travel_booking_mode_field_render',
        'avante_travel_options',
        'avante_travel_booking_section'
    );
    add_settings_field(
        'additional_email_field',
        'Additional Notification Email',
        'avante_travel_additional_email_field_render',
        'avante_travel_options',
        'avante_travel_booking_section'
    );
}

function avante_travel_token_field_render()
{
    $token = get_option('avante_travel_auth_token', '');
?>
    <input type='text' name='avante_travel_auth_token' value='<?php echo esc_attr($token); ?>' class='regular-text' style="width: 100%;">
<?php
}

function avante_travel_secondary_auth_token_field_render()
{
    $secondary_token = get_option('avante_travel_secondary_auth_token', '');
?>
    <input type='text' name='avante_travel_secondary_auth_token' value='<?php echo esc_attr($secondary_token); ?>' class='regular-text' style="width: 100%;">
    <p class="description">Enter the bearer auth token manually for use with the second search form. Use shortcode: <code>[query_stock_form site="secondary"]</code> or <code>[query_stock_form site="2"]</code></p>
<?php
}

function avante_travel_secondary_site_name_field_render()
{
    $site_name = get_option('avante_travel_secondary_site_name', '');
?>
    <input type='text' name='avante_travel_secondary_site_name' value='<?php echo esc_attr($site_name); ?>' class='regular-text' style="width: 100%;" placeholder="e.g. Durban Site">
    <p class="description">Optional label to identify the second search form/site in your admin settings.</p>
<?php
}

function avante_travel_tertiary_site_name_field_render()
{
    $site_name = get_option('avante_travel_tertiary_site_name', '');
?>
    <input type='text' name='avante_travel_tertiary_site_name' value='<?php echo esc_attr($site_name); ?>' class='regular-text' style="width: 100%;" placeholder="e.g. Cape Town Site">
    <p class="description">Optional label to identify the third search form/site in your admin settings.</p>
<?php
}

function avante_travel_tertiary_auth_token_field_render()
{
    $tertiary_token = get_option('avante_travel_tertiary_auth_token', '');
?>
    <input type='text' name='avante_travel_tertiary_auth_token' value='<?php echo esc_attr($tertiary_token); ?>' class='regular-text' style="width: 100%;">
    <p class="description">Enter the bearer auth token manually for use with the third search form. Use shortcode: <code>[query_stock_form site="3"]</code> or <code>[query_stock_form site="tertiary"]</code></p>
<?php
}

function avante_travel_design_section_callback()
{
    echo '<p>Customize the basic design of the search form.</p>';
}

function avante_travel_color_options_field_render()
{
    $main_color = get_option('avante_travel_main_color', '#0dcdc2');
    $secondary_color = get_option('avante_travel_secondary_color', '#172d72');
    $form_bg_color = get_option('avante_travel_form_bg_color', '#ffffff');
    $button_color = get_option('avante_travel_button_color', '#0dcdc2');
?>
    <div class="avante-color-grid">
        <div class="avante-color-option">
            <label for="avante_travel_main_color">Main Color</label>
            <input type="text" id="avante_travel_main_color" name="avante_travel_main_color" 
                   value="<?php echo esc_attr($main_color); ?>" class="avante-travel-color-picker" />
        </div>
        <div class="avante-color-option">
            <label for="avante_travel_secondary_color">Secondary Color</label>
            <input type="text" id="avante_travel_secondary_color" name="avante_travel_secondary_color" 
                   value="<?php echo esc_attr($secondary_color); ?>" class="avante-travel-color-picker" />
        </div>
        <div class="avante-color-option">
            <label for="avante_travel_form_bg_color">Search Form Background</label>
            <input type="text" id="avante_travel_form_bg_color" name="avante_travel_form_bg_color" 
                   value="<?php echo esc_attr($form_bg_color); ?>" class="avante-travel-color-picker" />
        </div>
        <div class="avante-color-option">
            <label for="avante_travel_button_color">Button Color</label>
            <input type="text" id="avante_travel_button_color" name="avante_travel_button_color" 
                   value="<?php echo esc_attr($button_color); ?>" class="avante-travel-color-picker" />
            <p class="description">Color for search and filter buttons</p>
        </div>
    </div>
<?php
}

function avante_travel_api_section_callback() {
    echo '<p>Enter your API credentials below. Click "Fetch Token" to retrieve and save your access token automatically.</p>';
}

function avante_travel_fetch_controls_field_render() {
    echo '<div style="margin: 10px 0 0;">';
    echo '<button type="button" id="avante-fetch-token" class="button">Fetch Token</button> ';
    echo '<span id="avante-fetch-token-status" style="margin-left:10px;"></span> ';
    echo '<button type="button" id="avante-fetch-search-options" class="button" style="margin-left: 10px;">Update Search Options</button> ';
    echo '<span id="avante-fetch-options-status" style="margin-left:10px;"></span>';
    echo '</div>';
    $primary_site_name = get_option('avante_travel_primary_site_name', '');
    echo '<div style="margin-top:10px;">';
    echo '<label for="avante_travel_primary_site_name"><strong>Primary Site Name</strong></label><br />';
    echo "<input type='text' id='avante_travel_primary_site_name' name='avante_travel_primary_site_name' value='" . esc_attr($primary_site_name) . "' class='regular-text' style='width: 100%;' placeholder='e.g. Main Site' />";
    echo '<p class="description" style="margin-top: 4px;">Optional label to identify the primary search form/site in your admin settings.</p>';
    echo '</div>';
    echo '<div style="margin-top:10px;">';
    echo '<label for="avante_travel_auth_token"><strong>Auth Token</strong></label><br />';
    avante_travel_token_field_render();
    echo '</div>';
}

function avante_travel_clientid_field_render() {
    $clientid = get_option('avante_travel_clientid', '');
    echo "<input type='text' name='avante_travel_clientid' value='" . esc_attr($clientid) . "' class='regular-text' style='width: 100%;' />";
}

function avante_travel_clientsecret_field_render() {
    $clientsecret = get_option('avante_travel_clientsecret', '');
    echo "<input type='text' name='avante_travel_clientsecret' value='" . esc_attr($clientsecret) . "' class='regular-text' style='width: 100%;' />";
}

function avante_travel_username_field_render() {
    $username = get_option('avante_travel_username', '');
    echo "<input type='text' name='avante_travel_username' value='" . esc_attr($username) . "' class='regular-text' style='width: 100%;' />";
}

function avante_travel_search_section_callback() {
    echo '<p>Configure search parameters and filters.</p>';
    
    // Display current site code for verification
    $site_code = get_option('avante_travel_site_code', 'Not fetched yet');
    echo '<div style="background: #f0f0f1; padding: 10px; border-radius: 4px; margin: 10px 0;">';
    echo '<strong>Current Site Code:</strong> <code>' . esc_html($site_code) . '</code>';
    echo '<br><small style="color: #666;">This is the siteCode from the last successful "Update Search Options" fetch.</small>';
    echo '</div>';
}

function avante_travel_min_price_field_render() {
    $min_price = get_option('avante_travel_min_price', '0.0');
?>
    <input type="number" name="avante_travel_min_price" value="<?php echo esc_attr($min_price); ?>" 
           step="0.01" min="0" class="regular-text" style="width: 200px;" />
    <p class="description">Set the minimum price for search results. Leave as 0.0 to show all properties.</p>
<?php
}

function avante_travel_sanitize_min_price($input) {
    $value = floatval($input);
    return $value >= 0 ? $value : 0.0;
}

function avante_travel_hide_more_filters_field_render() {
    $hide_more_filters = get_option('avante_travel_hide_more_filters', '0');
?>
    <label style="display: flex; align-items: center; gap: 10px;">
        <span style="font-weight: 600;">Off</span>
        <label class="avante-toggle-switch">
            <input type="checkbox" name="avante_travel_hide_more_filters" value="1" <?php checked($hide_more_filters, '1'); ?> />
            <span class="avante-toggle-slider"></span>
        </label>
        <span style="font-weight: 600;">On</span>
    </label>
    <p class="description">When enabled, the "More Filters" button will be hidden on the search form.</p>
    <style>
        .avante-toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }
        .avante-toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .avante-toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        .avante-toggle-slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        .avante-toggle-switch input:checked + .avante-toggle-slider {
            background-color: #2271b1;
        }
        .avante-toggle-switch input:checked + .avante-toggle-slider:before {
            transform: translateX(26px);
        }
        .avante-toggle-switch input:focus + .avante-toggle-slider {
            box-shadow: 0 0 1px #2271b1;
        }
    </style>
<?php
}

function avante_travel_sanitize_hide_more_filters($input) {
    return $input === '1' || $input === 1 ? '1' : '0';
}

function avante_travel_booking_section_callback() {
    echo '<p>Select how bookings are handled when users click Book Now.</p>';
}

function avante_travel_booking_mode_field_render() {
    $mode = get_option('avante_travel_booking_mode', 'email');
?>
    <fieldset>
        <label style="display:inline-block;margin-right:15px;">
            <input type="radio" name="avante_travel_booking_mode" value="email" <?php checked($mode, 'email'); ?> /> Email booking (collect details and send email)
        </label>
        <label style="display:inline-block;">
            <input type="radio" name="avante_travel_booking_mode" value="live" <?php checked($mode, 'live'); ?> /> Live booking (open booking in iframe)
        </label>
    </fieldset>
    <p class="description">Choose "Live booking" to open the provider booking page inside a modal iframe instead of sending an email.</p>
<?php
}

function avante_travel_sanitize_booking_mode($input) {
    $allowed = array('email', 'live');
    $val = is_string($input) ? strtolower($input) : 'email';
    return in_array($val, $allowed, true) ? $val : 'email';
}

function avante_travel_additional_email_field_render() {
    $mode = get_option('avante_travel_booking_mode', 'email');
    $value = get_option('avante_travel_additional_email', '');
    $display_style = ($mode === 'email') ? '' : 'display:none;';
?>
    <div id="additional-email-container" style="<?php echo esc_attr($display_style); ?>">
        <label for="avante_travel_additional_email">
            <strong>Would you like to send copies of booking to an additional email?</strong>
        </label>
        <input type="text" id="avante_travel_additional_email" name="avante_travel_additional_email" value="<?php echo esc_attr($value); ?>" class="regular-text" style="width: 100%;" placeholder="name@example.com">
        <p class="description">Enter a single email address (or multiple, comma-separated) to receive a copy of booking requests.</p>
    </div>
<?php
}

// Allow storing one or more comma-separated email addresses, sanitizing each
register_setting('avante_travel_options', 'avante_travel_additional_email', array('sanitize_callback' => 'avante_travel_sanitize_additional_email'));

function avante_travel_sanitize_additional_email($input) {
    if (!is_string($input)) {
        return '';
    }
    $parts = array_filter(array_map('trim', explode(',', $input)));
    $valid = array();
    foreach ($parts as $email) {
        $san = sanitize_email($email);
        if (!empty($san) && is_email($san)) {
            $valid[] = $san;
        }
    }
    return implode(', ', array_unique($valid));
}

add_action('wp_ajax_avante_fetch_token', 'avante_travel_ajax_fetch_token');
add_action('wp_ajax_avante_fetch_search_options', 'avante_travel_ajax_fetch_search_options');

function avante_travel_ajax_fetch_search_options() {
    check_ajax_referer('avante_fetch_search_options_nonce');

    $token = get_option('avante_travel_auth_token');
    if (empty($token)) {
        wp_send_json_error('Auth token is missing. Please fetch token first.');
    }

    $response = wp_remote_get('http://api.stocknetwork.co.za/api/2.0/search/options', [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Accept'        => 'application/json',
            'Accept-Charset'=> 'utf-8',
            'Content-Type'  => 'application/json',
            'Host'          => 'api.stocknetwork.co.za',
        ],
        'timeout' => 30
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error('Request failed: ' . $response->get_error_message());
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if ($code === 200 && !empty($data)) {
        update_option('avante_travel_search_options', $data);
        
        // Extract and save siteCode for verification
        $site_code = isset($data['siteCode']) ? $data['siteCode'] : 'Not found';
        update_option('avante_travel_site_code', $site_code);
        
        // Log the siteCode for debugging
        error_log('Avante Travel - Search options fetched. Site Code: ' . $site_code);
        
        wp_send_json_success([
            'message' => 'Search options saved.',
            'siteCode' => $site_code
        ]);
    } else {
        $msg_detail = !empty($data['message']) ? $data['message'] : 'Invalid response from search options endpoint.';
        $msg = sprintf('%s (HTTP %d)', $msg_detail, $code);
        wp_send_json_error($msg);
    }
}

function avante_travel_ajax_fetch_token() {
    check_ajax_referer('avante_fetch_token_nonce');
    // Use wp_unslash + trim to preserve special characters like % or > that the API requires.
    $clientid     = isset($_POST['clientid']) ? trim( wp_unslash( $_POST['clientid'] ) ) : '';
    $clientsecret = isset($_POST['clientsecret']) ? trim( wp_unslash( $_POST['clientsecret'] ) ) : '';
    // Username typically does not contain special chars that would be HTML sensitive, but keep it safe.
    $username     = isset($_POST['username']) ? sanitize_text_field( $_POST['username'] ) : '';
    if (!$clientid || !$clientsecret || !$username) {
        wp_send_json_error('All fields are required.');
    }
    $body = json_encode([
        'clientid' => $clientid,
        'clientsecret' => $clientsecret,
        'username' => $username
    ]);
    $response = wp_remote_post('http://api.stocknetwork.co.za/api/1.0/token', [
        'headers' => [
            'Content-Type' => 'application/json'
        ],
        'body' => $body,
        'timeout' => 15
    ]);
    if (is_wp_error($response)) {
        wp_send_json_error('Request failed.');
    }
    $code = wp_remote_retrieve_response_code($response);
    $data = json_decode(wp_remote_retrieve_body($response), true);
    if ($code === 200 && !empty($data['accessToken'])) {
        update_option('avante_travel_auth_token', $data['accessToken']);
        wp_send_json_success(['token' => $data['accessToken']]);
    } else {
        $error_body_full = wp_remote_retrieve_body($response);
        $msg_detail = !empty($data['message']) ? $data['message'] : 'Invalid response.';
        $msg = sprintf('%s (HTTP %d) %s', $msg_detail, $code, substr($error_body_full, 0, 300));
        wp_send_json_error($msg);
    }
}