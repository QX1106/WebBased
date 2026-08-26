<?php require '../../_base.php'; ?>
<?php auth('Super Admin'); ?>
<?php

$id = get('id');

// Deliberately scoped to role = Admin only — Super Admin accounts are
// never editable through this page, even by another Super Admin.
$stm = $pdo->prepare("SELECT * FROM member WHERE member_id = ? AND role = 'Admin'");
$stm->execute([$id]);
$admin = $stm->fetch();

if (!$admin) {
    temp('info', 'Admin not found.');
    redirect('/superadmin/admins/list.php');
}

$_err = [];
$action = post('action');

if (is_post() && $action == 'toggle_status') {
    $new_status = $admin->status == 'Active' ? 'Blocked' : 'Active';
    $pdo->prepare("UPDATE member SET status = ? WHERE member_id = ?")->execute([$new_status, $admin->member_id]);
    $admin->status = $new_status;
    temp('info', 'Admin has been ' . ($new_status == 'Active' ? 'activated.' : 'deactivated.'));
    redirect("/superadmin/admins/edit.php?id=$id");
}

if (is_post() && $action == 'update') {
    $username = post('username');
    $email = post('email');
    $phone = post('phone');

    if (!$username) {
        $_err['username'] = 'Username is required';
    } elseif (!is_unique('member', 'username', $username, $admin->member_id, 'member_id')) {
        $_err['username'] = 'Username is already taken';
    }

    if (!$email) {
        $_err['email'] = 'Email is required';
    } elseif (!is_email($email)) {
        $_err['email'] = 'Invalid email format';
    } elseif (!is_unique('member', 'email', $email, $admin->member_id, 'member_id')) {
        $_err['email'] = 'Email is already registered';
    }

    if (!$phone) {
        $_err['phone'] = 'Phone number is required';
    } elseif (!preg_match('/^(\+?60|0)[0-9]{8,10}$/', str_replace([' ', '-'], '', $phone))) {
        $_err['phone'] = 'Must be a valid Malaysian phone number, e.g. 012-3456789';
    }

    if (!$_err) {
        $pdo->prepare("UPDATE member SET username = ?, email = ?, phone = ?, updated_at = NOW() WHERE member_id = ?")
            ->execute([$username, $email, $phone, $admin->member_id]);
        $admin->username = $username;
        $admin->email = $email;
        $admin->phone = $phone;
        temp('info', 'Admin details updated.');
        redirect("/superadmin/admins/edit.php?id=$id");
    }
}

$username = $admin->username;
$email = $admin->email;
$phone = $admin->phone;

$_title = 'Manage Admin';
require '../../_head.php';
?>

<h1>Manage Admin</h1>

<table class="detail">
    <tr><th>Photo</th><td><?= user_avatar($admin, 60) ?></td></tr>
    <tr><th>Status</th><td><?= h($admin->status) ?></td></tr>
    <tr><th>Registered</th><td><?= h($admin->created_at) ?></td></tr>
</table>

<p>
    <form method="post" style="display:inline; max-width:none; margin:0;">
        <input type="hidden" name="action" value="toggle_status">
        <button><?= $admin->status == 'Active' ? 'Deactivate' : 'Activate' ?> Admin</button>
    </form>
</p>

<h2>Edit Details</h2>
<form method="post" novalidate>
    <input type="hidden" name="action" value="update">

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
</form>

<p><a href="/superadmin/admins/list.php">Back to Admin List</a></p>

<?php require '../../_foot.php'; ?>
