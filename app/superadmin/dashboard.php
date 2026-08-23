<?php require '../_base.php'; ?>
<?php auth('Super Admin'); ?>
<?php

$admin_stats = $pdo->query("SELECT
        COUNT(*) AS total,
        SUM(status = 'Active') AS active,
        SUM(status = 'Blocked') AS blocked
    FROM member WHERE role = 'Admin'")->fetch();

$member_total = (int) $pdo->query("SELECT COUNT(*) FROM member WHERE role = 'Member'")->fetchColumn();

$recent_admins = $pdo->query("SELECT id, username, email, status, created_at
                               FROM member
                               WHERE role = 'Admin'
                               ORDER BY created_at DESC
                               LIMIT 5")->fetchAll();

$_title = 'Super Admin Dashboard';
require '../_head.php';
?>

<h1>Super Admin Dashboard</h1>

<h2>Admins</h2>
<div class="stats">
    <div class="stat"><b><?= $admin_stats->total ?></b><span>Total Admins</span></div>
    <div class="stat"><b><?= $admin_stats->active ?></b><span>Active</span></div>
    <div class="stat"><b><?= $admin_stats->blocked ?></b><span>Blocked</span></div>
</div>
<p><a href="/superadmin/admins/list.php">Manage Admins</a></p>

<h2>Members</h2>
<div class="stats">
    <div class="stat"><b><?= $member_total ?></b><span>Total Members</span></div>
</div>
<p><a href="/member/list.php">View Member Maintenance</a></p>

<h2>Recently Added Admins</h2>
<table class="table">
    <tr>
        <th>Username</th>
        <th>Email</th>
        <th>Status</th>
        <th>Created</th>
        <th></th>
    </tr>
    <?php foreach ($recent_admins as $row): ?>
    <tr>
        <td><?= h($row->username) ?></td>
        <td><?= h($row->email) ?></td>
        <td><?= h($row->status) ?></td>
        <td><?= h($row->created_at) ?></td>
        <td><a href="/superadmin/admins/edit.php?id=<?= $row->id ?>">Manage</a></td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$recent_admins): ?>
    <tr><td colspan="5">No admins yet.</td></tr>
    <?php endif; ?>
</table>

<?php require '../_foot.php'; ?>
