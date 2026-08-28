<?php require '../_base.php'; ?>
<?php auth('Member'); ?>
<?php

auto_complete_shipped_orders();

$member_id = $_user->member_id;
$order_id = get('id');

// ----------------------------------------------------------------------
// Validate Order ID
// ----------------------------------------------------------------------

if (!$order_id || !ctype_digit((string)$order_id)) {
    redirect('order.php');
}

// ----------------------------------------------------------------------
// Get Order
// ----------------------------------------------------------------------

$stm = $pdo->prepare("
    SELECT
        o.*,
        p.pay_name
    FROM orders o

    LEFT JOIN payment p
        ON o.payment_id = p.pay_id

    WHERE o.order_id = ?
    AND o.member_id = ?
");

$stm->execute([
    $order_id,
    $member_id
]);

$order = $stm->fetch();

// Make sure member can only view their own order
if (!$order) {
    redirect('order.php');
}

// ----------------------------------------------------------------------
// Get Order Items
// ----------------------------------------------------------------------

$stm = $pdo->prepare("
    SELECT
        oi.order_item_id,
        oi.product_id,
        oi.quantity,
        oi.unit_price,
        p.name,
        p.photo
    FROM order_item oi
    JOIN product p
        ON oi.product_id = p.id
    WHERE oi.order_id = ?
    ORDER BY oi.order_item_id
");

$stm->execute([$order_id]);
$items = $stm->fetchAll();

// ----------------------------------------------------------------------
// Calculate Original Subtotal
// ----------------------------------------------------------------------
// Use order_item.unit_price instead of current product.price because
// order_item stores the price at the time the order was created.

$subtotal = 0;

foreach ($items as $item) {
    $subtotal += $item->unit_price * $item->quantity;
}

// ----------------------------------------------------------------------
// Calculate Discount
// ----------------------------------------------------------------------

$discount = max(
    0,
    $subtotal - $order->total_amount
);

// ----------------------------------------------------------------------
// Check Cancellation Request
// ----------------------------------------------------------------------

$stm = $pdo->prepare("
    SELECT *
    FROM cancel_request
    WHERE order_id = ?
    AND member_id = ?
    ORDER BY requested_at DESC
    LIMIT 1
");

$stm->execute([
    $order_id,
    $member_id
]);

$cancel_request = $stm->fetch();
?>

<?php require '../_head.php'; ?>
<div class="order-detail-page">
    <div class="order-detail-heading">
        <div>
            <h1>Order #<?= h($order->order_id) ?></h1>
            <div class="order-detail-date">
                <?= date(
                    'd M Y, h:i A',
                    strtotime($order->order_date)
                ) ?>
            </div>
        </div>

        <div class="order-detail-status">
            <?= h($order->order_status) ?>
        </div>
    </div>

    <!-- ============================================================= -->
    <!-- Order Items                                                   -->
    <!-- ============================================================= -->

    <div class="order-section">
        <h2>Order Items</h2>
        <?php foreach ($items as $item): ?>
            <div class="order-detail-item">
                <!-- Product Photo -->
                <div class="order-item-image">
                    <?php if ($item->photo): ?>
                        <img
                            src="../photos/<?= h($item->photo) ?>"
                            alt="<?= h($item->name) ?>"
                        >
                    <?php else: ?>
                        <div class="no-photo">No Photo</div>
                    <?php endif; ?>
                </div>

                <!-- Product Information -->
                <div class="order-item-info">
                    <div class="order-item-name">
                        <?= h($item->name) ?>
                    </div>
                    <div class="order-item-price">
                        RM <?= number_format(
                            $item->unit_price,
                            2
                        ) ?>
                    </div>
                </div>

                <!-- Quantity -->
                <div class="order-item-quantity">x<?= h($item->quantity) ?></div>

                <!-- Item Subtotal -->
                <div class="order-item-subtotal">
                    RM <?= number_format(
                        $item->unit_price
                        * $item->quantity,
                        2
                    ) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- ============================================================= -->
    <!-- Delivery + Summary                                            -->
    <!-- ============================================================= -->
    <div class="order-detail-bottom">
        <!-- Delivery Information -->
        <div class="order-section order-info">
            <h2>Delivery Information</h2>
            <div class="order-info-row">
                <span>Shipping Address</span>
                <span>
                    <?= nl2br(
                        h($order->shipping_address)
                    ) ?>
                </span>
            </div>

            <?php if ($order->tracking_number): ?>
                <div class="order-info-row">
                    <span>Tracking Number</span>
                    <span>
                        <?= h($order->tracking_number) ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Order Summary -->
        <div class="order-section order-summary">
            <h2>Order Summary</h2>

            <!-- Subtotal -->
            <div class="order-info-row">
                <span>Subtotal</span>
                <span>
                    RM <?= number_format(
                        $subtotal,
                        2
                    ) ?>
                </span>
            </div>

            <!-- Voucher Discount -->
            <?php if ($discount > 0): ?>
                <div class="order-info-row">
                    <span>Voucher Discount</span>
                    <span>
                        - RM <?= number_format(
                            $discount,
                            2
                        ) ?>
                    </span>
                </div>
            <?php endif; ?>

            <!-- Total -->
            <div class="order-info-row summary-total">
                <span>Total</span>
                <span>
                    RM <?= number_format(
                        $order->total_amount,
                        2
                    ) ?>
                </span>
            </div>

            <div class="summary-row">
                <span>Payment Method</span>
                <span>
                    <?= h(
                        $order->pay_name ?? '-'
                    ) ?>
                </span>
            </div>
        </div>
    </div>

    <!-- ============================================================= -->
    <!-- Cancellation Request Information                              -->
    <!-- ============================================================= -->
    <?php if ($cancel_request): ?>
        <div class="order-section cancellation-status">
            <h2>Cancellation Request</h2>

            <div class="order-info-row">
                <span>Status</span>
                <span><?= h($cancel_request->status) ?></span>
            </div>

            <div class="order-info-row">
                <span>Requested At</span>
                <span>
                    <?= date(
                        'd M Y, h:i A',
                        strtotime(
                            $cancel_request->requested_at
                        )
                    ) ?>
                </span>
            </div>

            <div class="order-info-row">
                <span>Reason</span>
                <span>
                    <?= nl2br(
                        h($cancel_request->reason)
                    ) ?>
                </span>
            </div>

            <?php if (!empty($cancel_request->admin_note)): ?>
                <div class="order-info-row">
                    <span>Admin Note</span>
                    <span>
                        <?= nl2br(
                            h(
                                $cancel_request
                                    ->admin_note
                            )
                        ) ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- ============================================================= -->
    <!-- Actions                                                       -->
    <!-- ============================================================= -->
    <div class="order-detail-actions">
        <a href="order.php">← Back to Orders</a>

        <?php if ($order->order_status === 'Pending'): ?>
            <?php if (
                $cancel_request &&
                $cancel_request->status === 'Pending'
            ): ?>

                <!-- Cancellation already submitted -->
                <div class="pending-order-actions">
                    <button type="button" class="btn-cancel-order" disabled>
                        Cancellation Request Pending
                    </button>
                </div>

            <?php else: ?>

                <!-- Normal Pending Order Actions -->
                <div class="pending-order-actions">
                    <a
                        href="checkout.php?order_id=<?= 
                            $order->order_id 
                        ?>"
                        class="btn-checkout"
                    >
                        Proceed to Payment
                    </a>

                    <a
                        href="cancel-request.php?order_id=<?= 
                            $order->order_id 
                        ?>"
                        class="btn-cancel-order"
                    >
                        Cancel Order
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require '../_foot.php'; ?>