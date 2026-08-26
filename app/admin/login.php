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

        if ($admin && is_account_locked($admin)) {
            $_err['password'] = 'Too many failed attempts. Try again in ' . login_lock_seconds_remaining($admin) . ' second(s).';
        } elseif (!$admin || !password_verify($password, $admin->password)) {
            if ($admin) record_failed_login($admin->member_id);
            $_err['password'] = 'Invalid username or password';
        } elseif ($admin->status != 'Active') {
            $_err['password'] = 'Your admin account has been deactivated.';
        } else {
            reset_login_attempts($admin->member_id);
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

<h1>Admin Login</h1>

<form method="post" novalidate>
    <label for="username">Username</label>
    <?= html_text('username', "placeholder='Enter your username'") ?>
    <?= err('username') ?>

    <label for="password">Password</label>
    <div class="pw-field">
        <?= html_password('password', "placeholder='Enter your password'") ?>
        <button type="button" class="toggle-pw" data-target="password" tabindex="-1"></button>
    </div>
    <?= err('password') ?>

    <p><a href="/admin/forgot-password.php">Forgot password?</a></p>

    <button>Login</button>
</form>

<?php require '../_foot.php'; ?>
