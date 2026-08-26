<?php require '../_base.php'; ?>
<?php auth('Admin', 'Super Admin'); ?>
<?php

$search = get('search', '');
$status_filter = get('status', '');
$sort = get('sort', 'created_at');
$dir = get('dir', 'desc');

$fields = [
    'Code'         => 'code',
    'Type'         => 'discount_type',
    'Valid From'   => 'valid_from',
    'Valid Until'  => 'valid_until',
    'Used'         => 'used_count',
];
if (!in_array($sort, $fields)) $sort = 'created_at';
if (!in_array($dir, ['asc', 'desc'])) $dir = 'desc';

$conditions = ['code LIKE ?'];
$params = ["%$search%"];
if ($status_filter == 'Active') {
    $conditions[] = "status = 'Active' AND valid_from <= CURDATE() AND valid_until >= CURDATE()";
} elseif ($status_filter == 'Inactive') {
    $conditions[] = "status = 'Inactive'";
} elseif ($status_filter == 'Expired') {
    $conditions[] = "status = 'Active' AND valid_until < CURDATE()";
} elseif ($status_filter == 'Scheduled') {
    $conditions[] = "status = 'Active' AND valid_from > CURDATE()";
}
$where_sql = 'WHERE ' . implode(' AND ', $conditions);

$query = "SELECT * FROM voucher $where_sql ORDER BY $sort $dir";

$page = get('page', 1);
$pager = new SimplePager($pdo, $query, $params, 10, $page);

$filter_qs = "&search=" . urlencode($search) . "&status=" . urlencode($status_filter);

$_title = 'Voucher Maintenance';
require '../_head.php';
?>

<h1>Voucher Listing</h1>

<p>
    <a href="/voucher/insert.php" class="btn-accent">+ Add New Voucher</a>
    <a href="/voucher/usage-log.php" class="btn-outline">View Usage Log</a>
</p>

<form method="get" class="search-bar">
    <?= html_search('search', "placeholder='Search voucher code'") ?>
    <input type="hidden" name="status" value="<?= h($status_filter) ?>">
    <button type="submit">Search</button>
    <a href="list.php" class="btn-outline">Reset</a>
</form>

<p class="status-filter">
    <?php foreach (['' => 'All', 'Active' => 'Active', 'Scheduled' => 'Scheduled', 'Inactive' => 'Inactive', 'Expired' => 'Expired'] as $val => $label): ?>
        <a href="?status=<?= $val ?>&search=<?= urlencode($search) ?>" class="<?= $status_filter === $val ? 'active' : '' ?>"><?= h($label) ?></a>
    <?php endforeach; ?>
</p>

<p><?= $pager->item_count ?> record(s) found. Page <?= $pager->page ?> of <?= $pager->page_count ?>.</p>

<table class="table">
    <tr>
        <?= table_headers($fields, $sort, $dir, $filter_qs) ?>
        <th>Discount</th>
        <th>Min Spend</th>
        <th>One/Member</th>
        <th>Status</th>
        <th>Actions</th>
    </tr>
    <?php foreach ($pager->result as $v): ?>
        <?php $effective = voucher_effective_status($v); ?>
        <tr>
            <td><?= h($v->code) ?></td>
            <td><?= h($v->discount_type) ?></td>
            <td><?= h($v->valid_from) ?></td>
            <td><?= h($v->valid_until) ?></td>
            <td><?= $v->used_count ?> / <?= $v->max_uses !== null ? $v->max_uses : 'Unlimited' ?></td>
            <td>
                <?php if ($v->discount_type == 'Percentage'): ?>
                    <?= number_format($v->discount_value, 0) ?>%<?= $v->max_discount !== null ? ' (up to RM ' . number_format($v->max_discount, 2) . ')' : '' ?>
                <?php else: ?>
                    RM <?= number_format($v->discount_value, 2) ?>
                <?php endif; ?>
            </td>
            <td>RM <?= number_format($v->min_spend, 2) ?></td>
            <td><?= $v->one_per_member ? 'Yes' : 'No' ?></td>
            <td><span class="status-badge status-<?= strtolower($effective) ?>"><?= h($effective) ?></span></td>
            <td>
                <a href="/voucher/update.php?id=<?= $v->voucher_id ?>">Edit</a> |
                <a href="/voucher/delete.php?id=<?= $v->voucher_id ?>" onclick="return confirm('Delete voucher &quot;<?= h($v->code) ?>&quot;?')">Delete</a>
            </td>
        </tr>
    <?php endforeach; ?>

    <?php if (!$pager->result): ?>
        <tr><td colspan="9">No vouchers found.</td></tr>
    <?php endif; ?>
</table>

<?= $pager->links("$filter_qs&sort=$sort&dir=$dir") ?>

<?php require '../_foot.php'; ?>
