<?php require '../_base.php'; ?>
<?php auth('Admin', 'Super Admin'); ?>
<?php

$id = get('id');

$stm = $pdo->prepare("SELECT * FROM member WHERE member_id = ?");
$stm->execute([$id]);
$m = $stm->fetch();

if (!$m) {
    temp('info', 'Member not found.');
    redirect('list.php');
}

// block/unblock member
if (is_post() && post('action') == 'toggle_status') {
    $new_status = $m->status == 'Blocked' ? 'Active' : 'Blocked';
    $stm = $pdo->prepare("UPDATE member SET status = ? WHERE member_id = ?");
    $stm->execute([$new_status, $id]);

    if ($_user && $id == $_user->member_id) {
        $_user->status = $new_status;
        $_SESSION['user'] = $_user;
    }

    temp('info', "Member $new_status.");
    redirect("detail.php?id=$id");
}

// Member's order history
$stm = $pdo->prepare("SELECT * FROM orders WHERE member_id = ? ORDER BY order_date DESC");
$stm->execute([$id]);
$orders = $stm->fetchAll();

// Customer's total order
$order_count = count($orders);
$total_spent = 0;
foreach ($orders as $o) {
    if ($o->order_status != 'Cancelled') {
        $total_spent += $o->total_amount;
    }
}
$last_order_date = $orders ? $orders[0]->order_date : null;

?>
<?php require '../_head.php'; ?>

<h1>Member Detail</h1>

<?php if ($m->photo): ?>
    <img src="/uploads/member/<?= h($m->photo) ?>" width="120" height="120">
<?php endif; ?>

<table class="detail">
    <tr><th>ID</th><td><?= h($m->member_id) ?></td></tr>
    <tr><th>Username</th><td><?= h($m->username) ?></td></tr>
    <tr><th>Email</th><td><?= h($m->email) ?></td></tr>
    <tr><th>Phone</th><td><?= h($m->phone) ?></td></tr>
    <tr><th>Address</th><td><?= h($m->address) ?></td></tr>
    <tr><th>Role</th><td><?= h($m->role) ?></td></tr>
    <tr><th>Status</th><td><?= h($m->status) ?></td></tr>
    <tr><th>Registered</th><td><?= h($m->created_at) ?></td></tr>
</table>

<p><a href="edit.php?id=<?= $m->member_id ?>" class="btn-outline">Edit Member</a> <a href="/member/address/manage.php?id=<?= $m->member_id ?>" class="btn-outline">Manage Addresses</a></p>

<form method="post">
    <input type="hidden" name="action" value="toggle_status">
    <button type="submit" class="<?= $m->status == 'Blocked' ? '' : 'btn-danger' ?>" data-confirm="<?= $m->status == 'Blocked' ? 'Unblock this member?' : 'Block this member?' ?>">
        <?= $m->status == 'Blocked' ? 'Unblock Member' : 'Block Member' ?>
    </button>
</form>

<h2>Order History</h2>
<div class="stats">
    <a class="stat" href="../order/list.php?search=<?= urlencode($m->username) ?>"><b><?= $order_count ?></b><span>Total Orders</span></a>
    <a class="stat" href="../order/list.php?search=<?= urlencode($m->username) ?>"><b>RM <?= number_format($total_spent, 2) ?></b><span>Total Spent</span></a>
    <a class="stat" href="../order/list.php?search=<?= urlencode($m->username) ?>"><b><?= $last_order_date ? h($last_order_date) : '—' ?></b><span>Last Order</span></a>
</div>
<?php if ($orders): ?>
    <table class="table">
        <tr><th>Order ID</th><th>Order Date</th><th>Total (RM)</th><th>Status</th><th></th></tr>
        <?php foreach ($orders as $o): ?>
            <tr>
                <td><?= h($o->order_id) ?></td>
                <td><?= h($o->order_date) ?></td>
                <td><?= number_format($o->total_amount, 2) ?></td>
                <td><?= h($o->order_status) ?></td>
                <td><a href="../order/detail.php?id=<?= $o->order_id ?>">Detail</a></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p>This member has not placed any order yet.</p>
<?php endif; ?>

<p><a href="list.php" class="btn-outline">Back to Member Listing</a></p>

<?php require '../_foot.php'; ?>
