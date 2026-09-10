<?php
declare(strict_types=1);

function avante_audit_redact($value, string $key = '')
{
    $blockedKeys = ['authorization', 'authtoken', 'password', 'card', 'cvv', 'cvc', 'expiry', 'securitycode'];
    $normalizedKey = strtolower(preg_replace('/[^a-z0-9]/i', '', $key));
    foreach ($blockedKeys as $blockedKey) {
        if ($normalizedKey !== '' && str_contains($normalizedKey, $blockedKey)) {
            return '[redacted]';
        }
    }
    if (!is_array($value)) {
        return is_string($value) && strlen($value) > 4000 ? substr($value, 0, 4000) . '…' : $value;
    }
    $clean = [];
    foreach ($value as $childKey => $childValue) {
        $clean[$childKey] = avante_audit_redact($childValue, (string) $childKey);
    }
    return $clean;
}

function avante_audit_log(string $event, array $context = []): void
{
    $record = [
        'at' => date('c'),
        'event' => $event,
        'context' => avante_audit_redact($context),
    ];
    $encoded = json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    if ($encoded === false) {
        throw new RuntimeException('Could not encode the audit record.');
    }
    $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'booking-audit.jsonl';
    if (file_put_contents($path, $encoded . "\n", FILE_APPEND | LOCK_EX) === false) {
        throw new RuntimeException('Could not write the booking audit log.');
    }
}

function avante_booking_audit_events(string $reference, string $reservationId = ''): array
{
    $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'booking-audit.jsonl';
    if (!is_file($path)) {
        return [];
    }
    $events = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $event = json_decode($line, true);
        $context = is_array($event['context'] ?? null) ? $event['context'] : [];
        if (($reference !== '' && (string) ($context['reservationRefNo'] ?? '') === $reference)
            || ($reservationId !== '' && (string) ($context['reservationId'] ?? '') === $reservationId)) {
            $events[] = $event;
        }
    }
    return $events;
}
