<?php
declare(strict_types=1);

session_start();

require dirname(__DIR__) . '/api/bookings-store.php';
require dirname(__DIR__) . '/api/audit-store.php';
require dirname(__DIR__) . '/includes/admin-auth.php';

$configPath = dirname(__DIR__) . '/config.php';
$config = is_file($configPath) ? require $configPath : [];
$adminPassword = (string) ($config['admin_password'] ?? '');
$error = avante_admin_handle_auth($adminPassword, 'booking.php?' . http_build_query($_GET));

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
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
                <label class="admin-remember">
                    <input type="checkbox" name="remember_me" value="1">
                    Remember me for 30 days
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
                            <a href="payment.php?ref=<?php echo urlencode((string) $booking['reference']); ?>" target="_blank" rel="noopener noreferrer">Pay / portal</a>
                        <?php endif; ?>
                    </div>
                    <?php
                    $snRecord = avante_find_accommodation_record((string) $booking['reference'], (string) $booking['guest_email']);
                    $snResponse = is_array($snRecord['sn_response'] ?? null) ? $snRecord['sn_response'] : null;
                    $auditEvents = avante_booking_audit_events((string) $booking['reference'], (string) $booking['reservation_id']);
                    ?>
                    <?php if ($snResponse): ?>
                        <h3 id="sn-json">Stock Network /request response</h3>
                        <pre class="admin-json"><?php echo h(json_encode($snResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)); ?></pre>
                    <?php endif; ?>
                    <h3>Payment audit trail</h3>
                    <?php if ($auditEvents): ?>
                        <ol class="admin-audit-list">
                            <?php foreach (array_reverse($auditEvents) as $event): ?>
                                <li>
                                    <strong><?php echo h(ucwords(str_replace('_', ' ', (string) ($event['event'] ?? 'event')))); ?></strong>
                                    <span><?php echo h(avante_booking_when((string) ($event['at'] ?? ''))); ?></span>
                                    <details>
                                        <summary>Recorded details</summary>
                                        <pre class="admin-json"><?php echo h(json_encode($event['context'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)); ?></pre>
                                    </details>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    <?php else: ?>
                        <p class="admin-muted">No payment actions have been recorded yet. Opening “Pay / portal” from this admin page will create the first entry.</p>
                    <?php endif; ?>
                    <p class="admin-hint">The audit excludes passwords, API tokens and card details. Stock Network hosts the payment page, so the app can record that the portal was opened but cannot see the card form or final gateway response unless Stock Network provides a callback or status API.</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
