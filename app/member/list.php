<?php require '../_base.php'; ?>
<?php auth('Admin'); ?>
<?php

$search = get('search', '');
$sort = get('sort', 'member_id');
$dir = get('dir', 'asc');

// Whitelist to prevent SQL injection via sort field
$fields = [
    'ID' => 'member_id',
    'Username' => 'username',
    'Email' => 'email',
    'Phone' => 'phone',
    'Status' => 'status',
    'Registered' => 'created_at',
];
if (!in_array($sort, $fields)) $sort = 'member_id';
if (!in_array($dir, ['asc', 'desc'])) $dir = 'asc';

$query = "SELECT * FROM member WHERE username LIKE ? OR email LIKE ? ORDER BY $sort $dir";
$params = ["%$search%", "%$search%"];

$page = get('page', 1);
$pager = new SimplePager($pdo, $query, $params, 10, $page);

?>
<?php require '../_head.php'; ?>

<h1>Member Maintenance</h1>

<form method="get">
    <?= html_search('search', "placeholder='Search username or email'") ?>
    <button type="submit">Search</button>
    <a href="list.php">Reset</a>
    <a href="export.php?search=<?= urlencode($search) ?>">Export CSV</a>
</form>

<p><?= $pager->item_count ?> member(s) found. Page <?= $pager->page ?> of <?= $pager->page_count ?>.</p>

<table class="table">
    <tr>
        <?= table_headers($fields, $sort, $dir, "&search=$search") ?>
        <th>Photo</th>
        <th></th>
    </tr>
    <?php foreach ($pager->result as $m): ?>
        <tr>
            <td><?= h($m->member_id) ?></td>
            <td><?= h($m->username) ?></td>
            <td><?= h($m->email) ?></td>
            <td><?= h($m->phone) ?></td>
            <td><?= h($m->status) ?></td>
            <td><?= h($m->created_at) ?></td>
            <td>
                <?php if ($m->photo): ?>
                    <img src="/uploads/member/<?= h($m->photo) ?>" width="40" height="40">
                <?php endif; ?>
            </td>
            <td><a href="detail.php?id=<?= $m->member_id ?>">Detail</a> · <a href="edit.php?id=<?= $m->member_id ?>">Edit</a></td>
        </tr>
    <?php endforeach; ?>
</table>

<?= $pager->links("&search=$search&sort=$sort&dir=$dir") ?>

<?php require '../_foot.php'; ?>
