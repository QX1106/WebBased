<?php require '../_base.php'; ?>
<?php auth('Admin'); ?>
<?php

$search = get('search', '');
$role = get('role', '');
$sort = get('sort', 'login_time');
$dir = get('dir', 'desc');

$fields = [
    'Username' => 'username',
    'Role' => 'role',
    'Login Time' => 'login_time',
];
if (!in_array($sort, $fields)) $sort = 'login_time';
if (!in_array($dir, ['asc', 'desc'])) $dir = 'desc';

$conditions = ['(m.username LIKE ? OR m.email LIKE ?)'];
$params = ["%$search%", "%$search%"];
if ($role !== '') {
    $conditions[] = 'm.role = ?';
    $params[] = $role;
}
$where_sql = 'WHERE ' . implode(' AND ', $conditions);

$query = "SELECT l.log_id, l.login_time, m.username, m.email, m.role
          FROM login_log l
          JOIN member m ON l.member_id = m.member_id
          $where_sql
          ORDER BY $sort $dir";

$page = get('page', 1);
$pager = new SimplePager($pdo, $query, $params, 10, $page);

$filter_qs = "&search=" . urlencode($search) . "&role=" . urlencode($role);

?>
<?php require '../_head.php'; ?>

<h1>Login Log</h1>

<form method="get" class="search-bar">
    <?= html_search('search', "placeholder='Search username or email'") ?>
    <input type="hidden" name="role" value="<?= h($role) ?>">
    <button type="submit">Search</button>
    <a href="login_log.php" class="btn-outline">Reset</a>
</form>

<p class="status-filter">
    <a href="?role=&search=<?= urlencode($search) ?>" class="<?= $role === '' ? 'active' : '' ?>">All</a>
    <?php foreach (['Member', 'Admin'] as $r): ?>
        | <a href="?role=<?= $r ?>&search=<?= urlencode($search) ?>" class="<?= $role === $r ? 'active' : '' ?>"><?= h($r) ?></a>
    <?php endforeach; ?>
</p>

<p><?= $pager->item_count ?> record(s) found. Page <?= $pager->page ?> of <?= $pager->page_count ?>.</p>

<table class="table">
    <tr>
        <?= table_headers($fields, $sort, $dir, $filter_qs) ?>
        <th>Email</th>
    </tr>
    <?php foreach ($pager->result as $l): ?>
        <tr>
            <td><?= h($l->username) ?></td>
            <td><?= h($l->role) ?></td>
            <td><?= h($l->login_time) ?></td>
            <td><?= h($l->email) ?></td>
        </tr>
    <?php endforeach; ?>
</table>

<?= $pager->links("$filter_qs&sort=$sort&dir=$dir") ?>

<p><a href="list.php" class="btn-outline">Back to Member Listing</a></p>

<?php require '../_foot.php'; ?>
