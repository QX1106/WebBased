<?php require '../_base.php'; ?>
<?php

// Already logged in as a Member? Just go home.
if ($_user && $_user->role == 'Member') redirect('/');

$_err = [];

if (is_post()) {
    $username = post('username');
    $password = post('password');

    if (!$username) $_err['username'] = 'Username is required';
    if (!$password) $_err['password'] = 'Password is required';

    if (!$_err) {
        // Only match accounts with role = Member — this is what keeps
        // customer login separate from admin login.
        $stm = $pdo->prepare("SELECT * FROM member WHERE username = ? AND role = 'Member'");
        $stm->execute([$username]);
        $member = $stm->fetch();

        if ($member && is_account_locked($member)) {
            $_err['password'] = 'Too many failed attempts. Try again in ' . login_lock_seconds_remaining($member) . ' second(s).';
        } elseif (!$member || !password_verify($password, $member->password)) {
            if ($member) record_failed_login($member->member_id);
            $_err['password'] = 'Invalid username or password';
        } elseif ($member->status != 'Active') {
            $_err['password'] = 'Your account has been blocked. Please contact support.';
        } elseif (!$member->email_verified) {
            redirect('/member/verify-email.php?email=' . urlencode($member->email));
        } else {
            reset_login_attempts($member->member_id);
            session_regenerate_id(true);
            unset($member->password);
            login($member, '/');
        }
    }
}

$_title = 'Login';
require '../_head.php';
?>

<h1>Login</h1>

<form method="post" novalidate>
    <label for="username">Username</label>
    <?= html_text('username') ?>
    <?= err('username') ?>

    <label for="password">Password</label>
    <div class="pw-field">
        <?= html_password('password') ?>
        <button type="button" class="toggle-pw" data-target="password" tabindex="-1"></button>
    </div>
    <?= err('password') ?>

    <p><a href="/user/forgot-password.php">Forgot password?</a></p>

    <button>Login</button>
</form>

<p>Don't have an account? <a href="/member/register.php">Register here</a></p>

<?php require '../_foot.php'; ?>
