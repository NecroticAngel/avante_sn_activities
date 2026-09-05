<?php
/**
 * Copy this file to config.php and paste your Stock Network bearer token.
 * config.php is gitignored — do not commit a live token.
 */
return [
    'auth_token' => '',
    'min_price' => 0.0,
    'main_color' => '#0dcdc2',
    'secondary_color' => '#172d72',
    'form_bg_color' => '#ffffff',
    'button_color' => '#0dcdc2',
    'hide_more_filters' => false,
    'api_base' => 'https://api.stocknetwork.co.za/api/2.0',
    'admin_password' => 'change-me',
    'avante_booking_email' => '',
];
// Catch-all booking copies are configured in admin Settings.
// avante_booking_email is only a fallback if Settings is empty.
