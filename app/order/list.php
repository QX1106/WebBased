<?php require '../_base.php'; ?>
<?php auth('Admin'); ?>
<?php

$status = get('status', '');
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

// join orders with member so admin can see who placed each order
$query = "SELECT o.*, m.username, m.email
          FROM orders o
          JOIN member m ON o.member_id = m.member_id
          WHERE o.order_status = ? OR ?
          ORDER BY $sort $dir";
$params = [$status, $status === ''];

$page = get('page', 1);
$pager = new SimplePager($pdo, $query, $params, 10, $page);

// distinct status list for the filter links (adjust to match your actual enum values)
$statuses = ['Pending', 'Processing', 'Shipped', 'Completed', 'Cancelled'];

?>
<?php require '../_head.php'; ?>

<h1>Order Maintenance (Admin)</h1>

<p>
    <a href="?status=" class="<?= $status === '' ? 'active' : '' ?>">All</a>
    <?php foreach ($statuses as $s): ?>
        | <a href="?status=<?= $s ?>" class="<?= $status === $s ? 'active' : '' ?>"><?= h($s) ?></a>
    <?php endforeach; ?>
</p>

<p><?= $pager->item_count ?> order(s) found.</p>

<table class="table">
    <tr>
        <?= table_headers($fields, $sort, $dir, "&status=$status") ?>
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

<?= $pager->links("&status=$status&sort=$sort&dir=$dir") ?>

<?php require '../_foot.php'; ?>
