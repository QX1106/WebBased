<?php require '../_base.php'; ?>
<?php

// Already logged in? Send Admin and Super Admin to their own dashboards.
if ($_user && $_user->role == 'Admin') redirect('/dashboard.php');
if ($_user && $_user->role == 'Super Admin') redirect('/superadmin/dashboard.php');

$_err = [];

if (is_post()) {
    $username = post('username');
    $password = post('password');

    if (!$username) $_err['username'] = 'Username is required';
    if (!$password) $_err['password'] = 'Password is required';

    if (!$_err) {
        // Shared by Admin and Super Admin — customer accounts (role =
        // Member) never match here.
        $stm = $pdo->prepare("SELECT * FROM member WHERE username = ? AND role IN ('Admin', 'Super Admin')");
        $stm->execute([$username]);
        $admin = $stm->fetch();

        if (!$admin || !password_verify($password, $admin->password)) {
            $_err['password'] = 'Invalid username or password';
        } elseif ($admin->status != 'Active') {
            $_err['password'] = 'Your admin account has been deactivated.';
        } else {
            session_regenerate_id(true);
            unset($admin->password);
            $redirect_url = $admin->role == 'Super Admin' ? '/superadmin/dashboard.php' : '/dashboard.php';
            login($admin, $redirect_url);
        }
    }
}

$_title = 'Admin Login';
require '../_head.php';
?>

<?php
$eye_open = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>';
$eye_closed = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3l18 18"/><path d="M10.6 10.6a3 3 0 0 0 4.2 4.2"/><path d="M9.9 5.1A11 11 0 0 1 12 5c7 0 11 7 11 7a13.4 13.4 0 0 1-3.1 3.9M6.6 6.6C3.9 8.3 2 12 2 12s4 7 11 7c1.4 0 2.6-.2 3.7-.6"/></svg>';
?>

<h1>Admin Login</h1>

<form method="post" novalidate>
    <label for="username">Username</label>
    <?= html_text('username', "placeholder='Enter your username'") ?>
    <?= err('username') ?>

    <label for="password">Password</label>
    <div class="pw-field">
        <?= html_password('password', "placeholder='Enter your password'") ?>
        <button type="button" class="toggle-pw" data-target="password" tabindex="-1"><?= $eye_open ?></button>
    </div>
    <?= err('password') ?>

    <p><a href="/admin/forgot-password.php">Forgot password?</a></p>

    <button>Login</button>
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
