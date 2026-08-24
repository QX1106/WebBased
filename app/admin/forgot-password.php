<?php require '../_base.php'; ?>
<?php

if ($_user && $_user->role == 'Admin') redirect('/dashboard.php');
if ($_user && $_user->role == 'Super Admin') redirect('/superadmin/dashboard.php');

$_err = [];
$reset_link = null;
$email_sent = false;

if (is_post()) {
    $email = post('email');

    if (!$email) {
        $_err['email'] = 'Email is required';
    } elseif (!is_email($email)) {
        $_err['email'] = 'Invalid email format';
    }

    if (!$_err) {
        $stm = $pdo->prepare("SELECT member_id, role FROM member WHERE email = ? AND role IN ('Admin', 'Super Admin')");
        $stm->execute([$email]);
        $admin = $stm->fetch();

        // Same message either way, so we don't reveal which emails are registered.
        if ($admin) {
            $token = bin2hex(random_bytes(32));
            $pdo->prepare("UPDATE member SET reset_token = ?, reset_expires = NOW() + INTERVAL 1 HOUR WHERE member_id = ?")
                ->execute([$token, $admin->member_id]);
            $reset_link = base("user/reset-password.php?token=$token");

            $email_sent = send_email(
                $email,
                'Reset your password - Stationary Online Store',
                "<p>We received a request to reset your admin password.</p><p><a href=\"$reset_link\">Click here to reset your password</a></p><p>This link expires in 1 hour. If you didn't request this, you can ignore this email.</p>"
            );
        }
    }
}

$_title = 'Admin - Forgot Password';
require '../_head.php';
?>

<h1>Forgot Password</h1>

<?php if (is_post() && !$_err && $email_sent): ?>
    <p>If that email is registered, a reset link has been emailed to it. Check your inbox (and spam folder).</p>
<?php elseif ($reset_link && !$email_sent): ?>
    <p>If that email is registered, we tried to email a reset link but it didn't go through.</p>
    <p style="background:#fff3cd; border:1px solid #ffe08a; padding:8px 12px;">
        <strong>Dev mode:</strong> the email failed to send, so here's the link directly so you can still test:
    </p>
    <p><a href="<?= h($reset_link) ?>"><?= h($reset_link) ?></a></p>
<?php elseif (is_post() && !$_err): ?>
    <p>If that email is registered, a reset link has been generated.</p>
<?php else: ?>
    <p>Enter your admin account email and we'll help you reset your password.</p>
    <form method="post" novalidate>
        <label for="email">Email</label>
        <?= html_text('email', "placeholder='you@example.com'") ?>
        <?= err('email') ?>

        <button>Send Reset Link</button>
    </form>
<?php endif; ?>

<p><a href="/admin/login.php">Back to Admin Login</a></p>

<?php require '../_foot.php'; ?>
