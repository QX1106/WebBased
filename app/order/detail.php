<?php require '../_base.php'; ?>
<?php auth('Admin'); ?>
<?php

auto_complete_shipped_orders();

$_err = [];
$id = get('id');

// Order status must move forward through a realistic fulfillment flow —
// Completed/Cancelled are final, and you can't jump straight from
// Pending to Completed or go backwards once shipped.
$transitions = [
    'Pending'    => ['Processing', 'Cancelled'],
    'Processing' => ['Shipped', 'Cancelled'],
    'Shipped'    => ['Completed'],
    'Completed'  => [],
    'Cancelled'  => [],
];

$stm = $pdo->prepare("SELECT o.*, m.username, m.email, m.phone, m.address
                       FROM orders o
                       JOIN member m ON o.member_id = m.member_id
                       WHERE o.order_id = ?");
$stm->execute([$id]);
$order = $stm->fetch();

if (!$order) {
    temp('info', 'Order not found.');
    redirect('list.php');
}

$allowed_next = $transitions[$order->order_status] ?? [];

// handle status update (Additional Module: order status update by Admin)
if (is_post()) {
    $new_status = post('order_status');

    // Validate: order_status must be a legal next step from the current status
    if ($new_status == '') {
        $_err['order_status'] = 'Required';
    }
    else if (!in_array($new_status, $allowed_next)) {
        $_err['order_status'] = "Cannot move from {$order->order_status} to $new_status";
    }

    if (!$_err) {
        $stm = $pdo->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
        $stm->execute([$new_status, $id]);

        // Log the change for the status timeline (Additional Module)
        $stm = $pdo->prepare("INSERT INTO order_status_log (order_id, status) VALUES (?, ?)");
        $stm->execute([$id, $new_status]);

        temp('info', 'Order status updated.');
        redirect("detail.php?id=$id");
    }
}

// order items joined with product for name/price display
$stm = $pdo->prepare("SELECT oi.*, p.product_name
                       FROM order_item oi
                       JOIN product p ON oi.product_id = p.product_id
                       WHERE oi.order_id = ?");
$stm->execute([$id]);
$items = $stm->fetchAll();

// Status timeline (Additional Module): "Order Placed" is always synthesized from
// orders.order_date so the timeline never depends on how the order was created.
$stm = $pdo->prepare("SELECT * FROM order_status_log WHERE order_id = ? ORDER BY changed_at ASC");
$stm->execute([$id]);
$log = $stm->fetchAll();

// Sticky field: keep the attempted value on validation error, else blank (nothing pre-selected)
$order_status = $_err ? ($new_status ?? '') : '';

?>
<?php require '../_head.php'; ?>

<h1>Order #<?= h($order->order_id) ?></h1>

<table class="detail">
    <tr><th>Order Date</th><td><?= h($order->order_date) ?></td></tr>
    <tr><th>Member</th><td><?= h($order->username) ?> (<?= h($order->email) ?>) — <?= h($order->phone) ?></td></tr>
    <tr><th>Address</th><td><?= h($order->address) ?></td></tr>
    <tr><th>Total</th><td>RM <?= number_format($order->total_amount, 2) ?></td></tr>
    <tr><th>Status</th><td><?= h($order->order_status) ?></td></tr>
</table>

<h2>Items</h2>
<table class="table">
    <tr><th>Product</th><th>Unit Price</th><th>Quantity</th><th>Subtotal</th></tr>
    <?php foreach ($items as $it): ?>
        <tr>
            <td><?= h($it->product_name) ?></td>
            <td><?= number_format($it->unit_price, 2) ?></td>
            <td><?= h($it->quantity) ?></td>
            <td><?= number_format($it->unit_price * $it->quantity, 2) ?></td>
        </tr>
    <?php endforeach; ?>
</table>

<h2>Status Timeline</h2>
<ul class="timeline">
    <li><b>Order Placed</b><span><?= h($order->order_date) ?></span></li>
    <?php foreach ($log as $l): ?>
        <li><b><?= h($l->status) ?></b><span><?= h($l->changed_at) ?></span></li>
    <?php endforeach; ?>
</ul>

<h2>Update Status</h2>
<?php if ($allowed_next): ?>
    <form method="post">
        <?= html_select('order_status', array_combine($allowed_next, $allowed_next), 'Choose next status') ?>
        <?= err('order_status') ?>
        <button type="submit">Update</button>
    </form>
<?php else: ?>
    <p>This order is <?= h($order->order_status) ?> and cannot be changed further.</p>
<?php endif; ?>

<p><a href="list.php" class="btn-outline">Back to Order Listing</a></p>

<?php require '../_foot.php'; ?>
