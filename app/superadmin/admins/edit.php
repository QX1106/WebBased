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

        temp('info', 'Admin details updated.');
        redirect('/superadmin/admins/list.php');
    }
}

$username = $admin->username;
$email = $admin->email;
$phone = $admin->phone;

$_title = 'Manage Admin';
require '../../_head.php';
?>

<h1>Manage Admin</h1>

<div class="user-chip" style="margin-bottom:24px;">
    <?= user_avatar($admin, 48) ?>
    <div>
        <strong style="display:block;"><?= h($admin->username) ?></strong>
        <span style="color:var(--muted); font-size:13px;">
            <?= h($admin->status) ?> · Registered <?= h($admin->created_at) ?>
        </span>
    </div>
</div>

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
    <a href="/superadmin/admins/list.php">Cancel</a>
</form>

<h2>Account Status</h2>
<p style="color:var(--muted); max-width:480px;">
    <?php if ($admin->status == 'Active'): ?>
        This admin can currently log in and use the admin dashboard. Deactivating blocks their access immediately without deleting the account.
    <?php else: ?>
        This admin is currently blocked from logging in. Activating restores their access immediately.
    <?php endif; ?>
</p>
<form method="post" style="max-width:none;">
    <input type="hidden" name="action" value="toggle_status">
    <button class="<?= $admin->status == 'Active' ? 'btn-danger' : '' ?>"
            data-confirm="<?= $admin->status == 'Active' ? 'Deactivate this admin account?' : 'Activate this admin account?' ?>">
        <?= $admin->status == 'Active' ? 'Deactivate Admin' : 'Activate Admin' ?>
    </button>
</form>

<p style="margin-top:32px;"><a href="/superadmin/admins/list.php" class="btn-outline">Back to Admin List</a></p>

<?php require '../../_foot.php'; ?>
