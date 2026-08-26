<?php require '../_base.php'; ?>
<?php auth('Super Admin'); ?>
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
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB

        $real_mime = @mime_content_type($file->tmp_name);

        if (!in_array($real_mime, $allowed_types, true)) {
            $_err['photo'] = 'Photo must be a JPG, PNG, or GIF image';
        } elseif ($file->size > $max_size) {
            $_err['photo'] = 'Photo must be smaller than 2MB';
        } else {
            try {
                $photo = save_photo($file, 'uploads/member');
            } catch (Exception $e) {
                error_log('save_photo failed: ' . $e->getMessage());
                $_err['photo'] = 'That file could not be processed as an image. Please try a different photo.';
            }
        }
    }

    if (!$_err) {
        $pdo->prepare("UPDATE member SET username = ?, email = ?, phone = ?, photo = ?, updated_at = NOW() WHERE member_id = ?")
            ->execute([$username, $email, $phone, $photo, $_user->member_id]);

        // Keep the session copy in sync so the sidebar updates immediately.
        $_user->username = $username;
        $_user->email = $email;
        $_user->phone = $phone;
        $_user->photo = $photo;
        $_SESSION['user'] = $_user;

        temp('info', 'Profile updated successfully.');
        redirect('/superadmin/profile.php');
    }
}

$_title = 'Edit Profile';
require '../_head.php';
?>

<h1>Edit Profile</h1>

<form method="post" enctype="multipart/form-data" novalidate>
    <label class="upload">
        <?php if ($_user->photo): ?>
            <img src="/uploads/member/<?= h($_user->photo) ?>" alt="Profile photo">
        <?php else: ?>
            <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120'%3E%3Crect width='120' height='120' fill='%23e4ddd0'/%3E%3C/svg%3E" alt="No photo">
        <?php endif; ?>
        <?= html_file('photo', 'image/jpeg,image/png,image/gif', "style='display:none'") ?>
    </label>
    <small>Click the photo to change it. JPG, PNG, or GIF, up to 2MB.</small>
    <?= err('photo') ?>

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
    <a href="/superadmin/profile.php">Cancel</a>
</form>

<script>
    document.getElementById('photo').addEventListener('change', function () {
        var file = this.files[0];
        var img = this.closest('label').querySelector('img');
        if (file && file.type.startsWith('image/') && img) {
            img.src = URL.createObjectURL(file);
        }
    });
</script>

<?php require '../_foot.php'; ?>
