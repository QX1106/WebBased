<?php require '../_base.php'; ?>
<?php auth('Admin'); ?>
<?php

$_err = [];
$id = get('id');

$stm = $pdo->prepare("SELECT * FROM member WHERE member_id = ?");
$stm->execute([$id]);
$m = $stm->fetch();

if (!$m) {
    temp('info', 'Member not found.');
    redirect('list.php');
}

$photo = $m->photo;

if (is_get()) {
    $username = $m->username;
    $email = $m->email;
    $phone = $m->phone;
    $address = $m->address;
}

if (is_post()) {
    $username = post('username');
    $email = post('email');
    $phone = post('phone');
    $address = post('address');
    $f = get_file('photo');
    $remove_photo = post('remove_photo');

    // Validate: username
    if ($username == '') {
        $_err['username'] = 'Required';
    } elseif (strlen($username) > 50) {
        $_err['username'] = 'Maximum 50 characters';
    } elseif (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) {
        $_err['username'] = 'Only letters, numbers, underscore (min 3 characters)';
    } elseif (!is_unique('member', 'username', $username, $id, 'member_id')) {
        $_err['username'] = 'Username already taken';
    }

    // Validate: email
    if ($email == '') {
        $_err['email'] = 'Required';
    } elseif (strlen($email) > 100) {
        $_err['email'] = 'Maximum 100 characters';
    } elseif (!is_email($email)) {
        $_err['email'] = 'Invalid email format';
    } elseif (!is_unique('member', 'email', $email, $id, 'member_id')) {
        $_err['email'] = 'Email already registered';
    }

    // Validate: phone (Malaysian format — mobile/landline, optional +60/60/0 prefix)
    if ($phone == '') {
        $_err['phone'] = 'Required';
    } elseif (strlen($phone) > 20) {
        $_err['phone'] = 'Maximum 20 characters';
    } elseif (!preg_match('/^(\+?60|0)[0-9]{8,10}$/', preg_replace('/[\s\-]/', '', $phone))) {
        $_err['phone'] = 'Must be a valid Malaysian phone number, e.g. 012-3456789 or +60123456789';
    }

    // Validate: address (optional, but bounded by DB column width)
    if ($address != '' && strlen($address) > 255) {
        $_err['address'] = 'Maximum 255 characters';
    }

    // Validate: photo (optional — only checked if a new file is selected)
    if ($f) {
        if (!str_starts_with($f->type, 'image/')) {
            $_err['photo'] = 'Must be an image file';
        } elseif ($f->size > 1 * 1024 * 1024) {
            $_err['photo'] = 'Max size 1MB';
        } elseif (!getimagesize($f->tmp_name)) {
            $_err['photo'] = 'File is not a valid image';
        }
    }

    if (!$_err) {
        $new_photo = $m->photo;

        // Replace photo only if a new file was uploaded; otherwise honor "remove photo"
        if ($f) {
            if ($m->photo && file_exists(root("uploads/member/{$m->photo}"))) {
                unlink(root("uploads/member/{$m->photo}"));
            }
            $new_photo = save_photo($f, 'uploads/member', 200, 200);
        } elseif ($remove_photo && $m->photo) {
            if (file_exists(root("uploads/member/{$m->photo}"))) {
                unlink(root("uploads/member/{$m->photo}"));
            }
            $new_photo = null;
        }

        $stm = $pdo->prepare("UPDATE member SET username = ?, email = ?, phone = ?, address = ?, photo = ? WHERE member_id = ?");
        $stm->execute([$username, $email, $phone, $address, $new_photo, $id]);

        // If the admin edited their own account, refresh the session copy too
        // (nav/sidebar reads $_user from the session, not a fresh DB query)
        if ($_user && $id == $_user->member_id) {
            $stm = $pdo->prepare("SELECT * FROM member WHERE member_id = ?");
            $stm->execute([$id]);
            $_SESSION['user'] = $stm->fetch();
        }

        temp('info', 'Member updated.');
        redirect("detail.php?id=$id");
    }
}

?>
<?php require '../_head.php'; ?>

<h1>Edit Member</h1>

<form method="post" enctype="multipart/form-data" novalidate>

    <label for="username">Username</label>
    <?= err('username') ?>
    <?= html_text('username', "maxlength='50' autofocus") ?>

    <label for="email">Email</label>
    <?= err('email') ?>
    <?= html_text('email', "maxlength='100'") ?>

    <label for="phone">Phone</label>
    <?= err('phone') ?>
    <?= html_text('phone', "maxlength='20'") ?>

    <label for="address">Address</label>
    <?= err('address') ?>
    <?= html_textarea('address', "maxlength='255'") ?>

    <label for="photo">Profile Photo</label>
    <?= err('photo') ?>
    <?php if ($photo): ?>
        <img src="/uploads/member/<?= h($photo) ?>" width="80" height="80"><br>
        <?= html_checkbox('remove_photo', 'Remove current photo') ?>
    <?php endif; ?>
    <?= html_file('photo', 'image/*') ?>
    <small>Leave empty to keep the current photo.</small>

    <section>
        <button type="submit">Save</button>
        <button type="reset">Reset</button>
    </section>
</form>

<p><a href="detail.php?id=<?= h($id) ?>">Back to Member Detail</a></p>

<?php require '../_foot.php'; ?>
