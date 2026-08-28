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
        // Photo is saved to disk now (harmless orphan file if never
        // verified), but the account itself is NOT written to the
        // database yet — only after the OTP is confirmed in
        // verify-email.php. Until then everything lives in the session.
        $photo = $f ? save_photo($f, 'uploads/member', 200, 200) : null;

        $otp = strval(random_int(100000, 999999));

        $_SESSION['pending_registration'] = [
            'username' => $username,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'phone' => $phone,
            'address' => $address,
            'photo' => $photo,
            'otp' => $otp,
            'otp_expires' => time() + 15 * 60, // 15 minutes
        ];

        $sent = send_email(
            $email,
            'Verify your email - Stationary Online Store',
            "<p>Hi " . h($username) . ",</p><p>Your verification code is:</p><h2>$otp</h2><p>This code expires in 15 minutes.</p>"
        );

        // Pass the send result across the redirect so verify-email.php
        // only shows its dev-mode fallback when this actually failed.
        temp('email_sent', $sent ? '1' : '0');

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
    <div class="pw-field">
        <?= html_password('password', "maxlength='100' placeholder='Enter password'") ?>
        <button type="button" class="toggle-pw" data-target="password" tabindex="-1"></button>
    </div>
    <ul class="pw-requirements">
        <li>At least 8 characters</li>
        <li>One uppercase letter</li>
        <li>One number</li>
        <li>One symbol (e.g. ! @ # $ %)</li>
    </ul>

    <label for="confirm">Confirm Password</label>
    <?= err('confirm') ?>
    <div class="pw-field">
        <?= html_password('confirm', "maxlength='100' placeholder='Re-enter your password'") ?>
        <button type="button" class="toggle-pw" data-target="confirm" tabindex="-1"></button>
    </div>

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