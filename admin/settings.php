<?php
declare(strict_types=1);

session_start();

require dirname(__DIR__) . '/api/settings-store.php';
require dirname(__DIR__) . '/includes/admin-auth.php';

$configPath = dirname(__DIR__) . '/config.php';
$config = is_file($configPath) ? require $configPath : [];
$adminPassword = (string) ($config['admin_password'] ?? '');
$notice = '';
$error = avante_admin_handle_auth($adminPassword, 'settings.php');
$settings = avante_settings_load();

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

if (avante_admin_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    try {
        $settings = avante_settings_save($_POST);
        header('Location: settings.php?saved=1');
        exit;
    } catch (Throwable $e) {
        $error = $e->getMessage();
        $settings['catch_all_emails'] = (string) ($_POST['catch_all_emails'] ?? '');
    }
}

if (isset($_GET['saved'])) {
    $notice = 'Settings saved.';
}

$base = '../';
$pageTitle = 'Settings';
$headerTitle = 'Settings';
$activeNav = '';
$extraCss = ['assets/css/admin.css'];
$bodyClass = 'avante-search-page avante-admin-page';
require dirname(__DIR__) . '/includes/header.php';
?>
        <?php if ($notice): ?><div class="admin-banner is-ok"><?php echo h($notice); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="admin-banner is-bad"><?php echo h($error); ?></div><?php endif; ?>

        <?php if (!avante_admin_logged_in()): ?>
            <form class="admin-login" method="post">
                <p>Sign in to change site settings.</p>
                <label>Password
                    <input type="password" name="password" required autofocus>
                </label>
                <label class="admin-remember">
                    <input type="checkbox" name="remember_me" value="1">
                    Remember me for 30 days
                </label>
                <button type="submit" name="login" value="1">Sign in</button>
            </form>
        <?php else: ?>
            <div class="admin-bar">
                <a href="bookings.php">Bookings</a>
                <a href="index.php">Activities</a>
                <a href="codes.php">Members &amp; vouchers</a>
                <a class="admin-add" href="settings.php">Settings</a>
                <a href="<?php echo h($base); ?>index.php">View search</a>
                <a href="index.php?logout=1">Sign out</a>
            </div>

            <form class="admin-form" method="post">
                <label class="full">Catch-all booking emails
                    <input type="text" name="catch_all_emails" value="<?php echo h($settings['catch_all_emails'] ?? ''); ?>" placeholder="you@example.com, second@example.com">
                    <span class="admin-hint">A copy of every accommodation and activity booking goes here. Separate multiple addresses with commas.</span>
                </label>
                <div class="admin-form-actions">
                    <button type="submit" name="save_settings" value="1">Save settings</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
