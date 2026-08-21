<?php require '../_base.php'; ?>
<?php auth('Admin'); ?>
<?php

$status_filter = get('status', 'Pending');
$sort = get('sort', 'requested_at');
$dir = get('dir', 'desc');

$fields = [
    'Request ID'   => 'r.request_id',
    'Order'        => 'r.order_id',
    'Member'       => 'm.username',
    'Requested At' => 'r.requested_at',
];
if (!in_array($sort, $fields)) $sort = 'r.requested_at';
if (!in_array($dir, ['asc', 'desc'])) $dir = 'desc';

$conditions = [];
$params = [];
if (in_array($status_filter, ['Pending', 'Approved', 'Rejected'], true)) {
    $conditions[] = 'r.status = ?';
    $params[] = $status_filter;
}
$where_sql = $conditions ? ('WHERE ' . implode(' AND ', $conditions)) : '';

$query = "SELECT r.*, m.username, m.email
          FROM cancel_request r
          JOIN member m ON r.member_id = m.member_id
          $where_sql
          ORDER BY $sort $dir";

$page = get('page', 1);
$pager = new SimplePager($pdo, $query, $params, 10, $page);

$filter_qs = "&status=" . urlencode($status_filter);

$_title = 'Cancellation Requests';
require '../_head.php';
?>

<h1>Cancellation Requests</h1>

<p class="status-filter">
    <?php foreach (['Pending' => 'Pending', 'Approved' => 'Approved', 'Rejected' => 'Rejected', '' => 'All'] as $val => $label): ?>
        <a href="?status=<?= $val ?>" class="<?= $status_filter === $val ? 'active' : '' ?>"><?= h($label) ?></a>
    <?php endforeach; ?>
</p>

<p><?= $pager->item_count ?> request(s) found.</p>

<table class="table">
    <tr>
        <?= table_headers($fields, $sort, $dir, $filter_qs) ?>
        <th>Reason</th>
        <th>Status</th>
        <th>Actions</th>
    </tr>
    <?php foreach ($pager->result as $r): ?>
        <tr>
            <td>#<?= $r->request_id ?></td>
            <td><a href="/order/detail.php?id=<?= $r->order_id ?>">#<?= $r->order_id ?></a></td>
            <td><?= h($r->username) ?> (<?= h($r->email) ?>)</td>
            <td><?= h($r->requested_at) ?></td>
            <td><?= h(mb_strimwidth($r->reason, 0, 60, '...')) ?></td>
            <td><span class="status-badge status-<?= strtolower($r->status) ?>"><?= h($r->status) ?></span></td>
            <td><a href="/order/cancel-request-review.php?id=<?= $r->request_id ?>">Review</a></td>
        </tr>
    <?php endforeach; ?>

    <?php if (!$pager->result): ?>
        <tr><td colspan="7">No cancellation requests found.</td></tr>
    <?php endif; ?>
</table>

<?= $pager->links("$filter_qs&sort=$sort&dir=$dir") ?>

<p><a href="list.php" class="btn-outline">Back to Order Listing</a></p>

<?php require '../_foot.php'; ?>
