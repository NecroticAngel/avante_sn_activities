<?php
declare(strict_types=1);

function avante_settings_path(): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'settings.json';
}

function avante_settings_defaults(): array
{
    return [
        'catch_all_emails' => 'liqqquid.ideas@gmail.com',
    ];
}

function avante_settings_load(): array
{
    $path = avante_settings_path();
    $settings = avante_settings_defaults();
    if (is_file($path)) {
        $data = json_decode((string) file_get_contents($path), true);
        if (is_array($data)) {
            $settings = array_merge($settings, $data);
        }
    }
    $settings['catch_all_emails'] = trim((string) ($settings['catch_all_emails'] ?? ''));
    return $settings;
}

function avante_settings_save(array $input): array
{
    $raw = trim((string) ($input['catch_all_emails'] ?? ''));
    $emails = avante_parse_email_list($raw);
    if ($raw !== '' && !$emails) {
        throw new RuntimeException('Enter at least one valid email address, or leave the catch-all blank.');
    }
    $settings = [
        'catch_all_emails' => implode(', ', $emails),
    ];
    $dir = dirname(avante_settings_path());
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    file_put_contents(
        avante_settings_path(),
        json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n"
    );
    return $settings;
}

function avante_catch_all_raw(?array $config = null): string
{
    $settings = avante_settings_load();
    $fromSettings = trim((string) ($settings['catch_all_emails'] ?? ''));
    if ($fromSettings !== '') {
        return $fromSettings;
    }
    if (is_array($config)) {
        return trim((string) ($config['avante_booking_email'] ?? ''));
    }
    return '';
}

function avante_catch_all_emails(?array $config = null): array
{
    return avante_parse_email_list(avante_catch_all_raw($config));
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
