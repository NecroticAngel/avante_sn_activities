<?php
declare(strict_types=1);

require __DIR__ . '/api/bookings-store.php';

$pageTitle = 'Manage booking — Avante Travel';
$headerTitle = 'Manage booking';
$activeNav = 'manage';
$extraCss = ['assets/css/admin.css'];
require __DIR__ . '/includes/header.php';

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$reference = trim((string) ($_POST['reference'] ?? $_GET['ref'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$error = '';
$booking = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking = avante_find_booking($reference, $email);
    if (!$booking) {
        $error = 'No booking matched that reference and email.';
    }
}
?>
        <p class="avante-lede">Look up an accommodation or activity request with the reference we sent you and the email used to book.</p>

        <?php if ($error): ?><div class="admin-banner is-bad"><?php echo h($error); ?></div><?php endif; ?>

        <form class="admin-form" method="post">
            <div class="admin-form-grid">
                <label>Booking reference
                    <input type="text" name="reference" required value="<?php echo h($reference); ?>" placeholder="111521 or ACT-…">
                </label>
                <label>Email used to book
                    <input type="email" name="email" required value="<?php echo h($email); ?>">
                </label>
            </div>
            <div class="admin-form-actions">
                <button type="submit">Find booking</button>
            </div>
        </form>

        <?php if ($booking): ?>
            <div class="admin-form booking-card">
                <h2><?php echo h($booking['title']); ?></h2>
                <p class="admin-muted"><?php echo h($booking['type']); ?> · <?php echo h($booking['detail']); ?></p>
                <div class="overview-content">
                    <div class="overview-item"><strong>Reference:</strong> <span><?php echo h($booking['reference']); ?></span></div>
                    <div class="overview-item"><strong>Status:</strong> <span><?php echo h($booking['status']); ?></span></div>
                    <div class="overview-item"><strong>Guest:</strong> <span><?php echo h($booking['guest_name']); ?></span></div>
                    <div class="overview-item"><strong>Email:</strong> <span><?php echo h($booking['guest_email']); ?></span></div>
                    <?php if ($booking['guest_phone'] !== ''): ?>
                        <div class="overview-item"><strong>Phone:</strong> <span><?php echo h($booking['guest_phone']); ?></span></div>
                    <?php endif; ?>
                    <?php if ($booking['amount'] !== ''): ?>
                        <div class="overview-item"><strong>Amount:</strong> <span><?php echo h($booking['amount']); ?></span></div>
                    <?php endif; ?>
                    <?php if ($booking['check_in'] !== ''): ?>
                        <div class="overview-item"><strong>Dates:</strong> <span><?php echo h(trim($booking['check_in'] . ($booking['check_out'] !== '' ? ' – ' . $booking['check_out'] : ''))); ?></span></div>
                    <?php endif; ?>
                    <?php if (!empty($booking['sent_to'])): ?>
                        <div class="overview-item"><strong>Sent to:</strong> <span><?php echo h($booking['sent_to']); ?></span></div>
                    <?php endif; ?>
                    <?php if ($booking['notes'] !== ''): ?>
                        <div class="overview-item"><strong>Notes:</strong> <span><?php echo h($booking['notes']); ?></span></div>
                    <?php endif; ?>
                </div>
                <div class="admin-form-actions">
                    <?php if ($booking['payment_url'] !== ''): ?>
                        <a class="admin-add" href="<?php echo h($booking['payment_url']); ?>" target="_blank" rel="noopener noreferrer">Pay / complete on Stock Network</a>
                    <?php endif; ?>
                    <?php if ($booking['info_url'] !== ''): ?>
                        <a href="<?php echo h($booking['info_url']); ?>" target="_blank" rel="noopener noreferrer">View Stock Network details</a>
                    <?php endif; ?>
                </div>
                <?php if ($booking['type'] === 'Accommodation'): ?>
                    <p class="admin-hint">Stock Network does not let us change or cancel a stay through this site. Use the Stock Network links above, or contact Avante with your reference.</p>
                <?php else: ?>
                    <p class="admin-hint">Activity requests are sent to the operator. Reply to your confirmation email if you need to change anything.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <?php require __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
