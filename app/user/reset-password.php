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
    } elseif (strlen($new_password) < 6) {
        $_err['new_password'] = 'Password must be at least 6 characters';
    }

    if ($new_password !== $confirm_password) {
        $_err['confirm_password'] = 'Passwords do not match';
    }

    if (!$_err) {
        $pdo->prepare("UPDATE member SET password = ?, reset_token = NULL, reset_expires = NULL, updated_at = NOW() WHERE member_id = ?")
            ->execute([password_hash($new_password, PASSWORD_DEFAULT), $account->member_id]);

        temp('info', 'Password reset successful. Please login with your new password.');
        redirect($account->role == 'Admin' ? '/admin/login.php' : '/user/login.php');
    }
}

$_title = 'Reset Password';
require '../_head.php';
?>

<?php
$eye_open = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>';
$eye_closed = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3l18 18"/><path d="M10.6 10.6a3 3 0 0 0 4.2 4.2"/><path d="M9.9 5.1A11 11 0 0 1 12 5c7 0 11 7 11 7a13.4 13.4 0 0 1-3.1 3.9M6.6 6.6C3.9 8.3 2 12 2 12s4 7 11 7c1.4 0 2.6-.2 3.7-.6"/></svg>';
?>

<h1>Reset Password</h1>
<p>Resetting password for <strong><?= h($account->username) ?></strong>.</p>

<form method="post" novalidate>
    <?= html_hidden('token', $token) ?>

    <label for="new_password">New Password</label>
    <div class="pw-field">
        <?= html_password('new_password', "placeholder='At least 6 characters'") ?>
        <button type="button" class="toggle-pw" data-target="new_password" tabindex="-1"><?= $eye_open ?></button>
    </div>
    <?= err('new_password') ?>

    <label for="confirm_password">Confirm New Password</label>
    <div class="pw-field">
        <?= html_password('confirm_password', "placeholder='Re-enter new password'") ?>
        <button type="button" class="toggle-pw" data-target="confirm_password" tabindex="-1"><?= $eye_open ?></button>
    </div>
    <?= err('confirm_password') ?>

    <button>Reset Password</button>
</form>

<script>
    var eyeOpen = <?= json_encode($eye_open) ?>;
    var eyeClosed = <?= json_encode($eye_closed) ?>;

    $(function () {
        $('.toggle-pw').on('click', function () {
            var $btn = $(this);
            var $input = $('#' + $btn.data('target'));
            var isHidden = $input.attr('type') === 'password';
            $input.attr('type', isHidden ? 'text' : 'password');
            $btn.html(isHidden ? eyeClosed : eyeOpen);
        });
    });
</script>

<?php require '../_foot.php'; ?>
