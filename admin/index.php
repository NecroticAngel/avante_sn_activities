<?php
declare(strict_types=1);

session_start();

require dirname(__DIR__) . '/api/activities-store.php';

$configPath = dirname(__DIR__) . '/config.php';
$config = is_file($configPath) ? require $configPath : [];
$adminPassword = (string) ($config['admin_password'] ?? '');
$notice = '';
$error = '';
$editing = null;
$isNew = isset($_GET['new']);

function avante_admin_logged_in(): bool
{
    return !empty($_SESSION['avante_admin']);
}

if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $attempt = (string) ($_POST['password'] ?? '');
    if ($adminPassword === '') {
        $error = 'Set admin_password in config.php first.';
    } elseif (hash_equals($adminPassword, $attempt)) {
        $_SESSION['avante_admin'] = true;
        header('Location: index.php');
        exit;
    } else {
        $error = 'That password is not right.';
    }
}

if (avante_admin_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_activity'])) {
    try {
        $existingId = trim((string) ($_POST['id'] ?? ''));
        $existing = $existingId !== '' ? avante_activity_find($existingId) : null;
        $activity = avante_activity_from_input($_POST, $existing);
        avante_activity_upsert($activity);
        header('Location: index.php?saved=' . urlencode($activity['id']));
        exit;
    } catch (Throwable $e) {
        $error = $e->getMessage();
        $editing = $_POST;
        $editing['id'] = (string) ($_POST['id'] ?? '');
        $editing['published'] = !empty($_POST['published']);
        $isNew = $existingId === '';
    }
}

if (avante_admin_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_activity'])) {
    $id = trim((string) ($_POST['id'] ?? ''));
    if ($id !== '') {
        avante_activity_delete($id);
    }
    header('Location: index.php?deleted=1');
    exit;
}

if (isset($_GET['saved'])) {
    $notice = 'Activity saved.';
}
if (isset($_GET['deleted'])) {
    $notice = 'Activity removed.';
}

if (avante_admin_logged_in() && isset($_GET['edit'])) {
    $editing = avante_activity_find((string) $_GET['edit']);
    if (!$editing) {
        $error = 'That activity was not found.';
    }
}

if (avante_admin_logged_in() && $isNew && !$editing) {
    $editing = [
        'id' => '',
        'company' => '',
        'name' => '',
        'category' => 'Experiences',
        'price_label' => '',
        'phone' => '',
        'email' => '',
        'booking_email' => '',
        'booking_url' => '',
        'lat' => '',
        'lng' => '',
        'image_url' => '',
        'location' => 'Plettenberg Bay',
        'description' => '',
        'published' => true,
    ];
}

$items = avante_admin_logged_in() ? avante_activities_all() : [];
$base = '../';
$pageTitle = 'Manage activities';
$headerTitle = 'Manage activities';
$activeNav = 'activities';
$extraCss = ['assets/css/admin.css'];
$bodyClass = 'avante-search-page avante-admin-page';
require dirname(__DIR__) . '/includes/header.php';

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
        <?php if ($notice): ?><div class="admin-banner is-ok"><?php echo h($notice); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="admin-banner is-bad"><?php echo h($error); ?></div><?php endif; ?>

        <?php if (!avante_admin_logged_in()): ?>
            <form class="admin-login" method="post">
                <p>Sign in to add or edit activities. Public visitors never see this page.</p>
                <?php if ($adminPassword === ''): ?>
                    <p class="admin-banner is-bad">Add <code>admin_password</code> to <code>config.php</code> before signing in.</p>
                <?php endif; ?>
                <label>Password
                    <input type="password" name="password" required autofocus>
                </label>
                <button type="submit" name="login" value="1">Sign in</button>
            </form>
        <?php else: ?>
            <div class="admin-bar">
                <a class="admin-add" href="index.php?new=1">Add activity</a>
                <a href="bookings.php">Bookings</a>
                <a href="<?php echo h($base); ?>activities.php">View public page</a>
                <a href="index.php?logout=1">Sign out</a>
            </div>
            <?php if (trim((string) ($config['avante_booking_email'] ?? '')) === ''): ?>
                <div class="admin-banner is-bad">Set <code>avante_booking_email</code> in <code>config.php</code> so Avante gets a copy of every Make booking request. Each activity also has its own “Send bookings to” address.</div>
            <?php endif; ?>

            <?php if ($editing): ?>
                <form class="admin-form" method="post">
                    <input type="hidden" name="id" value="<?php echo h($editing['id'] ?? ''); ?>">
                    <div class="admin-form-grid">
                        <label>Company
                            <input type="text" name="company" required value="<?php echo h($editing['company'] ?? ''); ?>">
                        </label>
                        <label>Activity / product
                            <input type="text" name="name" required value="<?php echo h($editing['name'] ?? ''); ?>">
                        </label>
                        <label>Category
                            <select name="category">
                                <?php foreach (avante_activity_categories() as $category): ?>
                                    <option value="<?php echo h($category); ?>"<?php echo (($editing['category'] ?? '') === $category) ? ' selected' : ''; ?>><?php echo h($category); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>Price
                            <input type="text" name="price_label" placeholder="R 500 or Variable" value="<?php echo h($editing['price_label'] ?? ''); ?>">
                        </label>
                        <label>Phone
                            <input type="text" name="phone" value="<?php echo h($editing['phone'] ?? ''); ?>">
                        </label>
                        <label>Email
                            <input type="email" name="email" value="<?php echo h($editing['email'] ?? ''); ?>">
                        </label>
                        <label>Send bookings to
                            <input type="text" name="booking_email" placeholder="operator@example.com" value="<?php echo h($editing['booking_email'] ?? ''); ?>">
                            <span class="admin-hint">Operator inbox for Make booking requests. Avante also gets a copy from config.php.</span>
                        </label>
                        <label class="full">Website URL
                            <input type="url" name="booking_url" value="<?php echo h($editing['booking_url'] ?? ''); ?>">
                        </label>
                        <label class="full">Image URL
                            <input type="url" name="image_url" value="<?php echo h($editing['image_url'] ?? ''); ?>">
                        </label>
                        <label>Location
                            <input type="text" name="location" value="<?php echo h($editing['location'] ?? 'Plettenberg Bay'); ?>">
                        </label>
                        <label>Latitude
                            <input type="text" name="lat" value="<?php echo h($editing['lat'] ?? ''); ?>">
                        </label>
                        <label>Longitude
                            <input type="text" name="lng" value="<?php echo h($editing['lng'] ?? ''); ?>">
                        </label>
                        <label class="check">
                            <input type="checkbox" name="published" value="1"<?php echo !empty($editing['published']) ? ' checked' : ''; ?>>
                            Published on the activities page
                        </label>
                        <label class="full">Description
                            <textarea name="description" rows="4"><?php echo h($editing['description'] ?? ''); ?></textarea>
                        </label>
                    </div>
                    <div class="admin-form-actions">
                        <button type="submit" name="save_activity" value="1">Save activity</button>
                        <a href="index.php">Cancel</a>
                    </div>
                </form>
            <?php endif; ?>

            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Activity</th>
                            <th>Operator</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Bookings to</th>
                            <th>Live</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?php echo h($item['name'] ?? ''); ?></td>
                                <td><?php echo h($item['company'] ?? ''); ?></td>
                                <td><?php echo h($item['category'] ?? ''); ?></td>
                                <td><?php echo h($item['price_label'] ?? ''); ?></td>
                                <td><?php echo h($item['booking_email'] ?? $item['email'] ?? ''); ?></td>
                                <td><?php echo !empty($item['published']) ? 'Yes' : 'No'; ?></td>
                                <td class="admin-row-actions">
                                    <a href="index.php?edit=<?php echo urlencode((string) ($item['id'] ?? '')); ?>">Edit</a>
                                    <form method="post" onsubmit="return confirm('Remove this activity?');">
                                        <input type="hidden" name="id" value="<?php echo h($item['id'] ?? ''); ?>">
                                        <button type="submit" name="delete_activity" value="1">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
