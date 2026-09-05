<?php
declare(strict_types=1);

function avante_jsonl_path(string $name): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $name;
}

function avante_read_jsonl(string $path): array
{
    if (!is_file($path)) {
        return [];
    }
    $rows = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $row = json_decode($line, true);
        if (is_array($row)) {
            $rows[] = $row;
        }
    }
    return $rows;
}

function avante_booking_when(string $iso): string
{
    if ($iso === '') {
        return '';
    }
    try {
        return (new DateTime($iso))->setTimezone(new DateTimeZone('Africa/Johannesburg'))->format('j M Y, H:i');
    } catch (Exception $e) {
        return $iso;
    }
}

function avante_booking_day(string $iso): string
{
    if ($iso === '') {
        return '';
    }
    try {
        return (new DateTime($iso))->format('j M Y');
    } catch (Exception $e) {
        return $iso;
    }
}

function avante_activity_reference(array $row): string
{
    if (!empty($row['reference'])) {
        return strtoupper((string) $row['reference']);
    }
    return 'ACT-' . strtoupper(substr(sha1(($row['at'] ?? '') . '|' . ($row['email'] ?? '') . '|' . ($row['activity_id'] ?? '')), 0, 8));
}

function avante_sn_portal_urls(string $reservationId, string $infoUrl = '', string $paymentUrl = ''): array
{
    if ($infoUrl === '' && $reservationId !== '') {
        $infoUrl = 'https://stock.stocknetwork.co.za/ui/reservationinfo/' . rawurlencode($reservationId);
    }
    if ($paymentUrl === '' && $reservationId !== '') {
        $paymentUrl = 'https://stock.stocknetwork.co.za/ui/reservation/' . rawurlencode($reservationId);
    }
    return [$infoUrl, $paymentUrl];
}

function avante_normalize_accommodation(array $row): array
{
    $stay = is_array($row['stay'] ?? null) ? $row['stay'] : [];
    $guest = is_array($row['guest'] ?? null) ? $row['guest'] : [];
    $reservationId = (string) ($row['reservationId'] ?? '');
    [$infoUrl, $paymentUrl] = avante_sn_portal_urls(
        $reservationId,
        (string) ($row['reservationInformationUrl'] ?? ''),
        (string) ($row['paymentUrl'] ?? '')
    );
    $checkIn = avante_booking_day((string) ($stay['checkInDate'] ?? ''));
    $checkOut = avante_booking_day((string) ($stay['checkOutDate'] ?? ''));
    $dates = trim($checkIn . ($checkOut !== '' ? ' – ' . $checkOut : ''));
    return [
        'type' => 'Accommodation',
        'at' => (string) ($row['at'] ?? ''),
        'title' => (string) ($stay['resortName'] ?? 'Accommodation'),
        'detail' => trim((string) ($stay['unitName'] ?? '') . ($dates !== '' ? ' · ' . $dates : '')),
        'guest_name' => (string) ($guest['fullName'] ?? ''),
        'guest_email' => (string) ($guest['email'] ?? ''),
        'guest_phone' => (string) ($guest['phone'] ?? ''),
        'amount' => isset($stay['amountIncl']) ? 'R ' . $stay['amountIncl'] : '',
        'reference' => (string) ($row['reservationRefNo'] ?? ''),
        'status' => (string) ($row['reservationStatus'] ?? $row['status'] ?? ''),
        'notes' => (string) ($row['notes'] ?? $stay['notes'] ?? ''),
        'info_url' => $infoUrl,
        'payment_url' => $paymentUrl,
        'reservation_id' => $reservationId,
        'occupancy' => trim(($stay['adults'] ?? '') . ' adults' . (!empty($stay['children']) ? ', ' . $stay['children'] . ' children' : '')),
        'check_in' => $checkIn,
        'check_out' => $checkOut,
        'membership_no' => (string) ($row['membershipNo'] ?? ''),
    ];
}

function avante_normalize_activity(array $row): array
{
    $recipients = $row['recipients'] ?? [];
    $sentTo = is_array($recipients) ? implode(', ', $recipients) : '';
    return [
        'type' => 'Activity',
        'at' => (string) ($row['at'] ?? ''),
        'title' => (string) ($row['activity'] ?? 'Activity'),
        'detail' => trim((string) ($row['company'] ?? '') . (!empty($row['preferred_date']) ? ' · ' . avante_booking_day((string) $row['preferred_date']) : '') . (!empty($row['people']) ? ' · ' . $row['people'] . ' people' : '')),
        'guest_name' => (string) ($row['full_name'] ?? ''),
        'guest_email' => (string) ($row['email'] ?? ''),
        'guest_phone' => (string) ($row['phone'] ?? ''),
        'amount' => (string) ($row['price'] ?? ''),
        'reference' => avante_activity_reference($row),
        'status' => 'Request',
        'notes' => (string) ($row['message'] ?? ''),
        'info_url' => '',
        'payment_url' => '',
        'reservation_id' => '',
        'occupancy' => (string) ($row['people'] ?? ''),
        'check_in' => avante_booking_day((string) ($row['preferred_date'] ?? '')),
        'check_out' => '',
        'sent_to' => $sentTo,
        'membership_no' => '',
    ];
}

function avante_all_bookings(): array
{
    $items = [];
    foreach (avante_read_jsonl(avante_jsonl_path('accommodation-bookings.jsonl')) as $row) {
        $items[] = avante_normalize_accommodation($row);
    }
    foreach (avante_read_jsonl(avante_jsonl_path('activity-bookings.jsonl')) as $row) {
        $items[] = avante_normalize_activity($row);
    }
    usort($items, static function (array $a, array $b): int {
        return strcmp((string) ($b['at'] ?? ''), (string) ($a['at'] ?? ''));
    });
    return $items;
}

function avante_admin_bookings(): array
{
    return avante_all_bookings();
}

function avante_find_booking(string $reference, string $email): ?array
{
    $reference = strtoupper(trim($reference));
    $email = strtolower(trim($email));
    if ($reference === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return null;
    }
    foreach (avante_all_bookings() as $item) {
        if (strtoupper((string) $item['reference']) === $reference && strtolower((string) $item['guest_email']) === $email) {
            return $item;
        }
    }
    return null;
}

function avante_handle_booking_lookup(): array
{
    $input = function_exists('avante_json_body') ? avante_json_body() : [];
    $reference = trim((string) ($input['reference'] ?? $_POST['reference'] ?? $_GET['reference'] ?? ''));
    $email = trim((string) ($input['email'] ?? $_POST['email'] ?? $_GET['email'] ?? ''));
    $booking = avante_find_booking($reference, $email);
    if (!$booking) {
        throw new RuntimeException('No booking matched that reference and email.');
    }
    return ['ok' => true, 'booking' => $booking];
}
