<?php require '../../_base.php'; ?>
<?php auth('Super Admin'); ?>
<?php

$fields = [
    'Username'   => 'username',
    'Email'      => 'email',
    'Registered' => 'created_at',
];

$sort = get('sort', 'created_at');
in_array($sort, $fields) || $sort = 'created_at';

$dir = get('dir', 'desc');
in_array($dir, ['asc', 'desc']) || $dir = 'desc';

$username = get('username', '');
$status = get('status', '');

$sql = "SELECT * FROM member
        WHERE role = 'Admin'
          AND username LIKE ?
          AND (status = ? OR ?)
        ORDER BY $sort $dir";
$params = ["%$username%", $status, $status == ''];

$page = get('page', 1);
$p = new SimplePager($pdo, $sql, $params, 10, $page);
$arr = $p->result;

$qs = '&username=' . urlencode($username) . '&status=' . urlencode($status);

$_title = 'Admin Management';
require '../../_head.php';
?>

<h1>Admin Management</h1>

<div class="toolbar">
    <a href="/superadmin/admins/create.php" class="btn-accent">+ Create Admin</a>
    <a href="export.php?username=<?= urlencode($username) ?>" class="btn-accent">Export CSV</a>
</div>

<form method="get" class="filters">
    <div>
        <label for="username">Search Username</label>
        <?= html_search('username') ?>
    </div>
    <div>
        <label for="status">Status</label>
        <?= html_select('status', ['Active' => 'Active', 'Blocked' => 'Blocked'], 'All Status') ?>
    </div>
    <button>Search</button>
    <a href="/superadmin/admins/list.php">Reset</a>
</form>

<p><?= $p->count ?> of <?= $p->item_count ?> record(s) | Page <?= $p->page ?> of <?= $p->page_count ?></p>

<table class="table">
    <tr>
        <th>Photo</th>
        <?= table_headers($fields, $sort, $dir, "$qs&page=$page") ?>
        <th>Status</th>
        <th>Action</th>
    </tr>

    <?php foreach ($arr as $row): ?>
    <tr>
        <td><?= user_avatar($row, 36) ?></td>
        <td><?= h($row->username) ?></td>
        <td><?= h($row->email) ?></td>
        <td><?= h($row->created_at) ?></td>
        <td><?= h($row->status) ?></td>
        <td><a href="/superadmin/admins/edit.php?id=<?= $row->member_id ?>">Manage</a></td>
    </tr>
    <?php endforeach; ?>

    <?php if (!$arr): ?>
    <tr><td colspan="6">No admins found.</td></tr>
    <?php endif; ?>
</table>

<br>

<?= $p->links("&sort=$sort&dir=$dir$qs") ?>

<?php require '../../_foot.php'; ?>
