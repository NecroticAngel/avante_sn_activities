<?php
declare(strict_types=1);

session_start();

require dirname(__DIR__) . '/api/bookings-store.php';
require dirname(__DIR__) . '/api/audit-store.php';
require dirname(__DIR__) . '/includes/admin-auth.php';

$configPath = dirname(__DIR__) . '/config.php';
$config = is_file($configPath) ? require $configPath : [];
$adminPassword = (string) ($config['admin_password'] ?? '');
avante_admin_handle_auth($adminPassword, 'index.php');

if (!avante_admin_logged_in()) {
    header('Location: index.php');
    exit;
}

$reference = trim((string) ($_GET['ref'] ?? ''));
$record = avante_find_accommodation_by_reference($reference);
if (!$record) {
    http_response_code(404);
    exit('Booking not found.');
}

$reservationId = trim((string) ($record['reservationId'] ?? ''));
[, $paymentUrl] = avante_sn_portal_urls($reservationId, '', (string) ($record['paymentUrl'] ?? ''));
$parts = parse_url($paymentUrl);
$host = strtolower((string) ($parts['host'] ?? ''));
if (($parts['scheme'] ?? '') !== 'https' || ($host !== 'stock.stocknetwork.co.za' && !str_ends_with($host, '.stocknetwork.co.za'))) {
    http_response_code(400);
    exit('The payment link is not valid.');
}

avante_audit_log('payment_portal_opened', [
    'reservationId' => $reservationId,
    'reservationRefNo' => (string) ($record['reservationRefNo'] ?? ''),
    'reservationStatus' => (string) ($record['reservationStatus'] ?? ''),
    'paymentUrl' => $paymentUrl,
]);

header('Cache-Control: no-store');
header('Location: ' . $paymentUrl, true, 302);
exit;
