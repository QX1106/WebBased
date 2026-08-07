<?php require '../_base.php'; ?>
<?php auth('Admin'); ?>
<?php

$_err = [];
$id = get('id');
$statuses = ['Pending', 'Processing', 'Shipped', 'Completed', 'Cancelled'];

// handle status update (Additional Module: order status update by Admin)
if (is_post()) {
    $new_status = post('order_status');

    // Validate: order_status must be one of the allowed values
    if ($new_status == '') {
        $_err['order_status'] = 'Required';
    }
    else if (!in_array($new_status, $statuses)) {
        $_err['order_status'] = 'Invalid status';
    }

    if (!$_err) {
        $stm = $pdo->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
        $stm->execute([$new_status, $id]);
        temp('info', 'Order status updated.');
        redirect("detail.php?id=$id");
    }
}

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

// order items joined with product for name/price display
$stm = $pdo->prepare("SELECT oi.*, p.product_name
                       FROM order_item oi
                       JOIN product p ON oi.product_id = p.product_id
                       WHERE oi.order_id = ?");
$stm->execute([$id]);
$items = $stm->fetchAll();

// Sticky field: keep the attempted value on validation error, else current DB value
$order_status = $_err ? ($new_status ?? '') : $order->order_status;

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

<h2>Update Status</h2>
<form method="post">
    <?= html_select('order_status', array_combine($statuses, $statuses), null) ?>
    <?= err('order_status') ?>
    <button type="submit">Update</button>
</form>

<p><a href="list.php">Back to Order Listing</a></p>

<?php require '../_foot.php'; ?>
