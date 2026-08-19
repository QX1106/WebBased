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

        if (!$member || !password_verify($password, $member->password)) {
            $_err['password'] = 'Invalid username or password';
        } elseif ($member->status != 'Active') {
            $_err['password'] = 'Your account has been blocked. Please contact support.';
        } elseif (!$member->email_verified) {
            redirect('/member/verify-email.php?email=' . urlencode($member->email));
        } else {
            session_regenerate_id(true);
            unset($member->password);
            login($member, '/');
        }
    }
}

$_title = 'Login';
require '../_head.php';
?>

<?php
$eye_open = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>';
$eye_closed = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3l18 18"/><path d="M10.6 10.6a3 3 0 0 0 4.2 4.2"/><path d="M9.9 5.1A11 11 0 0 1 12 5c7 0 11 7 11 7a13.4 13.4 0 0 1-3.1 3.9M6.6 6.6C3.9 8.3 2 12 2 12s4 7 11 7c1.4 0 2.6-.2 3.7-.6"/></svg>';
?>

<h1>Login</h1>

<form method="post" novalidate>
    <label for="username">Username</label>
    <?= html_text('username') ?>
    <?= err('username') ?>

    <label for="password">Password</label>
    <div class="pw-field">
        <?= html_password('password') ?>
        <button type="button" class="toggle-pw" data-target="password" tabindex="-1"><?= $eye_open ?></button>
    </div>
    <?= err('password') ?>

    <p><a href="/user/forgot-password.php">Forgot password?</a></p>

    <button>Login</button>
</form>

<p>Don't have an account? <a href="/member/register.php">Register here</a></p>

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
