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

function avante_admin_bookings(): array
{
    $items = [];

    foreach (avante_read_jsonl(avante_jsonl_path('accommodation-bookings.jsonl')) as $row) {
        $stay = is_array($row['stay'] ?? null) ? $row['stay'] : [];
        $guest = is_array($row['guest'] ?? null) ? $row['guest'] : [];
        $reservationId = (string) ($row['reservationId'] ?? '');
        $infoUrl = (string) ($row['reservationInformationUrl'] ?? '');
        if ($infoUrl === '' && $reservationId !== '') {
            $infoUrl = 'https://stock.stocknetwork.co.za/ui/reservationinfo/' . rawurlencode($reservationId);
        }
        $paymentUrl = (string) ($row['paymentUrl'] ?? '');
        if ($paymentUrl === '' && $reservationId !== '') {
            $paymentUrl = 'https://stock.stocknetwork.co.za/ui/reservation/' . rawurlencode($reservationId);
        }
        $checkIn = avante_booking_day((string) ($stay['checkInDate'] ?? ''));
        $checkOut = avante_booking_day((string) ($stay['checkOutDate'] ?? ''));
        $dates = trim($checkIn . ($checkOut !== '' ? ' – ' . $checkOut : ''));
        $items[] = [
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
        ];
    }

    foreach (avante_read_jsonl(avante_jsonl_path('activity-bookings.jsonl')) as $row) {
        $recipients = $row['recipients'] ?? [];
        $items[] = [
            'type' => 'Activity',
            'at' => (string) ($row['at'] ?? ''),
            'title' => (string) ($row['activity'] ?? 'Activity'),
            'detail' => trim((string) ($row['company'] ?? '') . (!empty($row['preferred_date']) ? ' · ' . avante_booking_day((string) $row['preferred_date']) : '') . (!empty($row['people']) ? ' · ' . $row['people'] . ' people' : '')),
            'guest_name' => (string) ($row['full_name'] ?? ''),
            'guest_email' => (string) ($row['email'] ?? ''),
            'guest_phone' => (string) ($row['phone'] ?? ''),
            'amount' => (string) ($row['price'] ?? ''),
            'reference' => is_array($recipients) ? implode(', ', $recipients) : '',
            'status' => 'Request',
            'notes' => (string) ($row['message'] ?? ''),
            'info_url' => '',
            'payment_url' => '',
        ];
    }

    usort($items, static function (array $a, array $b): int {
        return strcmp((string) ($b['at'] ?? ''), (string) ($a['at'] ?? ''));
    });

    return $items;
}
