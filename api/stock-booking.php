<?php
declare(strict_types=1);

function avante_api_v1_base(array $config): string
{
    $base = avante_api_base($config);
    if (str_ends_with($base, '/2.0')) {
        return substr($base, 0, -4) . '/1.0';
    }
    if (str_ends_with($base, '/2')) {
        return substr($base, 0, -2) . '/1.0';
    }
    return 'https://api.stocknetwork.co.za/api/1.0';
}

function avante_sn_message($data, string $fallback): string
{
    if (!is_array($data)) {
        return $fallback;
    }
    foreach (['message', 'error_message', 'errorMessage', 'title'] as $key) {
        if (!empty($data[$key]) && is_string($data[$key])) {
            return $data[$key];
        }
    }
    if (!empty($data['error']) && is_array($data['error']) && !empty($data['error']['message'])) {
        return (string) $data['error']['message'];
    }
    if (!empty($data['errorItems'][0]['errorReason'])) {
        return (string) $data['errorItems'][0]['errorReason'];
    }
    return $fallback;
}

function avante_sn_datetime(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return $value . 'T00:00:00';
    }
    if (preg_match('/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2})/', $value, $m)) {
        return $m[1];
    }
    return $value;
}

function avante_sn_nights(string $checkIn, string $checkOut, int $fallback = 1): int
{
    try {
        $start = new DateTime(substr($checkIn, 0, 10));
        $end = new DateTime(substr($checkOut, 0, 10));
        $nights = (int) $start->diff($end)->days;
        return $nights > 0 ? $nights : $fallback;
    } catch (Exception $e) {
        return $fallback;
    }
}

function avante_sn_post(array $config, string $path, array $payload): array
{
    $url = avante_api_v1_base($config) . $path;
    $response = avante_http_request(
        'POST',
        $url,
        avante_api_headers($config),
        json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        120
    );
    $data = json_decode($response['body'], true);
    if ($response['status'] < 200 || $response['status'] >= 300) {
        throw new RuntimeException(
            'Stock Network error (HTTP ' . $response['status'] . '): ' .
            substr(avante_sn_message($data, (string) $response['body']), 0, 400)
        );
    }
    if (!is_array($data)) {
        throw new RuntimeException('Invalid Stock Network response.');
    }
    return $data;
}

function avante_fetch_additional_info(array $config, string $resortId, string $roomId, string $checkIn, string $checkOut): array
{
    if ($resortId === '' || $roomId === '') {
        throw new RuntimeException('Resort and unit identifiers are required for extra booking information.');
    }
    return avante_sn_post($config, '/request/additionalinformation', [
        'resortId' => $resortId,
        'roomId' => $roomId,
        'checkInDate' => avante_sn_datetime($checkIn),
        'checkOutDate' => avante_sn_datetime($checkOut),
    ]);
}

function avante_handle_additional_info(array $config): array
{
    $input = avante_json_body();
    $info = avante_fetch_additional_info(
        $config,
        trim((string) ($input['resortId'] ?? '')),
        trim((string) ($input['roomId'] ?? '')),
        trim((string) ($input['checkInDate'] ?? '')),
        trim((string) ($input['checkOutDate'] ?? ''))
    );
    return ['ok' => true, 'info' => $info];
}

function avante_enforce_occupancy(array $info, int $adults, int $children, int $nights): void
{
    if ($info && array_key_exists('reservationCanContinue', $info) && empty($info['reservationCanContinue'])) {
        throw new RuntimeException(trim((string) ($info['errorMessage'] ?? '')) ?: 'This unit cannot be booked for those dates.');
    }

    $maxOccupancy = (int) ($info['maxOccupancy'] ?? 0);
    $maxAdults = (int) ($info['maxAdults'] ?? 0);
    $minLos = (int) ($info['minLOS'] ?? 0);
    $policy = is_array($info['childPolicy'] ?? null) ? $info['childPolicy'] : [];

    if ($maxOccupancy > 0 && ($adults + $children) > $maxOccupancy) {
        throw new RuntimeException('This unit sleeps a maximum of ' . $maxOccupancy . ' guests.');
    }
    if ($maxAdults > 0 && $adults > $maxAdults) {
        throw new RuntimeException('This unit allows a maximum of ' . $maxAdults . ' adults.');
    }
    if ($minLos > 1 && $nights > 0 && $nights < $minLos) {
        throw new RuntimeException('This unit requires a minimum stay of ' . $minLos . ' nights.');
    }
    if (array_key_exists('childrenAllowed', $policy) && empty($policy['childrenAllowed']) && $children > 0) {
        throw new RuntimeException('Children are not allowed in this unit.');
    }
}

function avante_default_meal_plan_id(array $info, $selected)
{
    if ($selected !== null && $selected !== '') {
        return $selected;
    }
    $plans = $info['mealPlans'] ?? [];
    if (!is_array($plans)) {
        return null;
    }
    foreach ($plans as $plan) {
        if (!is_array($plan)) {
            continue;
        }
        if (!empty($plan['isDefaultMealPlan']) && isset($plan['mealPlanRateId']) && $plan['mealPlanRateId'] !== '') {
            return $plan['mealPlanRateId'];
        }
    }
    return null;
}

function avante_handle_accommodation_booking(array $config, ?array $input = null): array
{
    if ($input === null) {
        $input = avante_json_body();
    }

    $fullName = trim((string) ($input['fullName'] ?? $input['full_name'] ?? ''));
    $email = trim((string) ($input['emailAddress'] ?? $input['email'] ?? ''));
    $phone = trim((string) ($input['cellphone'] ?? $input['phone'] ?? ''));
    $notes = trim((string) ($input['notes'] ?? $input['message'] ?? ''));
    $adults = (int) ($input['adultOccupancy'] ?? $input['adults'] ?? 0);
    $children = (int) ($input['childOccupancy'] ?? $input['children'] ?? 0);
    $childAges = $input['childAges'] ?? [];
    if (!is_array($childAges)) {
        $childAges = [];
    }
    $childAges = array_values(array_map('intval', $childAges));

    $resortId = trim((string) ($input['resortId'] ?? ''));
    $resortName = trim((string) ($input['resortName'] ?? ''));
    $unitName = trim((string) ($input['unitName'] ?? ''));
    $unitNameId = trim((string) ($input['unitNameId'] ?? ''));
    $unitSize = trim((string) ($input['unitSize'] ?? ''));
    $unitSizeTypeId = trim((string) ($input['unitSizeTypeId'] ?? ''));
    $uniqueStockId = trim((string) ($input['uniqueStockId'] ?? ''));
    $reservationRateId = trim((string) ($input['reservationRateId'] ?? $uniqueStockId));
    $source = trim((string) ($input['source'] ?? ''));
    $checkIn = trim((string) ($input['checkInDate'] ?? ''));
    $checkOut = trim((string) ($input['checkOutDate'] ?? ''));
    $nights = (int) ($input['numberOfNights'] ?? 0);
    $amountIncl = (float) ($input['amountIncl'] ?? 0);
    $currency = trim((string) ($input['currency'] ?? 'ZAR')) ?: 'ZAR';
    $currencySymbol = trim((string) ($input['currencySymbol'] ?? 'R')) ?: 'R';
    $needsExtra = !empty($input['requiresAdditionalInformation']);

    if ($fullName === '' || $email === '' || $phone === '' || $checkIn === '' || $checkOut === '') {
        throw new RuntimeException('Name, email, phone, check-in, and check-out are required.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Please enter a valid email address.');
    }
    if ($adults < 1) {
        throw new RuntimeException('Please choose how many adults are staying.');
    }
    if ($uniqueStockId === '' || $reservationRateId === '' || $resortName === '') {
        throw new RuntimeException('That unit is missing Stock Network booking identifiers. Search again and try another unit.');
    }
    if ($children !== count($childAges)) {
        throw new RuntimeException('Please enter an age for each child.');
    }
    if ($nights < 1) {
        $nights = avante_sn_nights($checkIn, $checkOut, 1);
    }

    $info = [];
    if ($needsExtra) {
        $info = avante_fetch_additional_info($config, $resortId, $unitNameId, $checkIn, $checkOut);
        avante_enforce_occupancy($info, $adults, $children, $nights);
    }

    $stay = [
        'uniqueStockId' => $uniqueStockId,
        'reservationRateId' => $reservationRateId,
        'resortId' => $resortId,
        'resortName' => $resortName,
        'unitSizeTypeId' => $unitSizeTypeId,
        'unitSize' => $unitSize,
        'unitNameId' => $unitNameId,
        'unitName' => $unitName,
        'checkInDate' => avante_sn_datetime($checkIn),
        'checkOutDate' => avante_sn_datetime($checkOut),
        'numberOfNights' => $nights,
        'noOfUnits' => 1,
        'amountIncl' => $amountIncl,
        'source' => $source,
        'adultOccupancy' => $adults,
        'childOccupancy' => $children,
        'childAges' => $childAges,
    ];

    $sharing = $input['sharingStockSourceId'] ?? $input['sharedStockSourceId'] ?? null;
    if ($sharing !== null && $sharing !== '') {
        $stay['sharingStockSourceId'] = (int) $sharing;
    }

    $mealRatePlanId = avante_default_meal_plan_id($info, $input['mealRatePlanId'] ?? null);
    if ($mealRatePlanId !== null) {
        $stay['mealRatePlanId'] = $mealRatePlanId;
    }

    $payload = [
        'amountIncl' => $amountIncl,
        'fullName' => $fullName,
        'emailAddress' => $email,
        'cellphone' => $phone,
        'currency' => $currency,
        'currencySymbol' => $currencySymbol,
        'deleteCustomerInfoAfterCheckout' => false,
        'notes' => $notes,
        'preferredContactMethod' => 'Email',
        'discount' => 0.0,
        'isRequestAndPay' => false,
        'items' => [$stay],
    ];
    $membershipNo = trim((string) ($input['membershipNo'] ?? $input['voucherCode'] ?? ''));
    if ($membershipNo !== '') {
        $payload['membershipNo'] = $membershipNo;
    }

    $result = avante_sn_post($config, '/request', $payload);
    $status = (string) ($result['status'] ?? '');
    if ($status === 'Error' || $status === 'NoReservationLineItems' || !empty($result['errorItems'])) {
        throw new RuntimeException(avante_sn_message($result, 'Stock Network could not complete this booking.'));
    }

    $record = [
        'at' => date('c'),
        'guest' => [
            'fullName' => $fullName,
            'email' => $email,
            'phone' => $phone,
        ],
        'stay' => [
            'resortName' => $resortName,
            'unitName' => $unitName,
            'checkInDate' => $checkIn,
            'checkOutDate' => $checkOut,
            'amountIncl' => $amountIncl,
            'adults' => $adults,
            'children' => $children,
        ],
        'reservationId' => $result['reservationId'] ?? null,
        'reservationRefNo' => $result['reservationRefNo'] ?? null,
        'reservationStatus' => $result['reservationStatus'] ?? null,
        'status' => $status,
        'paymentUrl' => $result['paymentUrl'] ?? null,
        'reservationInformationUrl' => $result['reservationInformationUrl'] ?? null,
        'notes' => $notes,
        'membershipNo' => $membershipNo,
    ];

    $logPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'accommodation-bookings.jsonl';
    file_put_contents($logPath, json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);

    require_once __DIR__ . '/settings-store.php';
    require_once __DIR__ . '/activities-store.php';
    $recipients = avante_catch_all_emails($config);
    if ($recipients) {
        $subject = 'Accommodation booking — ' . ($resortName !== '' ? $resortName : 'Stock Network');
        $body = "A booking request was sent to Stock Network from the Avante site.\n\n";
        $body .= 'Reference: ' . ($result['reservationRefNo'] ?? 'n/a') . "\n";
        $body .= 'Status: ' . ($result['reservationStatus'] ?? $status) . "\n";
        $body .= 'Resort: ' . $resortName . "\n";
        $body .= 'Unit: ' . $unitName . "\n";
        $body .= 'Dates: ' . $checkIn . ' to ' . $checkOut . "\n";
        $body .= 'Guest: ' . $fullName . ' / ' . $email . ' / ' . $phone . "\n";
        avante_send_mail(implode(', ', $recipients), $subject, $body, "Content-Type: text/plain; charset=UTF-8\r\nFrom: Avante Travel <noreply@localhost>");
    }

    return [
        'ok' => true,
        'status' => $status,
        'reservationId' => $result['reservationId'] ?? null,
        'reservationRefNo' => $result['reservationRefNo'] ?? null,
        'reservationStatus' => $result['reservationStatus'] ?? null,
        'paymentUrl' => $result['paymentUrl'] ?? null,
        'reservationInformationUrl' => $result['reservationInformationUrl'] ?? null,
        'totalAmountInclAfterDiscount' => $result['totalAmountInclAfterDiscount'] ?? $result['totalAmountIncl'] ?? $amountIncl,
        'successfulItems' => $result['successfulItems'] ?? [],
    ];
}
