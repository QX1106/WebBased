<?php require '../_base.php'; ?>
<?php auth('Member'); ?>
<?php

$_err = [];

if (is_post()) {
    $old_password = post('old_password');
    $new_password = post('new_password');
    $confirm_password = post('confirm_password');

    $stm = $pdo->prepare("SELECT password FROM member WHERE member_id = ?");
    $stm->execute([$_user->member_id]);
    $current_hash = $stm->fetchColumn();

    if (!$old_password) {
        $_err['old_password'] = 'Current password is required';
    } elseif (!password_verify($old_password, $current_hash)) {
        $_err['old_password'] = 'Current password is incorrect';
    }

    if (!$new_password) {
        $_err['new_password'] = 'New password is required';
    } elseif (!is_strong_password($new_password)) {
        $_err['new_password'] = 'At least 8 characters, with an uppercase letter, a number, and a symbol';
    }

    if ($new_password !== $confirm_password) {
        $_err['confirm_password'] = 'Passwords do not match';
    }

    if (!$_err) {
        $pdo->prepare("UPDATE member SET password = ?, updated_at = NOW() WHERE member_id = ?")
            ->execute([password_hash($new_password, PASSWORD_DEFAULT), $_user->member_id]);

        temp('info', 'Password changed successfully.');
        redirect('/member/profile.php');
    }
}

$_title = 'Change Password';
require '../_head.php';
?>

<h1>Change Password</h1>

<form method="post" novalidate>
    <label for="old_password">Current Password</label>
    <div class="pw-field">
        <?= html_password('old_password', "placeholder='Enter your current password'") ?>
        <button type="button" class="toggle-pw" data-target="old_password" tabindex="-1"></button>
    </div>
    <?= err('old_password') ?>

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

    <button>Change Password</button>
    <a href="/member/profile.php">Cancel</a>
</form>

<?php require '../_foot.php'; ?>
