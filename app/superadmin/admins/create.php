<?php require '../../_base.php'; ?>
<?php auth('Super Admin'); ?>
<?php

$_err = [];

if (is_post()) {
    $username = post('username');
    $email = post('email');
    $phone = post('phone');
    $password = post('password');
    $confirm_password = post('confirm_password');

    if (!$username) {
        $_err['username'] = 'Username is required';
    } elseif (!is_unique('member', 'username', $username)) {
        $_err['username'] = 'Username is already taken';
    }

    if (!$email) {
        $_err['email'] = 'Email is required';
    } elseif (!is_email($email)) {
        $_err['email'] = 'Invalid email format';
    } elseif (!is_unique('member', 'email', $email)) {
        $_err['email'] = 'Email is already registered';
    }

    if (!$phone) {
        $_err['phone'] = 'Phone number is required';
    } elseif (!preg_match('/^(\+?60|0)[0-9]{8,10}$/', str_replace([' ', '-'], '', $phone))) {
        $_err['phone'] = 'Must be a valid Malaysian phone number, e.g. 012-3456789';
    }

    if (!$password) {
        $_err['password'] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $_err['password'] = 'Password must be at least 6 characters';
    }

    if ($password !== $confirm_password) {
        $_err['confirm_password'] = 'Passwords do not match';
    }

    if (!$_err) {
        // New accounts created here are always role = Admin — Super Admin
        // accounts are never created through this form.
        $stm = $pdo->prepare("INSERT INTO member (username, email, phone, password, role, status, created_at)
                               VALUES (?, ?, ?, ?, 'Admin', 'Active', NOW())");
        $stm->execute([$username, $email, $phone, password_hash($password, PASSWORD_DEFAULT)]);

        temp('info', 'Admin account created.');
        redirect('/superadmin/admins/list.php');
    }
}

$_title = 'Create Admin';
require '../../_head.php';
?>

<?php
$eye_open = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>';
$eye_closed = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3l18 18"/><path d="M10.6 10.6a3 3 0 0 0 4.2 4.2"/><path d="M9.9 5.1A11 11 0 0 1 12 5c7 0 11 7 11 7a13.4 13.4 0 0 1-3.1 3.9M6.6 6.6C3.9 8.3 2 12 2 12s4 7 11 7c1.4 0 2.6-.2 3.7-.6"/></svg>';
?>

<h1>Create Admin</h1>

<form method="post" novalidate>
    <label for="username">Username</label>
    <?= html_text('username', "placeholder='Choose a username'") ?>
    <?= err('username') ?>

    <label for="email">Email</label>
    <?= html_text('email', "placeholder='admin@example.com'") ?>
    <?= err('email') ?>

    <label for="phone">Phone Number</label>
    <?= html_text('phone', "placeholder='012-3456789'") ?>
    <?= err('phone') ?>

    <label for="password">Password</label>
    <div class="pw-field">
        <?= html_password('password', "placeholder='At least 6 characters'") ?>
        <button type="button" class="toggle-pw" data-target="password" tabindex="-1"><?= $eye_open ?></button>
    </div>
    <?= err('password') ?>

    <label for="confirm_password">Confirm Password</label>
    <div class="pw-field">
        <?= html_password('confirm_password', "placeholder='Re-enter password'") ?>
        <button type="button" class="toggle-pw" data-target="confirm_password" tabindex="-1"><?= $eye_open ?></button>
    </div>
    <?= err('confirm_password') ?>

    <button>Create Admin</button>
    <a href="/superadmin/admins/list.php">Cancel</a>
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

<?php require '../../_foot.php'; ?>
