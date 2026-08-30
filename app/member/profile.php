<?php require '../_base.php'; ?>
<?php auth('Member'); ?>
<?php

$stm = $pdo->prepare("SELECT * FROM orders WHERE member_id = ? ORDER BY order_date DESC LIMIT 10");
$stm->execute([$_user->member_id]);
$orders = $stm->fetchAll();

$stm = $pdo->prepare("SELECT * FROM member_address WHERE member_id = ? AND is_default = 1 LIMIT 1");
$stm->execute([$_user->member_id]);
$default_address = $stm->fetch();

$_title = 'My Profile';
require '../_head.php';
?>

<h1>My Profile</h1>

<table class="detail">
    <tr><th>Photo</th><td><?= user_avatar($_user, 60) ?></td></tr>
    <tr><th>Username</th><td><?= h($_user->username) ?></td></tr>
    <tr><th>Email</th><td><?= h($_user->email) ?></td></tr>
    <tr><th>Phone</th><td><?= h($_user->phone) ?></td></tr>
    <tr>
        <th>Address</th>
        <td>
            <?= $default_address
                ? nl2br(h($default_address->address))
                : 'No default address set.'
            ?>
        </td>
    </tr>
</table>

<p>
    <a href="/member/edit-profile.php">Edit Profile</a> |
    <a href="/member/password.php">Change Password</a> |
    <a href="/member/address/list.php">My Addresses</a>
</p>

<div style="display: flex; justify-content: space-between; align-items: center; margin: 40px 0 20px;">
    <h2 style="margin: 0;">Order History</h2>
    <a href="/order/history.php" style="text-decoration: underline;">View all</a>
</div>
<table class="table">
    <tr>
        <th>Order ID</th>
        <th>Date</th>
        <th>Status</th>
        <th>Total</th>
    </tr>
    <?php foreach ($orders as $row): ?>
    <tr>
        <td>#<?= $row->order_id ?></td>
        <td><?= h($row->order_date) ?></td>
        <td><?= h($row->order_status) ?></td>
        <td>RM <?= number_format($row->total_amount, 2) ?></td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$orders): ?>
    <tr><td colspan="4">You have no orders yet.</td></tr>
    <?php endif; ?>
</table>

<?php require '../_foot.php'; ?>
