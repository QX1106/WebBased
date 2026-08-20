<?php require '../_base.php'; ?>
<?php auth('Admin'); ?>
<?php

auto_complete_shipped_orders();

$_err = [];
$id = get('id');

// Order status 
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

$cancel_reasons = [
    'Out of Stock' => 'Out of Stock',
    'Customer Requested' => 'Customer Requested',
    'Payment Issue' => 'Payment Issue',
    'Suspected Fraud' => 'Suspected Fraud',
    'Other' => 'Other',
];

// status update
if (is_post()) {
    $new_status = post('order_status');
    $cancel_reason = post('cancel_reason');
    $cancel_other = post('cancel_other');

    if ($new_status == '') {
        $_err['order_status'] = 'Required';
    }
    else if (!in_array($new_status, $allowed_next)) {
        $_err['order_status'] = "Cannot move from {$order->order_status} to $new_status";
    }

    // reason cancel
    $note = null;
    if (!$_err && $new_status == 'Cancelled') {
        if ($cancel_reason == '' || !isset($cancel_reasons[$cancel_reason])) {
            $_err['cancel_reason'] = 'Required';
        }
        elseif ($cancel_reason == 'Other' && trim($cancel_other) == '') {
            $_err['cancel_other'] = 'Please specify a reason';
        }
        if (!$_err) {
            $note = $cancel_reason == 'Other' ? 'Other — ' . trim($cancel_other) : $cancel_reason;
        }
    }

    if (!$_err) {
        $stm = $pdo->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
        $stm->execute([$new_status, $id]);

        $stm = $pdo->prepare("INSERT INTO order_status_log (order_id, status, note) VALUES (?, ?, ?)");
        $stm->execute([$id, $new_status, $note]);

        // Sync in-memory $order so the data built below (used by the AJAX response) is current
        $order->order_status = $new_status;
        $allowed_next = $transitions[$order->order_status] ?? [];

        if (!is_ajax()) {
            temp('info', 'Order status updated.');
            redirect("detail.php?id=$id");
        }
        // AJAX: fall through so $timeline/$allowed_next get rebuilt below
        // against the fresh $order->order_status, then respond with JSON
        // instead of a normal page render (see bottom of file).
    } elseif (is_ajax()) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'errors' => $_err]);
        exit;
    }
}

// Additional Module (AJAX): renders the same "next status" form / "no
// further changes" message used both on a normal page load and inside the
// JSON response after an AJAX update, so the two never drift apart.
function render_update_status_html($allowed_next, $cancel_reasons, $current_status) {
    ob_start();
    if ($allowed_next):
?>
    <form method="post" class="no-print" id="order-status-form">
        <?= html_select('order_status', array_combine($allowed_next, $allowed_next), 'Choose next status', 'data-no-autofocus') ?>
        <?= err('order_status') ?>

        <div id="cancel-reason-wrap" style="display:none">
            <label for="cancel_reason">Cancellation Reason</label>
            <?= html_select('cancel_reason', $cancel_reasons, 'Choose a reason', 'data-no-autofocus') ?>
            <?= err('cancel_reason') ?>

            <div id="cancel-other-wrap" style="display:none">
                <label for="cancel_other">Please specify</label>
                <?= html_text('cancel_other', "maxlength='255' data-no-autofocus") ?>
                <?= err('cancel_other') ?>
            </div>
        </div>

        <button type="submit">Update</button>
    </form>
<?php else: ?>
    <p class="no-print">This order is <?= h($current_status) ?> and cannot be changed further.</p>
<?php endif;
    return ob_get_clean();
}

// Additional Module (AJAX): same idea for the Status Timeline list items.
function render_timeline_html($timeline) {
    ob_start();
    foreach ($timeline as $t):
?>
        <li class="<?= h($t['state']) ?>">
            <b><?= h($t['label']) ?></b>
            <span><?= $t['time'] ? h($t['time']) : 'Not yet' ?></span>
            <?php if ($t['note']): ?><div class="timeline-note">Reason: <?= h($t['note']) ?></div><?php endif; ?>
        </li>
<?php
    endforeach;
    return ob_get_clean();
}

$stm = $pdo->prepare("SELECT oi.*, p.name AS product_name
                       FROM order_item oi
                       JOIN product p ON oi.product_id = p.id
                       WHERE oi.order_id = ?");
$stm->execute([$id]);
$items = $stm->fetchAll();

// status timeline
$stm = $pdo->prepare("SELECT * FROM order_status_log WHERE order_id = ? ORDER BY changed_at ASC");
$stm->execute([$id]);
$log = $stm->fetchAll();

$sequence = ['Pending' => 'Order Placed', 'Processing' => 'Processing', 'Shipped' => 'Shipped', 'Completed' => 'Completed'];

$reached = ['Pending' => $order->order_date];
$cancelled_at = null;
$cancelled_note = null;
foreach ($log as $l) {
    if ($l->status == 'Cancelled') {
        $cancelled_at = $l->changed_at;
        $cancelled_note = $l->note;
    } else {
        $reached[$l->status] = $l->changed_at;
    }
}

$timeline = [];
foreach ($sequence as $key => $label) {
    if (isset($reached[$key])) {
        $state = ($key == $order->order_status) ? 'current' : 'done';
        $timeline[] = ['label' => $label, 'time' => $reached[$key], 'state' => $state, 'note' => null];
    } elseif ($cancelled_at === null) {
        $timeline[] = ['label' => $label, 'time' => null, 'state' => 'future', 'note' => null];
    } else {
        break;
    }
}
if ($cancelled_at !== null) {
    $timeline[] = ['label' => 'Cancelled', 'time' => $cancelled_at, 'state' => 'cancelled', 'note' => $cancelled_note];
}

// Additional Module (AJAX): the update succeeded and this was an AJAX
// request (see the fall-through above) — respond with JSON instead of
// rendering the full page, using the exact same render_*_html() helpers
// the normal page render below uses.
if (is_post() && !$_err && is_ajax()) {
    header('Content-Type: application/json');
    echo json_encode([
        'ok' => true,
        'status' => $order->order_status,
        'timeline_html' => render_timeline_html($timeline),
        'update_html' => render_update_status_html($allowed_next, $cancel_reasons, $order->order_status),
    ]);
    exit;
}

$order_status = $_err ? ($new_status ?? '') : '';
$cancel_reason = $_err ? ($cancel_reason ?? '') : '';
$cancel_other = $_err ? ($cancel_other ?? '') : '';

?>
<?php require '../_head.php'; ?>

<h1 class="no-print">Order #<?= h($order->order_id) ?></h1>

<p class="no-print receipt-actions">
    <a href="receipt-pdf.php?id=<?= h($order->order_id) ?>" class="btn-accent">Download Receipt (PDF)</a>
    <button type="button" class="btn-outline" id="send-receipt-email" data-order-id="<?= h($order->order_id) ?>">Send Email to Customer</button>
    <span id="send-receipt-status"></span>
</p>

<table class="detail no-print">
    <tr><th>Order Date</th><td><?= h($order->order_date) ?></td></tr>
    <tr><th>Member</th><td><?= h($order->username) ?> (<?= h($order->email) ?>) — <?= h($order->phone) ?></td></tr>
    <tr><th>Address</th><td><?= h($order->address) ?></td></tr>
    <tr><th>Total</th><td>RM <?= number_format($order->total_amount, 2) ?></td></tr>
    <tr><th>Status</th><td id="order-status-cell"><?= h($order->order_status) ?></td></tr>
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

<h2 class="no-print">Status Timeline</h2>
<ul class="timeline no-print" id="order-timeline"><?= render_timeline_html($timeline) ?></ul>

<h2 class="no-print">Update Status</h2>
<div id="order-update-section" data-order-id="<?= h($order->order_id) ?>">
    <?= render_update_status_html($allowed_next, $cancel_reasons, $order->order_status) ?>
</div>

<p class="no-print"><a href="list.php" class="btn-outline">Back to Order Listing</a></p>

<?php require '../_foot.php'; ?>
