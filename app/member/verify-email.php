<?php require '../_base.php'; ?>
<?php

$pending = $_SESSION['pending_registration'] ?? null;
$email = req('email');

// No pending registration in this session, or it doesn't match the email
// in the URL (e.g. someone bookmarked/reloaded this page later).
if (!$pending || $pending['email'] !== $email) {
    temp('info', 'No pending registration found. Please register again.');
    redirect('/member/register.php');
}

$_err = [];
$action = post('action', 'verify');

// Reflects whether the ORIGINAL registration email actually sent —
// read once from the flash set by register.php, so the dev-mode box
// below only appears on a genuine failure, not by default.
$email_sent = null;
if (!is_post()) {
    $flag = temp('email_sent');
    if ($flag !== null) $email_sent = ($flag === '1');
}

if (is_post() && $action == 'resend') {
    $otp = strval(random_int(100000, 999999));
    $pending['otp'] = $otp;
    $pending['otp_expires'] = time() + 15 * 60;
    $_SESSION['pending_registration'] = $pending;

    $email_sent = send_email(
        $pending['email'],
        'Your new verification code - Stationary Online Store',
        "<p>Your verification code is:</p><h2>$otp</h2><p>This code expires in 15 minutes.</p>"
    );

    temp('info', 'A new code has been sent to your email.');
}

if (is_post() && $action == 'verify') {
    $otp = post('otp');

    if (!$otp) {
        $_err['otp'] = 'Enter the code from your email';
    } elseif ($otp != $pending['otp']) {
        $_err['otp'] = 'Incorrect code';
    } elseif (time() > $pending['otp_expires']) {
        $_err['otp'] = 'This code has expired. Request a new one.';
    }

    if (!$_err) {
        // Only now — after the code is confirmed — does the account
        // actually get created.
        $stm = $pdo->prepare("INSERT INTO member (username, email, password, phone, address, photo, role, status, email_verified, created_at)
                               VALUES (?, ?, ?, ?, ?, ?, 'Member', 'Active', 1, NOW())");
        $stm->execute([
            $pending['username'],
            $pending['email'],
            $pending['password_hash'],
            $pending['phone'],
            $pending['address'],
            $pending['photo'],
        ]);

        unset($_SESSION['pending_registration']);

        temp('info', 'Email verified! Your account has been created — you can now login.');
        redirect('/user/login.php');
    }
}

$_title = 'Verify Email';
require '../_head.php';
?>

<h1>Verify Your Email</h1>
<p>We've sent a 6-digit code to <strong><?= h($pending['email']) ?></strong>. Enter it below to finish creating your account.</p>

<?php if ($email_sent === false): ?>
<p style="background:#fff3cd; border:1px solid #ffe08a; padding:8px 12px;">
    <strong>Dev mode:</strong> this local server has no mail relay configured, so here's the code directly for testing:
    <strong><?= h($pending['otp']) ?></strong>
</p>
<?php endif; ?>

<form method="post" novalidate>
    <input type="hidden" name="action" value="verify">

    <label for="otp">Verification Code</label>
    <?= html_text('otp', "maxlength='6' placeholder='6-digit code'") ?>
    <?= err('otp') ?>

    <button>Verify</button>
</form>

<form method="post" style="margin-top:16px;">
    <input type="hidden" name="action" value="resend">
    <button type="submit" style="background:transparent; color:var(--ink); border-color:var(--border);">Resend Code</button>
</form>

<?php require '../_foot.php'; ?>
