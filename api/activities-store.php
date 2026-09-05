<?php
declare(strict_types=1);

function avante_activities_path(): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'activities.json';
}

function avante_activity_categories(): array
{
    return ['Adventure', 'Golf', 'Padel', 'Wine', 'Trails', 'Experiences'];
}

function avante_activity_public(array $item): array
{
    unset($item['booking_email']);
    return $item;
}

function avante_activities_all(): array
{
    $path = avante_activities_path();
    if (!is_file($path)) {
        return [];
    }
    $data = json_decode((string) file_get_contents($path), true);
    if (!is_array($data)) {
        return [];
    }
    foreach ($data as &$item) {
        if (!is_array($item)) {
            continue;
        }
        if (empty($item['booking_email']) && !empty($item['email'])) {
            $item['booking_email'] = $item['email'];
        }
    }
    unset($item);
    return array_values($data);
}

function avante_activities_published(): array
{
    $published = array_filter(avante_activities_all(), static function ($item) {
        return is_array($item) && !empty($item['published']);
    });
    return array_values(array_map('avante_activity_public', $published));
}

function avante_activity_find(string $id): ?array
{
    foreach (avante_activities_all() as $item) {
        if (($item['id'] ?? '') === $id) {
            return $item;
        }
    }
    return null;
}

function avante_activity_slug(string $company, string $name, string $excludeId = ''): string
{
    $base = strtolower(trim($company . '-' . $name));
    $base = preg_replace('/[^a-z0-9]+/', '-', $base) ?? 'activity';
    $base = trim($base, '-');
    if ($base === '') {
        $base = 'activity';
    }
    $base = substr($base, 0, 80);
    $slug = $base;
    $n = 2;
    $existing = [];
    foreach (avante_activities_all() as $item) {
        $id = (string) ($item['id'] ?? '');
        if ($id !== '' && $id !== $excludeId) {
            $existing[$id] = true;
        }
    }
    while (isset($existing[$slug])) {
        $slug = $base . '-' . $n;
        $n++;
    }
    return $slug;
}

function avante_activities_write(array $items): void
{
    $dir = dirname(avante_activities_path());
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    $json = json_encode(array_values($items), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        throw new RuntimeException('Could not encode activities.');
    }
    file_put_contents(avante_activities_path(), $json . "\n");
}

function avante_parse_price(string $raw): array
{
    $raw = trim($raw);
    if ($raw === '') {
        return ['price_label' => 'Variable', 'price_numeric' => null];
    }
    if (preg_match('/variable/i', $raw)) {
        return ['price_label' => 'Variable', 'price_numeric' => null];
    }
    $digits = preg_replace('/[^0-9.]/', '', $raw);
    if ($digits === '' || !is_numeric($digits)) {
        return ['price_label' => $raw, 'price_numeric' => null];
    }
    $num = (float) $digits;
    $label = $num == floor($num) ? ('R ' . number_format($num, 0)) : ('R ' . number_format($num, 2));
    return ['price_label' => $label, 'price_numeric' => $num];
}

function avante_activity_from_input(array $input, ?array $existing = null): array
{
    $company = trim((string) ($input['company'] ?? ''));
    $name = trim((string) ($input['name'] ?? ''));
    if ($company === '' || $name === '') {
        throw new RuntimeException('Company and activity name are required.');
    }

    $price = avante_parse_price((string) ($input['price_label'] ?? ''));
    $existingId = (string) ($existing['id'] ?? '');
    $id = $existingId !== '' ? $existingId : avante_activity_slug($company, $name);

    $lat = trim((string) ($input['lat'] ?? ''));
    $lng = trim((string) ($input['lng'] ?? ''));
    $category = trim((string) ($input['category'] ?? 'Experiences'));
    if (!in_array($category, avante_activity_categories(), true)) {
        $category = 'Experiences';
    }

    return [
        'id' => $id,
        'company' => $company,
        'name' => $name,
        'category' => $category,
        'price_label' => $price['price_label'],
        'price_numeric' => $price['price_numeric'],
        'phone' => trim((string) ($input['phone'] ?? '')),
        'email' => trim((string) ($input['email'] ?? '')),
        'booking_email' => trim((string) ($input['booking_email'] ?? '')) ?: trim((string) ($input['email'] ?? '')),
        'booking_url' => trim((string) ($input['booking_url'] ?? '')),
        'lat' => $lat === '' ? null : (float) $lat,
        'lng' => $lng === '' ? null : (float) $lng,
        'image_url' => trim((string) ($input['image_url'] ?? '')),
        'location' => trim((string) ($input['location'] ?? 'Plettenberg Bay')) ?: 'Plettenberg Bay',
        'description' => trim((string) ($input['description'] ?? '')),
        'published' => !empty($input['published']),
    ];
}

function avante_activity_upsert(array $activity): array
{
    $items = avante_activities_all();
    $found = false;
    foreach ($items as $i => $item) {
        if (($item['id'] ?? '') === $activity['id']) {
            $items[$i] = $activity;
            $found = true;
            break;
        }
    }
    if (!$found) {
        $items[] = $activity;
    }
    avante_activities_write($items);
    return $activity;
}

function avante_activity_delete(string $id): void
{
    $items = array_values(array_filter(avante_activities_all(), static function ($item) use ($id) {
        return ($item['id'] ?? '') !== $id;
    }));
    avante_activities_write($items);
}

if (!function_exists('avante_parse_email_list')) {
    function avante_parse_email_list(string $raw): array
    {
        $emails = [];
        foreach (preg_split('/[,;]+/', $raw) as $part) {
            $email = filter_var(trim($part), FILTER_VALIDATE_EMAIL);
            if ($email) {
                $emails[] = $email;
            }
        }
        return array_values(array_unique($emails));
    }
}

function avante_handle_activity_booking(array $config, ?array $input = null): array
{
    if ($input === null) {
        $input = $_POST;
        if (empty($input['activity_id'])) {
            $input = array_merge($input, function_exists('avante_json_body') ? avante_json_body() : []);
        }
    }

    $activityId = trim((string) ($input['activity_id'] ?? ''));
    $fullName = trim((string) ($input['full_name'] ?? ''));
    $email = trim((string) ($input['email'] ?? ''));
    $phone = trim((string) ($input['phone'] ?? ''));
    $preferredDate = trim((string) ($input['preferred_date'] ?? ''));
    $people = trim((string) ($input['people'] ?? ''));
    $message = trim((string) ($input['message'] ?? ''));

    if ($activityId === '' || $fullName === '' || $email === '' || $preferredDate === '' || $people === '') {
        throw new RuntimeException('Name, email, preferred date, and number of people are required.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Please enter a valid email address.');
    }

    $activity = avante_activity_find($activityId);
    if (!$activity || empty($activity['published'])) {
        throw new RuntimeException('That activity is not available to book.');
    }

    require_once __DIR__ . '/settings-store.php';
    $recipients = avante_parse_email_list((string) ($activity['booking_email'] ?? ''));
    if (!$recipients) {
        $recipients = avante_parse_email_list((string) ($activity['email'] ?? ''));
    }
    $recipients = array_merge($recipients, avante_catch_all_emails($config));
    $recipients = array_values(array_unique($recipients));
    if (!$recipients) {
        throw new RuntimeException('No booking email is set for this activity or Avante yet. Add a catch-all in admin Settings.');
    }

    $record = [
        'at' => date('c'),
        'reference' => 'ACT-' . strtoupper(bin2hex(random_bytes(4))),
        'activity_id' => $activityId,
        'activity' => $activity['name'] ?? '',
        'company' => $activity['company'] ?? '',
        'price' => $activity['price_label'] ?? '',
        'full_name' => $fullName,
        'email' => $email,
        'phone' => $phone,
        'preferred_date' => $preferredDate,
        'people' => $people,
        'message' => $message,
        'recipients' => $recipients,
    ];

    $logPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'activity-bookings.jsonl';
    file_put_contents($logPath, json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);

    $subject = 'Activity booking request — ' . ($activity['name'] ?? 'Activity');
    $body = "New activity booking request from the Avante site.\n\n";
    $body .= "ACTIVITY\n";
    $body .= 'Activity: ' . ($activity['name'] ?? '') . "\n";
    $body .= 'Operator: ' . ($activity['company'] ?? '') . "\n";
    $body .= 'Price: ' . ($activity['price_label'] ?? '') . "\n";
    $body .= 'Location: ' . ($activity['location'] ?? '') . "\n\n";
    $body .= "GUEST\n";
    $body .= 'Name: ' . $fullName . "\n";
    $body .= 'Email: ' . $email . "\n";
    $body .= 'Phone: ' . ($phone !== '' ? $phone : 'Not provided') . "\n";
    $body .= 'Preferred date: ' . $preferredDate . "\n";
    $body .= 'Number of people: ' . $people . "\n";
    if ($message !== '') {
        $body .= "\nMESSAGE\n" . $message . "\n";
    }
    $body .= "\nPlease reply to the guest to confirm the booking.";

    $host = preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'From: Avante Travel <noreply@' . $host . '>',
        'Reply-To: ' . $email,
    ];
    $sent = avante_send_mail(implode(', ', $recipients), $subject, $body, implode("\r\n", $headers));

    return ['ok' => true, 'emailed' => $sent, 'reference' => $record['reference']];
}

function avante_send_mail(string $to, string $subject, string $body, string $headers): bool
{
    $smtp = (string) (ini_get('SMTP') ?: 'localhost');
    $port = (int) (ini_get('smtp_port') ?: 25);
    $probe = @fsockopen($smtp, $port, $errno, $errstr, 2);
    if ($probe === false) {
        return false;
    }
    fclose($probe);
    return @mail($to, $subject, $body, $headers);
}

