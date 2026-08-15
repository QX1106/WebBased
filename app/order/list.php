<?php require '../_base.php'; ?>
<?php auth('Admin'); ?>
<?php

auto_complete_shipped_orders();

$status = get('status', '');
$search = get('search', '');
$date_from = get('date_from', '');
$date_to = get('date_to', '');
$sort = get('sort', 'order_id');
$dir = get('dir', 'desc');

$fields = [
    'Order ID' => 'order_id',
    'Member' => 'username',
    'Order Date' => 'order_date',
    'Total (RM)' => 'total_amount',
    'Status' => 'order_status',
];
if (!in_array($sort, $fields)) $sort = 'order_id';
if (!in_array($dir, ['asc', 'desc'])) $dir = 'desc';

// Advanced search (Additional Module): filter by member name/email/order id + date range
$conditions = [];
$params = [];

if ($status !== '') {
    $conditions[] = 'o.order_status = ?';
    $params[] = $status;
}
if ($search !== '') {
    $conditions[] = '(m.username LIKE ? OR m.email LIKE ? OR o.order_id = ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = ctype_digit($search) ? $search : 0;
}
if ($date_from !== '' && is_date($date_from)) {
    $conditions[] = 'o.order_date >= ?';
    $params[] = "$date_from 00:00:00";
}
if ($date_to !== '' && is_date($date_to)) {
    $conditions[] = 'o.order_date <= ?';
    $params[] = "$date_to 23:59:59";
}

$where_sql = $conditions ? ('WHERE ' . implode(' AND ', $conditions)) : '';

// join orders with member so admin can see who placed each order
$query = "SELECT o.*, m.username, m.email
          FROM orders o
          JOIN member m ON o.member_id = m.member_id
          $where_sql
          ORDER BY $sort $dir";

$page = get('page', 1);
$pager = new SimplePager($pdo, $query, $params, 10, $page);

// distinct status list for the filter links (adjust to match your actual enum values)
$statuses = ['Pending', 'Processing', 'Shipped', 'Completed', 'Cancelled'];

$filter_qs = "&status=$status&search=$search&date_from=$date_from&date_to=$date_to";

?>
<?php require '../_head.php'; ?>

<h1>Order Maintenance (Admin)</h1>

<form method="get" class="filters">
    <?= html_search('search', "placeholder='Search Order ID, member name or email'") ?>
    <label>From <input type="date" name="date_from" value="<?= h($date_from) ?>"></label>
    <label>To <input type="date" name="date_to" value="<?= h($date_to) ?>"></label>
    <input type="hidden" name="status" value="<?= h($status) ?>">
    <button type="submit">Search</button>
    <a href="list.php" class="btn-outline">Reset</a>
</form>

<div class="toolbar">
    <a href="export.php?<?= h(ltrim($filter_qs, '&')) ?>" class="btn-accent">Export CSV</a>
</div>

<p class="status-filter">
    <a href="?status=&search=<?= urlencode($search) ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>" class="<?= $status === '' ? 'active' : '' ?>">All</a>
    <?php foreach ($statuses as $s): ?>
        | <a href="?status=<?= $s ?>&search=<?= urlencode($search) ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>" class="<?= $status === $s ? 'active' : '' ?>"><?= h($s) ?></a>
    <?php endforeach; ?>
</p>

<p><?= $pager->item_count ?> order(s) found.</p>

<table class="table">
    <tr>
        <?= table_headers($fields, $sort, $dir, $filter_qs) ?>
        <th></th>
    </tr>
    <?php foreach ($pager->result as $o): ?>
        <tr>
            <td><?= h($o->order_id) ?></td>
            <td><?= h($o->username) ?> (<?= h($o->email) ?>)</td>
            <td><?= h($o->order_date) ?></td>
            <td><?= number_format($o->total_amount, 2) ?></td>
            <td><?= h($o->order_status) ?></td>
            <td><a href="detail.php?id=<?= $o->order_id ?>">Detail</a></td>
        </tr>
    <?php endforeach; ?>
</table>

<?= $pager->links("$filter_qs&sort=$sort&dir=$dir") ?>

<?php require '../_foot.php'; ?>
