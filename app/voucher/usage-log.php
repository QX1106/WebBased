<?php require '../_base.php'; ?>
<?php auth('Admin'); ?>
<?php

$search = get('search', '');
$sort = get('sort', 'used_at');
$dir = get('dir', 'desc');

$fields = [
    'Voucher Code' => 'v.code',
    'Member'       => 'm.username',
    'Used At'      => 'u.used_at',
];
if (!in_array($sort, $fields)) $sort = 'u.used_at';
if (!in_array($dir, ['asc', 'desc'])) $dir = 'desc';

$query = "SELECT u.*, v.code, m.username, m.email
          FROM voucher_usage u
          JOIN voucher v ON u.voucher_id = v.voucher_id
          JOIN member m ON u.member_id = m.member_id
          WHERE v.code LIKE ? OR m.username LIKE ? OR m.email LIKE ?
          ORDER BY $sort $dir";
$params = ["%$search%", "%$search%", "%$search%"];

$page = get('page', 1);
$pager = new SimplePager($pdo, $query, $params, 10, $page);

$filter_qs = "&search=" . urlencode($search);

$_title = 'Voucher Usage Log';
require '../_head.php';
?>

<h1>Voucher Usage Log</h1>

<form method="get" class="search-bar">
    <?= html_search('search', "placeholder='Search voucher code, username, or email'") ?>
    <button type="submit">Search</button>
    <a href="usage-log.php" class="btn-outline">Reset</a>
</form>

<p><?= $pager->item_count ?> record(s) found. Page <?= $pager->page ?> of <?= $pager->page_count ?>.</p>

<table class="table">
    <tr>
        <?= table_headers($fields, $sort, $dir, $filter_qs) ?>
        <th>Order</th>
    </tr>
    <?php foreach ($pager->result as $u): ?>
        <tr>
            <td><?= h($u->code) ?></td>
            <td><?= h($u->username) ?> (<?= h($u->email) ?>)</td>
            <td><?= h($u->used_at) ?></td>
            <td><?= $u->order_id ? '<a href="/order/detail.php?id=' . $u->order_id . '">#' . $u->order_id . '</a>' : '—' ?></td>
        </tr>
    <?php endforeach; ?>

    <?php if (!$pager->result): ?>
        <tr><td colspan="4">No voucher usage recorded yet.</td></tr>
    <?php endif; ?>
</table>

<?= $pager->links("$filter_qs&sort=$sort&dir=$dir") ?>

<p><a href="list.php" class="btn-outline">Back to Voucher Listing</a></p>

<?php require '../_foot.php'; ?>
