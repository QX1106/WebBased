<?php require '../_base.php'; ?>
<?php

if ($_user) redirect('/');

$_err = [];
$reset_link = null;

if (is_post()) {
    $email = post('email');

    if (!$email) {
        $_err['email'] = 'Email is required';
    } elseif (!is_email($email)) {
        $_err['email'] = 'Invalid email format';
    }

    if (!$_err) {
        $stm = $pdo->prepare("SELECT member_id FROM member WHERE email = ? AND role = 'Member'");
        $stm->execute([$email]);
        $member = $stm->fetch();

        // Same message either way, so we don't reveal which emails are registered.
        if ($member) {
            $token = bin2hex(random_bytes(32));
            $pdo->prepare("UPDATE member SET reset_token = ?, reset_expires = NOW() + INTERVAL 1 HOUR WHERE member_id = ?")
                ->execute([$token, $member->member_id]);
            $reset_link = base("user/reset-password.php?token=$token");

            send_email(
                $email,
                'Reset your password - Stationary Online Store',
                "<p>We received a request to reset your password.</p><p><a href=\"$reset_link\">Click here to reset your password</a></p><p>This link expires in 1 hour. If you didn't request this, you can ignore this email.</p>"
            );
        }
    }
}

$_title = 'Forgot Password';
require '../_head.php';
?>

<h1>Forgot Password</h1>

<?php if ($reset_link): ?>
    <p>If that email is registered, a reset link has been emailed to it.</p>
    <p style="background:#fff3cd; border:1px solid #ffe08a; padding:8px 12px;">
        <strong>Dev mode:</strong> this local server may not have a mail relay configured, so here's the link directly in case the email doesn't arrive:
    </p>
    <p><a href="<?= h($reset_link) ?>"><?= h($reset_link) ?></a></p>
<?php elseif (is_post() && !$_err): ?>
    <p>If that email is registered, a reset link has been generated.</p>
<?php else: ?>
    <p>Enter your account email and we'll help you reset your password.</p>
    <form method="post" novalidate>
        <label for="email">Email</label>
        <?= html_text('email', "placeholder='you@example.com'") ?>
        <?= err('email') ?>

        <button>Send Reset Link</button>
    </form>
<?php endif; ?>

<p><a href="/user/login.php">Back to Login</a></p>

<?php require '../_foot.php'; ?>
