<?php require '../_base.php'; ?>
<?php auth('Admin', 'Super Admin'); ?>
<?php

$search = get('search', '');
$sort = get('sort', 'member_id');
$dir = get('dir', 'asc');

$fields = [
    'ID' => 'member_id',
    'Username' => 'username',
    'Email' => 'email',
    'Phone' => 'phone',
    'Status' => 'status',
    'Registered' => 'created_at',
    'Online' => 'last_active',
];
if (!in_array($sort, $fields)) $sort = 'member_id';
if (!in_array($dir, ['asc', 'desc'])) $dir = 'asc';

// customers account only
$query = "SELECT * FROM member WHERE role = 'Member' AND (username LIKE ? OR email LIKE ? OR phone LIKE ?) ORDER BY $sort $dir";
$params = ["%$search%", "%$search%", "%$search%"];

$page = get('page', 1);
$pager = new SimplePager($pdo, $query, $params, 10, $page);

?>
<?php require '../_head.php'; ?>

<h1>Member Maintenance</h1>

<form method="get" class="search-bar">
    <?= html_search('search', "placeholder='Search username, email, or phone'") ?>
    <button type="submit">Search</button>
    <a href="list.php" class="btn-outline">Reset</a>
</form>

<div class="toolbar">
    <div class="export-dropdown">
        <button type="button" class="btn-accent" data-toggle-dropdown>Export ▾</button>
        <div class="dropdown-menu">
            <a href="export.php?search=<?= urlencode($search) ?>">Export as CSV</a>
            <a href="export-pdf.php?search=<?= urlencode($search) ?>">Export as PDF</a>
        </div>
    </div>
    <a href="login_log.php" class="btn-accent">View Login Log</a>
</div>

<p><?= $pager->item_count ?> member(s) found. Page <?= $pager->page ?> of <?= $pager->page_count ?>.</p>

<table class="table">
    <tr>
        <?= table_headers($fields, $sort, $dir, "&search=$search") ?>
        <th>Photo</th>
        <th></th>
    </tr>
    <?php foreach ($pager->result as $m): ?>
        <?php $online = $m->last_active && strtotime($m->last_active) >= strtotime('-10 minutes'); ?>
        <tr>
            <td><?= h($m->member_id) ?></td>
            <td><?= h($m->username) ?></td>
            <td><?= h($m->email) ?></td>
            <td><?= h($m->phone) ?></td>
            <td><?= h($m->status) ?></td>
            <td><?= h($m->created_at) ?></td>
            <td class="<?= $online ? 'online-dot' : 'offline-dot' ?>"><?= $online ? '● Online' : '● Offline' ?></td>
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
