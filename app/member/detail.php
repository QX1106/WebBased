<?php require '../_base.php'; ?>
<?php auth('Admin'); ?>
<?php

$id = get('id');

$stm = $pdo->prepare("SELECT * FROM member WHERE member_id = ?");
$stm->execute([$id]);
$m = $stm->fetch();

if (!$m) {
    temp('info', 'Member not found.');
    redirect('list.php');
}

?>
<?php require '../_head.php'; ?>

<h1>Member Detail</h1>

<?php if ($m->photo): ?>
    <img src="/uploads/member/<?= h($m->photo) ?>" width="120" height="120">
<?php endif; ?>

<table class="detail">
    <tr><th>ID</th><td><?= h($m->member_id) ?></td></tr>
    <tr><th>Username</th><td><?= h($m->username) ?></td></tr>
    <tr><th>Email</th><td><?= h($m->email) ?></td></tr>
    <tr><th>Phone</th><td><?= h($m->phone) ?></td></tr>
    <tr><th>Address</th><td><?= h($m->address) ?></td></tr>
    <tr><th>Role</th><td><?= h($m->role) ?></td></tr>
    <tr><th>Status</th><td><?= h($m->status) ?></td></tr>
    <tr><th>Registered</th><td><?= h($m->created_at) ?></td></tr>
</table>

<p><a href="list.php">Back to Member Listing</a></p>

<?php require '../_foot.php'; ?>
