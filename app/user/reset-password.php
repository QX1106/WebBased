<?php require '../_base.php'; ?>
<?php

$token = req('token');

$stm = $pdo->prepare("SELECT * FROM member WHERE reset_token = ? AND reset_expires > NOW()");
$stm->execute([$token]);
$account = $stm->fetch();

if (!$account) {
    temp('info', 'This reset link is invalid or has expired. Please request a new one.');
    redirect('/user/login.php');
}

$_err = [];

if (is_post()) {
    $new_password = post('new_password');
    $confirm_password = post('confirm_password');

    if (!$new_password) {
        $_err['new_password'] = 'New password is required';
    } elseif (!is_strong_password($new_password)) {
        $_err['new_password'] = 'At least 8 characters, with an uppercase letter, a number, and a symbol';
    }

    if ($new_password !== $confirm_password) {
        $_err['confirm_password'] = 'Passwords do not match';
    }

    if (!$_err) {
        $pdo->prepare("UPDATE member SET password = ?, reset_token = NULL, reset_expires = NULL, updated_at = NOW() WHERE member_id = ?")
            ->execute([password_hash($new_password, PASSWORD_DEFAULT), $account->member_id]);

        temp('info', 'Password reset successful. Please login with your new password.');
        redirect(in_array($account->role, ['Admin', 'Super Admin']) ? '/admin/login.php' : '/user/login.php');
    }
}

$_title = 'Reset Password';
require '../_head.php';
?>

<h1>Reset Password</h1>
<p>Resetting password for <strong><?= h($account->username) ?></strong>.</p>

<form method="post" novalidate>
    <?= html_hidden('token', $token) ?>

    <label for="new_password">New Password</label>
    <div class="pw-field">
        <?= html_password('new_password', "placeholder='8+ chars, 1 uppercase, 1 number, 1 symbol'") ?>
        <button type="button" class="toggle-pw" data-target="new_password" tabindex="-1"></button>
    </div>
    <?= err('new_password') ?>

    <label for="confirm_password">Confirm New Password</label>
    <div class="pw-field">
        <?= html_password('confirm_password', "placeholder='Re-enter new password'") ?>
        <button type="button" class="toggle-pw" data-target="confirm_password" tabindex="-1"></button>
    </div>
    <?= err('confirm_password') ?>

    <button>Reset Password</button>
</form>

<?php require '../_foot.php'; ?>
