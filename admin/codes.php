<?php
declare(strict_types=1);

session_start();

$configPath = dirname(__DIR__) . '/config.php';
$config = is_file($configPath) ? require $configPath : [];
$adminPassword = (string) ($config['admin_password'] ?? '');
$notice = '';
$error = '';
$resultJson = '';

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
        header('Location: codes.php');
        exit;
    }
    $error = 'That password is not right.';
}

$base = '../';
$pageTitle = 'Members & vouchers';
$headerTitle = 'Members & vouchers';
$extraCss = ['assets/css/admin.css'];
$bodyClass = 'avante-search-page avante-admin-page';
require dirname(__DIR__) . '/includes/header.php';
?>
        <?php if ($notice): ?><div class="admin-banner is-ok"><?php echo h($notice); ?></div><?php endif; ?>
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
                <a href="bookings.php">Bookings</a>
                <a href="index.php">Activities</a>
                <a class="admin-add" href="codes.php">Members &amp; vouchers</a>
                <a href="settings.php">Settings</a>
                <a href="index.php?logout=1">Sign out</a>
            </div>

            <p class="avante-lede">These are the Stock Network member and voucher calls. Validate a code, or upload one so a guest can use it on Book Now.</p>

            <form class="admin-form" id="validate-form">
                <h2>Validate a code</h2>
                <div class="admin-form-grid">
                    <label>Membership number
                        <input type="text" name="membershipNo" placeholder="Optional">
                    </label>
                    <label>Voucher code
                        <input type="text" name="voucherCode" placeholder="Optional">
                    </label>
                </div>
                <div class="admin-form-actions">
                    <button type="submit">Check with Stock Network</button>
                </div>
                <pre class="admin-result" id="validate-result" hidden></pre>
            </form>

            <form class="admin-form" id="voucher-form">
                <h2>Upload a voucher</h2>
                <div class="admin-form-grid">
                    <label>Code *
                        <input type="text" name="code" required>
                    </label>
                    <label>Amount (ZAR) *
                        <input type="number" name="amount" min="0" step="0.01" required>
                    </label>
                    <label>Full name *
                        <input type="text" name="fullName" required>
                    </label>
                    <label>Email *
                        <input type="email" name="emailAddress" required>
                    </label>
                    <label>Cellphone *
                        <input type="tel" name="cellphone" required>
                    </label>
                </div>
                <div class="admin-form-actions">
                    <button type="submit">Upload voucher</button>
                </div>
                <pre class="admin-result" id="voucher-result" hidden></pre>
            </form>

            <form class="admin-form" id="member-form">
                <h2>Upload a member</h2>
                <div class="admin-form-grid">
                    <label>Membership number *
                        <input type="text" name="membershipNumber" required>
                    </label>
                    <label>Full name *
                        <input type="text" name="fullName" required>
                    </label>
                    <label>Email *
                        <input type="email" name="emailAddress" required>
                    </label>
                    <label>Cellphone *
                        <input type="tel" name="cellphone" required>
                    </label>
                </div>
                <div class="admin-form-actions">
                    <button type="submit">Upload member</button>
                </div>
                <pre class="admin-result" id="member-result" hidden></pre>
            </form>

            <script>
            function showResult(id, data) {
                var el = document.getElementById(id);
                el.hidden = false;
                el.textContent = JSON.stringify(data, null, 2);
            }
            function postJson(action, body) {
                return fetch('../api/index.php?action=' + action, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(body)
                }).then(function(res) {
                    return res.json().then(function(data) {
                        if (!res.ok || data.ok === false) {
                            throw new Error(data.error || 'Request failed');
                        }
                        return data;
                    });
                });
            }
            document.getElementById('validate-form').addEventListener('submit', function(event) {
                event.preventDefault();
                var membershipNo = this.membershipNo.value.trim();
                var voucherCode = this.voucherCode.value.trim();
                var action = membershipNo ? postJson('validate_member', { membershipNo: membershipNo }) : postJson('validate_voucher', { voucherCode: voucherCode });
                if (!membershipNo && !voucherCode) {
                    showResult('validate-result', { error: 'Enter a membership number or voucher code.' });
                    return;
                }
                action.then(function(data) { showResult('validate-result', data); })
                    .catch(function(error) { showResult('validate-result', { error: error.message }); });
            });
            document.getElementById('voucher-form').addEventListener('submit', function(event) {
                event.preventDefault();
                postJson('upload_voucher', {
                    code: this.code.value,
                    amount: this.amount.value,
                    fullName: this.fullName.value,
                    emailAddress: this.emailAddress.value,
                    cellphone: this.cellphone.value
                }).then(function(data) { showResult('voucher-result', data); })
                    .catch(function(error) { showResult('voucher-result', { error: error.message }); });
            });
            document.getElementById('member-form').addEventListener('submit', function(event) {
                event.preventDefault();
                postJson('upload_member', {
                    membershipNumber: this.membershipNumber.value,
                    fullName: this.fullName.value,
                    emailAddress: this.emailAddress.value,
                    cellphone: this.cellphone.value
                }).then(function(data) { showResult('member-result', data); })
                    .catch(function(error) { showResult('member-result', { error: error.message }); });
            });
            </script>
        <?php endif; ?>
    </div>
</body>
</html>
