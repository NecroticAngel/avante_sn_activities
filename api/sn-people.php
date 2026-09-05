<?php
declare(strict_types=1);

function avante_sn_get(array $config, string $path): array
{
    $url = avante_api_v1_base($config) . $path;
    $response = avante_http_request('GET', $url, avante_api_headers($config), null, 60);
    $data = json_decode($response['body'], true);
    if ($response['status'] < 200 || $response['status'] >= 300) {
        throw new RuntimeException(
            'Stock Network error (HTTP ' . $response['status'] . '): ' .
            substr(function_exists('avante_sn_message') ? avante_sn_message($data, (string) $response['body']) : (string) $response['body'], 0, 400)
        );
    }
    if (!is_array($data)) {
        throw new RuntimeException('Invalid Stock Network response.');
    }
    return $data;
}

function avante_handle_validate_member(array $config): array
{
    $input = function_exists('avante_json_body') ? avante_json_body() : [];
    $code = trim((string) ($input['membershipNo'] ?? $_POST['membershipNo'] ?? $_GET['membershipNo'] ?? ''));
    if ($code === '') {
        throw new RuntimeException('Enter a membership number.');
    }
    $person = avante_sn_get($config, '/person/membership/' . rawurlencode($code));
    return ['ok' => true, 'member' => $person];
}

function avante_handle_validate_voucher(array $config): array
{
    $input = function_exists('avante_json_body') ? avante_json_body() : [];
    $code = trim((string) ($input['voucherCode'] ?? $_POST['voucherCode'] ?? $_GET['voucherCode'] ?? ''));
    if ($code === '') {
        throw new RuntimeException('Enter a voucher code.');
    }
    $voucher = avante_sn_get($config, '/persondiscount/voucher/' . rawurlencode($code));
    return ['ok' => true, 'voucher' => $voucher];
}

function avante_handle_upload_voucher(array $config): array
{
    $input = function_exists('avante_json_body') ? avante_json_body() : $_POST;
    $voucher = [
        'code' => trim((string) ($input['code'] ?? '')),
        'amount' => (float) ($input['amount'] ?? 0),
        'fullName' => trim((string) ($input['fullName'] ?? '')),
        'emailAddress' => trim((string) ($input['emailAddress'] ?? '')),
        'cellphone' => trim((string) ($input['cellphone'] ?? '')),
        'isActive' => true,
    ];
    if ($voucher['code'] === '' || $voucher['fullName'] === '' || $voucher['emailAddress'] === '' || $voucher['cellphone'] === '') {
        throw new RuntimeException('Voucher code, name, email, and cellphone are required.');
    }
    $result = avante_sn_post($config, '/persondiscount/voucher', [$voucher]);
    return ['ok' => true, 'result' => $result];
}

function avante_handle_upload_member(array $config): array
{
    $input = function_exists('avante_json_body') ? avante_json_body() : $_POST;
    $member = [
        'membershipNumber' => trim((string) ($input['membershipNumber'] ?? '')),
        'fullName' => trim((string) ($input['fullName'] ?? '')),
        'emailAddress' => trim((string) ($input['emailAddress'] ?? '')),
        'cellphone' => trim((string) ($input['cellphone'] ?? '')),
        'isActive' => true,
        'membershipType' => trim((string) ($input['membershipType'] ?? 'Standard')) ?: 'Standard',
    ];
    if ($member['membershipNumber'] === '' || $member['fullName'] === '' || $member['emailAddress'] === '' || $member['cellphone'] === '') {
        throw new RuntimeException('Membership number, name, email, and cellphone are required.');
    }
    $result = avante_sn_post($config, '/persondiscount/member', [$member]);
    return ['ok' => true, 'result' => $result];
}
