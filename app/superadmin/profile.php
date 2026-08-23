<?php require '../_base.php'; ?>
<?php auth('Super Admin'); ?>
<?php

$_title = 'My Profile';
require '../_head.php';
?>

<h1>My Profile</h1>

<table class="detail">
    <tr><th>Photo</th><td><?= user_avatar($_user, 60) ?></td></tr>
    <tr><th>Username</th><td><?= h($_user->username) ?></td></tr>
    <tr><th>Email</th><td><?= h($_user->email) ?></td></tr>
    <tr><th>Phone</th><td><?= h($_user->phone) ?></td></tr>
    <tr><th>Role</th><td><?= h($_user->role) ?></td></tr>
</table>

<p>
    <a href="/superadmin/edit.php">Edit Profile</a> |
    <a href="/superadmin/password.php">Change Password</a>
</p>

<?php require '../_foot.php'; ?>
