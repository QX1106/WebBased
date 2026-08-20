<?php require '../_base.php'; ?>
<?php auth('Admin'); ?>
<?php

$username = $_user->username;
$email = $_user->email;
$phone = $_user->phone;

$_err = [];

if (is_post()) {
    $username = post('username');
    $email = post('email');
    $phone = post('phone');

    if (!$username) {
        $_err['username'] = 'Username is required';
    } elseif (!is_unique('member', 'username', $username, $_user->member_id, 'member_id')) {
        $_err['username'] = 'Username is already taken';
    }

    if (!$email) {
        $_err['email'] = 'Email is required';
    } elseif (!is_email($email)) {
        $_err['email'] = 'Invalid email format';
    } elseif (!is_unique('member', 'email', $email, $_user->member_id, 'member_id')) {
        $_err['email'] = 'Email is already registered';
    }

    if (!$phone) {
        $_err['phone'] = 'Phone number is required';
    } elseif (!preg_match('/^(\+?60|0)[0-9]{8,10}$/', str_replace([' ', '-'], '', $phone))) {
        $_err['phone'] = 'Must be a valid Malaysian phone number, e.g. 012-3456789';
    }

    $photo = $_user->photo;
    $file = get_file('photo');

    if ($file) {
        if (!str_starts_with($file->type, 'image/')) {
            $_err['photo'] = 'Must be an image file';
        } elseif ($file->size > 3 * 1024 * 1024) {
            $_err['photo'] = 'Max size 3MB';
        } elseif (!getimagesize($file->tmp_name)) {
            $_err['photo'] = 'File is not a valid image';
        } else {
            $photo = save_photo($file, 'uploads/member');
        }
    }

    if (!$_err) {
        $pdo->prepare("UPDATE member SET username = ?, email = ?, phone = ?, photo = ?, updated_at = NOW() WHERE member_id = ?")
            ->execute([$username, $email, $phone, $photo, $_user->member_id]);

        // sync
        $_user->username = $username;
        $_user->email = $email;
        $_user->phone = $phone;
        $_user->photo = $photo;
        $_SESSION['user'] = $_user;

        temp('info', 'Profile updated successfully.');
        redirect('/admin/profile.php');
    }
}

$_title = 'Edit Profile';
require '../_head.php';
?>

<h1>Edit Profile</h1>

<form method="post" enctype="multipart/form-data" novalidate>
    <label>Profile Photo</label>
    <?= err('photo') ?>
    <div class="photo-drop" tabindex="0" role="button" aria-label="Upload profile photo">
        <img src="<?= $_user->photo ? '/uploads/member/' . h($_user->photo) : '' ?>" <?= $_user->photo ? '' : 'style="display:none"' ?>>
        <div class="photo-drop-hint" <?= $_user->photo ? 'style="display:none"' : '' ?>>Drag &amp; drop a photo here, or click to browse<br><small>Max 3MB</small></div>
        <?= html_file('photo', 'image/*', "style='display:none'") ?>
        <button type="button" class="photo-drop-clear">✕ Clear selection</button>
    </div>

    <label for="username">Username</label>
    <?= html_text('username') ?>
    <?= err('username') ?>

    <label for="email">Email</label>
    <?= html_text('email') ?>
    <?= err('email') ?>

    <label for="phone">Phone Number</label>
    <?= html_text('phone') ?>
    <?= err('phone') ?>

    <button>Save Changes</button>
    <a href="/admin/profile.php">Cancel</a>
</form>

<?php require '../_foot.php'; ?>
