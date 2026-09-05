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
        header('Location: bookings.php');
        exit;
    }
    $error = $adminPassword === '' ? 'Set admin_password in config.php first.' : 'That password is not right.';
}

$filter = preg_replace('/[^a-z]/', '', strtolower((string) ($_GET['type'] ?? 'all')));
if (!in_array($filter, ['all', 'accommodation', 'activity'], true)) {
    $filter = 'all';
}

$bookings = avante_admin_logged_in() ? avante_admin_bookings() : [];
if ($filter === 'accommodation') {
    $bookings = array_values(array_filter($bookings, static fn(array $row): bool => $row['type'] === 'Accommodation'));
} elseif ($filter === 'activity') {
    $bookings = array_values(array_filter($bookings, static fn(array $row): bool => $row['type'] === 'Activity'));
}

$base = '../';
$pageTitle = 'Bookings';
$headerTitle = 'Bookings';
$activeNav = '';
$extraCss = ['assets/css/admin.css'];
$bodyClass = 'avante-search-page avante-admin-page';
require dirname(__DIR__) . '/includes/header.php';
?>
        <?php if ($error): ?><div class="admin-banner is-bad"><?php echo h($error); ?></div><?php endif; ?>

        <?php if (!avante_admin_logged_in()): ?>
            <form class="admin-login" method="post">
                <p>Sign in to view bookings.</p>
                <label>Password
                    <input type="password" name="password" required autofocus>
                </label>
                <button type="submit" name="login" value="1">Sign in</button>
            </form>
        <?php else: ?>
            <div class="admin-bar">
                <a class="admin-add" href="bookings.php">Bookings</a>
                <a href="index.php">Activities</a>
                <a href="codes.php">Members &amp; vouchers</a>
                <a href="settings.php">Settings</a>
                <a href="<?php echo h($base); ?>index.php">View search</a>
                <a href="index.php?logout=1">Sign out</a>
            </div>

            <div class="admin-bar admin-filters">
                <a href="bookings.php"<?php echo $filter === 'all' ? ' class="is-active"' : ''; ?>>All (<?php echo count(avante_admin_bookings()); ?>)</a>
                <a href="bookings.php?type=accommodation"<?php echo $filter === 'accommodation' ? ' class="is-active"' : ''; ?>>Accommodation</a>
                <a href="bookings.php?type=activity"<?php echo $filter === 'activity' ? ' class="is-active"' : ''; ?>>Activities</a>
            </div>

            <?php if (!$bookings): ?>
                <div class="admin-banner">No bookings logged yet.</div>
            <?php else: ?>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>When</th>
                                <th>Type</th>
                                <th>Booking</th>
                                <th>Guest</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Reference</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $row): ?>
                                <tr>
                                    <td><?php echo h(avante_booking_when($row['at'])); ?></td>
                                    <td><?php echo h($row['type']); ?></td>
                                    <td>
                                        <strong><?php echo h($row['title']); ?></strong>
                                        <?php if ($row['detail'] !== ''): ?>
                                            <div class="admin-muted"><?php echo h($row['detail']); ?></div>
                                        <?php endif; ?>
                                        <?php if ($row['notes'] !== ''): ?>
                                            <div class="admin-muted"><?php echo h($row['notes']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo h($row['guest_name']); ?>
                                        <div class="admin-muted"><?php echo h($row['guest_email']); ?></div>
                                        <?php if ($row['guest_phone'] !== ''): ?>
                                            <div class="admin-muted"><?php echo h($row['guest_phone']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo h($row['amount']); ?></td>
                                    <td><?php echo h($row['status']); ?></td>
                                    <td>
                                        <?php echo h($row['reference']); ?>
                                        <?php if ($row['type'] === 'Accommodation' && ($row['info_url'] !== '' || $row['payment_url'] !== '')): ?>
                                            <div class="admin-row-actions">
                                                <?php if ($row['info_url'] !== ''): ?>
                                                    <a href="<?php echo h($row['info_url']); ?>" target="_blank" rel="noopener noreferrer">SN details</a>
                                                <?php endif; ?>
                                                <?php if ($row['payment_url'] !== ''): ?>
                                                    <a href="<?php echo h($row['payment_url']); ?>" target="_blank" rel="noopener noreferrer">Pay / portal</a>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="admin-row-actions">
                                        <a href="booking.php?ref=<?php echo urlencode((string) $row['reference']); ?>&email=<?php echo urlencode((string) $row['guest_email']); ?>">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
