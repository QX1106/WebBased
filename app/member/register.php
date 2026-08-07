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

    // Validate: username
    if ($username == '') {
        $_err['username'] = 'Required';
    } elseif (strlen($username) > 50) {
        $_err['username'] = 'Maximum 50 characters';
    } elseif (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) {
        $_err['username'] = 'Only letters, numbers, underscore (min 3 characters)';
    } elseif (!is_unique('member', 'username', $username)) {
        $_err['username'] = 'Username already taken';
    }

    // Validate: email
    if ($email == '') {
        $_err['email'] = 'Required';
    } elseif (strlen($email) > 100) {
        $_err['email'] = 'Maximum 100 characters';
    } elseif (!is_email($email)) {
        $_err['email'] = 'Invalid email format';
    } elseif (!is_unique('member', 'email', $email)) {
        $_err['email'] = 'Email already registered';
    }

    // Validate: password
    if ($password == '') {
        $_err['password'] = 'Required';
    } elseif (strlen($password) < 6 || strlen($password) > 100) {
        $_err['password'] = 'Between 6-100 characters';
    }

    // Validate: confirm
    if ($confirm == '') {
        $_err['confirm'] = 'Required';
    } elseif ($confirm !== $password) {
        $_err['confirm'] = 'Passwords do not match';
    }

    // Validate: phone
    if ($phone == '') {
        $_err['phone'] = 'Required';
    } elseif (strlen($phone) > 20) {
        $_err['phone'] = 'Maximum 20 characters';
    } elseif (!preg_match('/^[0-9+\-\s]{7,20}$/', $phone)) {
        $_err['phone'] = 'Invalid phone number format';
    }

    // Validate: address (optional, but bounded by DB column width)
    if ($address != '' && strlen($address) > 255) {
        $_err['address'] = 'Maximum 255 characters';
    }

    $photo = null;
    $f = get_file('photo');
    if ($f) {
        if (!str_starts_with($f->type, 'image/')) {
            $_err['photo'] = 'Must be an image file';
        } elseif ($f->size > 1 * 1024 * 1024) {
            $_err['photo'] = 'Max size 1MB';
        } else {
            $photo = save_photo($f, 'uploads/member', 200, 200);
        }
    }

    if (!$_err) {
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

    <label for="username">Username <?= err('username') ?></label>
    <?= html_text('username', "maxlength='50' autofocus") ?>

    <label for="email">Email <?= err('email') ?></label>
    <?= html_text('email', "maxlength='100'") ?>

    <label for="password">Password <?= err('password') ?></label>
    <?= html_password('password', "maxlength='100'") ?>

    <label for="confirm">Confirm Password <?= err('confirm') ?></label>
    <?= html_password('confirm', "maxlength='100'") ?>

    <label for="phone">Phone <?= err('phone') ?></label>
    <?= html_text('phone', "maxlength='20'") ?>

    <label for="address">Address <?= err('address') ?></label>
    <?= html_textarea('address', "maxlength='255'") ?>

    <label for="photo">Profile Photo <?= err('photo') ?></label>
    <?= html_file('photo', 'image/*') ?>

    <br><br>
    <button type="submit">Register</button>
    <button type="reset">Reset</button>
</form>

<?php require '../_foot.php'; ?>
