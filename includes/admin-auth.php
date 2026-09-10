<?php
declare(strict_types=1);

const AVANTE_ADMIN_COOKIE = 'avante_admin_remember';
const AVANTE_ADMIN_REMEMBER_SECONDS = 2592000;

function avante_admin_cookie_options(int $expires): array
{
    $scriptDirectory = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/admin/index.php')));
    $cookiePath = $scriptDirectory === '.' ? '/admin' : '/' . trim($scriptDirectory, '/');
    return [
        'expires' => $expires,
        'path' => $cookiePath,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

function avante_admin_remember_signature(string $adminPassword, int $expires): string
{
    return hash_hmac('sha256', 'avante-admin|' . $expires, $adminPassword);
}

function avante_admin_set_remember_cookie(string $adminPassword): void
{
    $expires = time() + AVANTE_ADMIN_REMEMBER_SECONDS;
    $value = $expires . '.' . avante_admin_remember_signature($adminPassword, $expires);
    setcookie(AVANTE_ADMIN_COOKIE, $value, avante_admin_cookie_options($expires));
}

function avante_admin_clear_remember_cookie(): void
{
    setcookie(AVANTE_ADMIN_COOKIE, '', avante_admin_cookie_options(time() - 3600));
    unset($_COOKIE[AVANTE_ADMIN_COOKIE]);
}

function avante_admin_restore_session(string $adminPassword): void
{
    if ($adminPassword === '' || !empty($_SESSION['avante_admin'])) {
        return;
    }

    $cookie = (string) ($_COOKIE[AVANTE_ADMIN_COOKIE] ?? '');
    if (!preg_match('/^(\d{10})\.([a-f0-9]{64})$/', $cookie, $matches)) {
        if ($cookie !== '') {
            avante_admin_clear_remember_cookie();
        }
        return;
    }

    $expires = (int) $matches[1];
    $expected = avante_admin_remember_signature($adminPassword, $expires);
    if ($expires <= time() || !hash_equals($expected, $matches[2])) {
        avante_admin_clear_remember_cookie();
        return;
    }

    session_regenerate_id(true);
    $_SESSION['avante_admin'] = true;
}

function avante_admin_logged_in(): bool
{
    return !empty($_SESSION['avante_admin']);
}

function avante_admin_handle_auth(string $adminPassword, string $loginRedirect = ''): string
{
    avante_admin_restore_session($adminPassword);

    if (isset($_GET['logout'])) {
        avante_admin_clear_remember_cookie();
        $_SESSION = [];
        session_destroy();
        header('Location: index.php');
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['login'])) {
        return '';
    }

    $attempt = (string) ($_POST['password'] ?? '');
    if ($adminPassword === '') {
        return 'Set admin_password in config.php first.';
    }
    if (!hash_equals($adminPassword, $attempt)) {
        return 'That password is not right.';
    }

    session_regenerate_id(true);
    $_SESSION['avante_admin'] = true;
    if (!empty($_POST['remember_me'])) {
        avante_admin_set_remember_cookie($adminPassword);
    } else {
        avante_admin_clear_remember_cookie();
    }
    $redirect = $loginRedirect !== '' ? $loginRedirect : basename((string) ($_SERVER['PHP_SELF'] ?? 'index.php'));
    header('Location: ' . $redirect);
    exit;
}
