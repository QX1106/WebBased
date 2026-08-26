<?php require '../_base.php'; ?>
<?php auth('Member'); ?>
<?php

auto_complete_shipped_orders();

$member_id = $_user->member_id;
$order_id = get('id');

if (!$order_id || !ctype_digit((string)$order_id)) {
    redirect('order.php');
}

// Get order details
$stm = $pdo->prepare("
    SELECT o.*, p.pay_name
    FROM orders o
    LEFT JOIN payment p ON o.payment_id = p.pay_id
    WHERE o.order_id = ?
    AND o.member_id = ?
");

$stm->execute([$order_id, $member_id]);
$order = $stm->fetch();

if (!$order) {
    redirect('order.php');
}

// Get items in this order
$stm = $pdo->prepare("
    SELECT
        oi.order_item_id,
        oi.product_id,
        oi.quantity,
        oi.unit_price,
        p.name,
        p.photo as photo

    FROM order_item oi
    JOIN product p
        ON oi.product_id = p.id

    WHERE oi.order_id = ?

    ORDER BY oi.order_item_id
");

$stm->execute([$order_id]);
$items = $stm->fetchAll();

?>
<?php require '../_head.php'; ?>

<div class="order-detail-page">

    <div class="order-detail-heading">
        <div>
            <h1>Order #<?= h($order->order_id) ?></h1>

            <div class="order-detail-date">
                <?= date('d M Y, h:i A', strtotime($order->order_date)) ?>
            </div>
        </div>

        <div class="order-detail-status">
            <?= h($order->order_status) ?>
        </div>
    </div>


    <div class="order-section">

        <h2>Order Items</h2>

        <?php foreach ($items as $item): ?>

            <div class="order-detail-item">

                <div class="order-item-image">

                    <?php if ($item->photo): ?>
                        <img
                            src="../photos/<?= h($item->photo) ?>"
                            alt="<?= h($item->name) ?>"
                        >
                    <?php endif; ?>

                </div>

                <div class="order-item-info">

                    <div class="order-item-name">
                        <?= h($item->name) ?>
                    </div>

                    <div class="order-item-price">
                        RM <?= number_format($item->unit_price, 2) ?>
                    </div>

                </div>

                <div class="order-item-quantity">
                    x<?= h($item->quantity) ?>
                </div>

                <div class="order-item-subtotal">
                    RM <?= number_format(
                        $item->unit_price * $item->quantity,
                        2
                    ) ?>
                </div>

            </div>

        <?php endforeach; ?>

    </div>


    <div class="order-detail-bottom">

        <div class="order-section order-info">

            <h2>Delivery Information</h2>

            <div class="order-info-row">
                <span>Shipping Address</span>
                <span><?= nl2br(h($order->shipping_address)) ?></span>
            </div>

            <div class="order-info-row">
                <span>Payment Method</span>
                <span><?= h($order->pay_name ?? '-') ?></span>
            </div>

        </div>


        <div class="order-section order-summary">

            <h2>Order Summary</h2>

            <div class="summary-row">
                <span>Subtotal</span>
                <span>
                    RM <?= number_format($order->total_amount, 2) ?>
                </span>
            </div>

            <div class="summary-row summary-total">
                <span>Total</span>
                <span>
                    RM <?= number_format($order->total_amount, 2) ?>
                </span>
            </div>

        </div>

    </div>


    <div class="order-detail-actions">

    <a href="order.php">
        ← Back to Orders
    </a>

    <?php if ($order->order_status === 'Pending'): ?>

        <a href="checkout.php?order_id=<?= $order->order_id ?>"
           class="btn-checkout">
            Proceed to Payment
        </a>

    <?php endif; ?>

</div>

</div>

<?php require '../_foot.php'; ?>