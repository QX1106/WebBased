<?php require '../_base.php'; ?>
<?php

$_err = [];

if (is_post()) {

    $username = post('username');
    $email = post('email');
    $password = post('password');
    $confirm = post('confirm');
    $phone = post('phone');
    $address = post('address');

    if ($username == '') {
        $_err['username'] = 'Required';
    } elseif (strlen($username) > 50) {
        $_err['username'] = 'Maximum 50 characters';
    } elseif (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) {
        $_err['username'] = 'Only letters, numbers, underscore (min 3 characters)';
    } elseif (!is_unique('member', 'username', $username)) {
        $_err['username'] = 'Username already taken';
    }

    if ($email == '') {
        $_err['email'] = 'Required';
    } elseif (strlen($email) > 100) {
        $_err['email'] = 'Maximum 100 characters';
    } elseif (!is_email($email)) {
        $_err['email'] = 'Invalid email format';
    } elseif (!is_unique('member', 'email', $email)) {
        $_err['email'] = 'Email already registered';
    }

    if ($password == '') {
        $_err['password'] = 'Required';
    } elseif (strlen($password) > 100) {
        $_err['password'] = 'Maximum 100 characters';
    } elseif (!is_strong_password($password)) {
        $_err['password'] = 'At least 8 characters, with an uppercase letter, a number, and a symbol';
    }

    if ($confirm == '') {
        $_err['confirm'] = 'Required';
    } elseif ($confirm !== $password) {
        $_err['confirm'] = 'Passwords do not match';
    }

    if ($phone == '') {
        $_err['phone'] = 'Required';
    } elseif (strlen($phone) > 20) {
        $_err['phone'] = 'Maximum 20 characters';
    } elseif (!preg_match('/^(\+?60|0)[0-9]{8,10}$/', preg_replace('/[\s\-]/', '', $phone))) {
        $_err['phone'] = 'Must be a valid Malaysian phone number, e.g. 012-3456789 or +60123456789';
    }

    if ($address != '' && strlen($address) > 255) {
        $_err['address'] = 'Maximum 255 characters';
    }

    $f = get_file('photo');
    if ($f) {
        if (!str_starts_with($f->type, 'image/')) {
            $_err['photo'] = 'Must be an image file';
        } elseif ($f->size > 3 * 1024 * 1024) {
            $_err['photo'] = 'Max size 3MB';
        } elseif (!getimagesize($f->tmp_name)) {
            $_err['photo'] = 'File is not a valid image';
        }
    }

    if (!$_err) {
        $photo = $f ? save_photo($f, 'uploads/member', 200, 200) : null;

        // OTP
        $otp = strval(random_int(100000, 999999));

        $stm = $pdo->prepare("INSERT INTO member (username, email, password, phone, address, photo, role, status, email_verified, email_otp, email_otp_expires, created_at)
                               VALUES (?, ?, ?, ?, ?, ?, 'Member', 'Active', 0, ?, NOW() + INTERVAL 15 MINUTE, NOW())");
        $stm->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT), $phone, $address, $photo, $otp]);

        send_email(
            $email,
            'Verify your email - Stationary Online Store',
            "<p>Hi " . h($username) . ",</p><p>Your verification code is:</p><h2>$otp</h2><p>This code expires in 15 minutes.</p>"
        );

        redirect('/member/verify-email.php?email=' . urlencode($email));
    }
}

?>
<?php require '../_head.php'; ?>

<h1>Member Registration</h1>

<form method="post" enctype="multipart/form-data" novalidate>

    <label for="username">Username</label>
    <?= err('username') ?>
    <?= html_text('username', "maxlength='50' autofocus data-check-available='username' placeholder='Choose a username'") ?>

    <label for="email">Email</label>
    <?= err('email') ?>
    <?= html_text('email', "maxlength='100' data-check-available='email' placeholder='you@example.com'") ?>

    <label for="password">Password</label>
    <?= err('password') ?>
    <?= html_password('password', "maxlength='100' placeholder='8+ chars, 1 uppercase, 1 number, 1 symbol'") ?>

    <label for="confirm">Confirm Password</label>
    <?= err('confirm') ?>
    <?= html_password('confirm', "maxlength='100' placeholder='Re-enter your password'") ?>

    <label for="phone">Phone</label>
    <?= err('phone') ?>
    <?= html_text('phone', "maxlength='20' placeholder='012-3456789'") ?>

    <label for="address">Address (Optional)</label>
    <?= err('address') ?>
    <?= html_textarea('address', "maxlength='255' placeholder='Street, city, state, postcode'") ?>
    <small>You can skip this now and add it later from your Profile page.</small>

    <label>Profile Photo</label>
    <?= err('photo') ?>
    <div class="photo-drop" tabindex="0" role="button" aria-label="Upload profile photo">
        <img style="display:none">
        <div class="photo-drop-hint">Drag &amp; drop a photo here, or click to browse<br><small>Max 3MB</small></div>
        <?= html_file('photo', 'image/*', "style='display:none'") ?>
        <button type="button" class="photo-drop-clear">✕ Clear selection</button>
    </div>

    <br><br>
    <button type="submit">Register</button>
    <button type="reset">Reset</button>
</form>

<?php require '../_foot.php'; ?>