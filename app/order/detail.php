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

// Build the full timeline (Shopee/Pinduoduo style): show every step in the
// normal flow, including ones not reached yet, instead of only what already
// happened — so progress is visible at a glance.
$sequence = ['Pending' => 'Order Placed', 'Processing' => 'Processing', 'Shipped' => 'Shipped', 'Completed' => 'Completed'];

$reached = ['Pending' => $order->order_date];
$cancelled_at = null;
foreach ($log as $l) {
    if ($l->status == 'Cancelled') {
        $cancelled_at = $l->changed_at;
    } else {
        $reached[$l->status] = $l->changed_at;
    }
}

$timeline = [];
foreach ($sequence as $key => $label) {
    if (isset($reached[$key])) {
        $state = ($key == $order->order_status) ? 'current' : 'done';
        $timeline[] = ['label' => $label, 'time' => $reached[$key], 'state' => $state];
    } elseif ($cancelled_at === null) {
        $timeline[] = ['label' => $label, 'time' => null, 'state' => 'future'];
    } else {
        // order was cancelled before reaching this step — stop here,
        // don't show steps that will now never happen
        break;
    }
}
if ($cancelled_at !== null) {
    $timeline[] = ['label' => 'Cancelled', 'time' => $cancelled_at, 'state' => 'cancelled'];
}

// Sticky field: keep the attempted value on validation error, else blank (nothing pre-selected)
$order_status = $_err ? ($new_status ?? '') : '';

?>
<?php require '../_head.php'; ?>

<h1 class="no-print">Order #<?= h($order->order_id) ?></h1>

<p class="no-print"><button type="button" class="btn-accent" data-print>Download Receipt</button></p>

<table class="detail no-print">
    <tr><th>Order Date</th><td><?= h($order->order_date) ?></td></tr>
    <tr><th>Member</th><td><?= h($order->username) ?> (<?= h($order->email) ?>) — <?= h($order->phone) ?></td></tr>
    <tr><th>Address</th><td><?= h($order->address) ?></td></tr>
    <tr><th>Total</th><td>RM <?= number_format($order->total_amount, 2) ?></td></tr>
    <tr><th>Status</th><td><?= h($order->order_status) ?></td></tr>
</table>

<h2 class="no-print">Items</h2>
<table class="table no-print">
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

<!-- Print-only receipt layout (hidden on screen, shown by @media print) -->
<div class="receipt">
    <div class="receipt-store">
        <div class="receipt-store-name">Stationary Online Store</div>
        <div class="receipt-store-sub">Order Receipt</div>
    </div>

    <div class="receipt-row"><span>Order #</span><span><?= h($order->order_id) ?></span></div>
    <div class="receipt-row"><span>Order Date</span><span><?= h($order->order_date) ?></span></div>
    <div class="receipt-row"><span>Status</span><span><?= h($order->order_status) ?></span></div>

    <div class="receipt-divider"></div>

    <div class="receipt-row"><span>Customer</span><span><?= h($order->username) ?></span></div>
    <div class="receipt-row"><span>Email</span><span><?= h($order->email) ?></span></div>
    <div class="receipt-row"><span>Phone</span><span><?= h($order->phone) ?></span></div>
    <div class="receipt-row"><span>Address</span><span><?= h($order->address) ?></span></div>

    <div class="receipt-divider"></div>

    <table class="receipt-items">
        <tr><th>Item</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr>
        <?php foreach ($items as $it): ?>
            <tr>
                <td><?= h($it->product_name) ?></td>
                <td><?= h($it->quantity) ?></td>
                <td>RM <?= number_format($it->unit_price, 2) ?></td>
                <td>RM <?= number_format($it->unit_price * $it->quantity, 2) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <div class="receipt-divider"></div>

    <div class="receipt-total"><span>Total</span><span>RM <?= number_format($order->total_amount, 2) ?></span></div>

    <div class="receipt-footer">Thank you for shopping with us!</div>
</div>

<h2 class="no-print">Status Timeline</h2>
<ul class="timeline no-print">
    <?php foreach ($timeline as $t): ?>
        <li class="<?= h($t['state']) ?>">
            <b><?= h($t['label']) ?></b>
            <span><?= $t['time'] ? h($t['time']) : 'Not yet' ?></span>
        </li>
    <?php endforeach; ?>
</ul>

<h2 class="no-print">Update Status</h2>
<?php if ($allowed_next): ?>
    <form method="post" class="no-print">
        <?= html_select('order_status', array_combine($allowed_next, $allowed_next), 'Choose next status', 'data-no-autofocus') ?>
        <?= err('order_status') ?>
        <button type="submit">Update</button>
    </form>
<?php else: ?>
    <p class="no-print">This order is <?= h($order->order_status) ?> and cannot be changed further.</p>
<?php endif; ?>

<p class="no-print"><a href="list.php" class="btn-outline">Back to Order Listing</a></p>

<?php require '../_foot.php'; ?>
