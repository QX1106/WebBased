<?php require '../_base.php'; ?>
<?php auth('Admin'); ?>
<?php

// Login Log (Additional Module): every successful login is recorded by the
// shared login() function in _base.php, so this works regardless of how a
// teammate's own login.php checks the password.
$search = get('search', '');
$sort = get('sort', 'login_time');
$dir = get('dir', 'desc');

$fields = [
    'Username' => 'username',
    'Login Time' => 'login_time',
];
if (!in_array($sort, $fields)) $sort = 'login_time';
if (!in_array($dir, ['asc', 'desc'])) $dir = 'desc';

$query = "SELECT l.log_id, l.login_time, m.username, m.email
          FROM login_log l
          JOIN member m ON l.member_id = m.member_id
          WHERE m.username LIKE ? OR m.email LIKE ?
          ORDER BY $sort $dir";
$params = ["%$search%", "%$search%"];

$page = get('page', 1);
$pager = new SimplePager($pdo, $query, $params, 10, $page);

?>
<?php require '../_head.php'; ?>

<h1>Login Log</h1>

<form method="get" class="search-bar">
    <?= html_search('search', "placeholder='Search username or email'") ?>
    <button type="submit">Search</button>
    <a href="login_log.php" class="btn-outline">Reset</a>
</form>

<p><?= $pager->item_count ?> record(s) found. Page <?= $pager->page ?> of <?= $pager->page_count ?>.</p>

<table class="table">
    <tr>
        <?= table_headers($fields, $sort, $dir, "&search=$search") ?>
        <th>Email</th>
    </tr>
    <?php foreach ($pager->result as $l): ?>
        <tr>
            <td><?= h($l->username) ?></td>
            <td><?= h($l->login_time) ?></td>
            <td><?= h($l->email) ?></td>
        </tr>
    <?php endforeach; ?>
</table>

<?= $pager->links("&search=$search&sort=$sort&dir=$dir") ?>

<p><a href="list.php" class="btn-outline">Back to Member Listing</a></p>

<?php require '../_foot.php'; ?>
