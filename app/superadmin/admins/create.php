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
    } elseif (!is_strong_password($password)) {
        $_err['password'] = 'At least 8 characters, with an uppercase letter, a number, and a symbol';
    }

    if ($password !== $confirm_password) {
        $_err['confirm_password'] = 'Passwords do not match';
    }

    if (!$_err) {
        // New accounts created here are always role = Admin — Super Admin
        // accounts are never created through this form.
        $stm = $pdo->prepare("INSERT INTO member (username, email, phone, password, role, status, email_verified, created_at)
                               VALUES (?, ?, ?, ?, 'Admin', 'Active', 1, NOW())");
        $stm->execute([$username, $email, $phone, password_hash($password, PASSWORD_DEFAULT)]);

        temp('info', 'Admin account created.');
        redirect('/superadmin/admins/list.php');
    }
}

$_title = 'Create Admin';
require '../../_head.php';
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
    <?= err('password') ?>
    <div class="pw-field">
        <?= html_password('password', "placeholder='Enter password'") ?>
        <button type="button" class="toggle-pw" data-target="password" tabindex="-1"></button>
    </div>
    <ul class="pw-requirements">
        <li>At least 8 characters</li>
        <li>One uppercase letter</li>
        <li>One number</li>
        <li>One symbol (e.g. ! @ # $ %)</li>
    </ul>

    <label for="confirm_password">Confirm Password</label>
    <div class="pw-field">
        <?= html_password('confirm_password', "placeholder='Re-enter password'") ?>
        <button type="button" class="toggle-pw" data-target="confirm_password" tabindex="-1"></button>
    </div>
    <?= err('confirm_password') ?>

    <button>Create Admin</button>
    <a href="/superadmin/admins/list.php">Cancel</a>
</form>

<?php require '../../_foot.php'; ?>
