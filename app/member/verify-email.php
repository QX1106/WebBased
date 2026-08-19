<?php require '../_base.php'; ?>
<?php

$email = req('email');

$stm = $pdo->prepare("SELECT * FROM member WHERE email = ? AND role = 'Member'");
$stm->execute([$email]);
$member = $stm->fetch();

if (!$member) {
    temp('info', 'Account not found.');
    redirect('/user/login.php');
}

if ($member->email_verified) {
    redirect('/user/login.php');
}

$_err = [];
$action = post('action', 'verify');

if (is_post() && $action == 'resend') {
    $otp = strval(random_int(100000, 999999));
    $pdo->prepare("UPDATE member SET email_otp = ?, email_otp_expires = NOW() + INTERVAL 15 MINUTE WHERE member_id = ?")
        ->execute([$otp, $member->member_id]);

    send_email(
        $member->email,
        'Your new verification code - Stationary Online Store',
        "<p>Your verification code is:</p><h2>$otp</h2><p>This code expires in 15 minutes.</p>"
    );

    $member->email_otp = $otp; // so the dev-mode box below shows the fresh code
    temp('info', 'A new code has been sent to your email.');
}

if (is_post() && $action == 'verify') {
    $otp = post('otp');

    if (!$otp) {
        $_err['otp'] = 'Enter the code from your email';
    } elseif ($otp != $member->email_otp) {
        $_err['otp'] = 'Incorrect code';
    } elseif (!$member->email_otp_expires || strtotime($member->email_otp_expires) < time()) {
        $_err['otp'] = 'This code has expired. Request a new one.';
    }

    if (!$_err) {
        $pdo->prepare("UPDATE member SET email_verified = 1, email_otp = NULL, email_otp_expires = NULL WHERE member_id = ?")
            ->execute([$member->member_id]);

        temp('info', 'Email verified! You can now login.');
        redirect('/user/login.php');
    }
}

$_title = 'Verify Email';
require '../_head.php';
?>

<h1>Verify Your Email</h1>
<p>We've sent a 6-digit code to <strong><?= h($member->email) ?></strong>. Enter it below to activate your account.</p>

<?php if ($member->email_otp): ?>
<p style="background:#fff3cd; border:1px solid #ffe08a; padding:8px 12px;">
    <strong>Dev mode:</strong> this local server has no mail relay configured, so here's the code directly for testing:
    <strong><?= h($member->email_otp) ?></strong>
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
