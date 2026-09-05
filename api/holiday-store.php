<?php
declare(strict_types=1);

function avante_handle_holiday_checkout(array $config): array
{
    require_once __DIR__ . '/activities-store.php';
    require_once __DIR__ . '/stock-booking.php';

    $body = function_exists('avante_json_body') ? avante_json_body() : [];
    $guest = is_array($body['guest'] ?? null) ? $body['guest'] : [];
    $stay = is_array($body['stay'] ?? null) ? $body['stay'] : null;
    $activities = is_array($body['activities'] ?? null) ? $body['activities'] : [];

    $fullName = trim((string) ($guest['fullName'] ?? $guest['full_name'] ?? ''));
    $email = trim((string) ($guest['email'] ?? $guest['emailAddress'] ?? ''));
    $phone = trim((string) ($guest['phone'] ?? $guest['cellphone'] ?? ''));
    $notes = trim((string) ($guest['notes'] ?? $guest['message'] ?? ''));
    $adults = (int) ($guest['adults'] ?? $guest['adultOccupancy'] ?? 2);
    $children = (int) ($guest['children'] ?? $guest['childOccupancy'] ?? 0);
    $childAges = $guest['childAges'] ?? [];
    $people = max(1, $adults + $children);
    $activityDate = trim((string) ($guest['activityDate'] ?? $guest['preferred_date'] ?? ''));
    $stayCheckIn = is_array($stay) ? trim((string) ($stay['checkInDate'] ?? '')) : '';

    if ($fullName === '' || $email === '' || $phone === '') {
        throw new RuntimeException('Name, email, and phone are required to check out.');
    }
    if (!$stay && !$activities) {
        throw new RuntimeException('Add a stay or at least one activity before checking out.');
    }

    $stayResult = null;
    $activityResults = [];
    $errors = [];

    if ($stay) {
        try {
            $stayResult = avante_handle_accommodation_booking($config, array_merge($stay, [
                'fullName' => $fullName,
                'emailAddress' => $email,
                'cellphone' => $phone,
                'notes' => $notes,
                'adultOccupancy' => $adults,
                'childOccupancy' => $children,
                'childAges' => $childAges,
                'membershipNo' => trim((string) ($guest['membershipNo'] ?? '')),
            ]));
        } catch (Throwable $e) {
            $errors[] = 'Stay: ' . $e->getMessage();
        }
    }

    foreach ($activities as $item) {
        if (!is_array($item) || empty($item['activity_id'])) {
            continue;
        }
        $label = (string) ($item['name'] ?? $item['activity_id']);
        try {
            $activityResults[] = avante_handle_activity_booking($config, [
                'activity_id' => $item['activity_id'],
                'full_name' => $fullName,
                'email' => $email,
                'phone' => $phone,
                'preferred_date' => $item['preferred_date'] ?? ($stayCheckIn !== '' ? $stayCheckIn : $activityDate),
                'people' => (string) ($item['people'] ?? $people),
                'message' => $notes,
            ]);
        } catch (Throwable $e) {
            $errors[] = $label . ': ' . $e->getMessage();
        }
    }

    if (!$stayResult && !$activityResults) {
        throw new RuntimeException($errors ? implode(' ', $errors) : 'Could not complete this holiday.');
    }

    $package = [
        'at' => date('c'),
        'reference' => 'HOL-' . strtoupper(bin2hex(random_bytes(4))),
        'guest' => [
            'fullName' => $fullName,
            'email' => $email,
            'phone' => $phone,
        ],
        'stayRef' => $stayResult['reservationRefNo'] ?? null,
        'activityRefs' => array_values(array_filter(array_map(static fn(array $row) => $row['reference'] ?? null, $activityResults))),
        'errors' => $errors,
    ];
    $logPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'holiday-packages.jsonl';
    file_put_contents($logPath, json_encode($package, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);

    return [
        'ok' => true,
        'packageRef' => $package['reference'],
        'stay' => $stayResult,
        'activities' => $activityResults,
        'errors' => $errors,
    ];
}
