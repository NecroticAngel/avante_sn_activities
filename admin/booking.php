<?php
declare(strict_types=1);

session_start();

require dirname(__DIR__) . '/api/bookings-store.php';

$configPath = dirname(__DIR__) . '/config.php';
$config = is_file($configPath) ? require $configPath : [];
$adminPassword = (string) ($config['admin_password'] ?? '');
$error = '';

function avante_admin_logged_in(): bool
{
    return !empty($_SESSION['avante_admin']);
}

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $attempt = (string) ($_POST['password'] ?? '');
    if ($adminPassword !== '' && hash_equals($adminPassword, $attempt)) {
        $_SESSION['avante_admin'] = true;
        header('Location: booking.php?' . http_build_query($_GET));
        exit;
    }
    $error = 'That password is not right.';
}

$booking = null;
if (avante_admin_logged_in()) {
    $booking = avante_find_booking((string) ($_GET['ref'] ?? ''), (string) ($_GET['email'] ?? ''));
    if (!$booking) {
        $error = 'That booking was not found.';
    }
}

$base = '../';
$pageTitle = 'Booking detail';
$headerTitle = 'Booking detail';
$extraCss = ['assets/css/admin.css'];
$bodyClass = 'avante-search-page avante-admin-page';
require dirname(__DIR__) . '/includes/header.php';
?>
        <?php if ($error): ?><div class="admin-banner is-bad"><?php echo h($error); ?></div><?php endif; ?>

        <?php if (!avante_admin_logged_in()): ?>
            <form class="admin-login" method="post">
                <label>Password
                    <input type="password" name="password" required autofocus>
                </label>
                <button type="submit" name="login" value="1">Sign in</button>
            </form>
        <?php else: ?>
            <div class="admin-bar">
                <a href="bookings.php">Back to bookings</a>
                <a href="codes.php">Members &amp; vouchers</a>
                <a href="settings.php">Settings</a>
                <a href="index.php?logout=1">Sign out</a>
            </div>
            <?php if ($booking): ?>
                <div class="admin-form">
                    <h2><?php echo h($booking['title']); ?></h2>
                    <p class="admin-muted"><?php echo h($booking['type']); ?> · <?php echo h($booking['detail']); ?></p>
                    <div class="overview-content">
                        <div class="overview-item"><strong>When:</strong> <span><?php echo h(avante_booking_when($booking['at'])); ?></span></div>
                        <div class="overview-item"><strong>Reference:</strong> <span><?php echo h($booking['reference']); ?></span></div>
                        <div class="overview-item"><strong>Status:</strong> <span><?php echo h($booking['status']); ?></span></div>
                        <div class="overview-item"><strong>Guest:</strong> <span><?php echo h($booking['guest_name']); ?></span></div>
                        <div class="overview-item"><strong>Email:</strong> <span><?php echo h($booking['guest_email']); ?></span></div>
                        <div class="overview-item"><strong>Phone:</strong> <span><?php echo h($booking['guest_phone']); ?></span></div>
                        <div class="overview-item"><strong>Amount:</strong> <span><?php echo h($booking['amount']); ?></span></div>
                        <?php if (!empty($booking['membership_no'])): ?>
                            <div class="overview-item"><strong>Member / voucher:</strong> <span><?php echo h($booking['membership_no']); ?></span></div>
                        <?php endif; ?>
                        <?php if (!empty($booking['sent_to'])): ?>
                            <div class="overview-item"><strong>Emailed to:</strong> <span><?php echo h($booking['sent_to']); ?></span></div>
                        <?php endif; ?>
                        <?php if ($booking['notes'] !== ''): ?>
                            <div class="overview-item"><strong>Notes:</strong> <span><?php echo h($booking['notes']); ?></span></div>
                        <?php endif; ?>
                    </div>
                    <div class="admin-form-actions">
                        <?php if ($booking['info_url'] !== ''): ?>
                            <a class="admin-add" href="<?php echo h($booking['info_url']); ?>" target="_blank" rel="noopener noreferrer">SN details</a>
                        <?php endif; ?>
                        <?php if ($booking['payment_url'] !== ''): ?>
                            <a href="<?php echo h($booking['payment_url']); ?>" target="_blank" rel="noopener noreferrer">Pay / portal</a>
                        <?php endif; ?>
                    </div>
                    <p class="admin-hint">Stock Network has no documented API to edit or cancel a reservation. Those actions happen on the Stock Network portal.</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
