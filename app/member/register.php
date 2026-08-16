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
    } elseif (strlen($password) < 6 || strlen($password) > 100) {
        $_err['password'] = 'Between 6-100 characters';
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

        $stm = $pdo->prepare("INSERT INTO member (username, email, password, phone, address, photo, role, status, created_at)
                               VALUES (?, ?, ?, ?, ?, ?, 'Member', 'Active', NOW())");
        $stm->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT), $phone, $address, $photo]);

        temp('info', 'Registration successful. Please login.');
        redirect('/user/login.php');
    }
}

?>
<?php require '../_head.php'; ?>

<h1>Member Registration</h1>

<form method="post" enctype="multipart/form-data" novalidate>

    <label for="username">Username</label>
    <?= err('username') ?>
    <?= html_text('username', "maxlength='50' autofocus") ?>

    <label for="email">Email</label>
    <?= err('email') ?>
    <?= html_text('email', "maxlength='100'") ?>

    <label for="password">Password</label>
    <?= err('password') ?>
    <?= html_password('password', "maxlength='100'") ?>

    <label for="confirm">Confirm Password</label>
    <?= err('confirm') ?>
    <?= html_password('confirm', "maxlength='100'") ?>

    <label for="phone">Phone</label>
    <?= err('phone') ?>
    <?= html_text('phone', "maxlength='20'") ?>

    <label for="address">Address</label>
    <?= err('address') ?>
    <?= html_textarea('address', "maxlength='255'") ?>

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
