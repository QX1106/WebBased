<?php require '../_base.php'; ?>
<?php auth('Member'); ?>
<?php

auto_complete_shipped_orders();

$member_id = $_user->member_id;

$status = get('status', '');

$statuses = [
    'Pending',
    'Processing',
    'Shipped',
    'Completed',
    'Cancelled'
];

$conditions = [
    'o.member_id = ?'
];

$params = [$member_id];

if ($status !== '' && in_array($status, $statuses)) {
    $conditions[] = 'o.order_status = ?';
    $params[] = $status;
}

$where_sql = 'WHERE ' . implode(' AND ', $conditions);

$query = "
    SELECT o.*
    FROM orders o
    $where_sql
    ORDER BY o.order_date DESC, o.order_id DESC
";

$page = get('page', 1);

$pager = new SimplePager(
    $pdo,
    $query,
    $params,
    10,
    $page
);

?>

<?php require '../_head.php'; ?>

<div class="order-history-page">

    <h1>Order History</h1>

    <div class="order-status-filter">

        <a href="?status="
           class="<?= $status === '' ? 'active' : '' ?>">
            All
        </a>

        <?php foreach ($statuses as $s): ?>
            <span>|</span>

            <a href="?status=<?= urlencode($s) ?>"
               class="<?= $status === $s ? 'active' : '' ?>">
                <?= h($s) ?>
            </a>
        <?php endforeach; ?>

    </div>

    <p>
        <?= $pager->item_count ?> order(s) found.
    </p>

    <div class="order-list">

        <?php foreach ($pager->result as $o): ?>

            <?php
            // Get up to 3 products for preview
            $stm = $pdo->prepare("
                SELECT
                    oi.quantity,
                    oi.unit_price,
                    p.name,
                    p.photo as photo

                FROM order_item oi

                JOIN product p
                    ON oi.product_id = p.id

                WHERE oi.order_id = ?

                ORDER BY oi.order_item_id
                LIMIT 3
            ");

            $stm->execute([$o->order_id]);
            $items = $stm->fetchAll();


            // Count total items/products in the order
            $stm = $pdo->prepare("
                SELECT COUNT(*)
                FROM order_item
                WHERE order_id = ?
            ");

            $stm->execute([$o->order_id]);
            $item_count = $stm->fetchColumn();
            ?>

            <div class="order-card">

                <div class="order-card-header">

                    <div>
                        <div class="order-number">
                            Order #<?= h($o->order_id) ?>
                        </div>

                        <div class="order-date">
                            <?= date(
                                'd M Y',
                                strtotime($o->order_date)
                            ) ?>
                        </div>
                    </div>

                    <div class="order-card-status">
                        <?= h($o->order_status) ?>
                    </div>

                </div>


                <div class="order-products">

                    <?php foreach ($items as $item): ?>

                        <div class="order-product-preview">

                            <?php if ($item->photo): ?>

                                <div class="order-product-image">
                                    <img
                                        src="../photos/<?= h($item->photo) ?>"
                                        alt="<?= h($item->name) ?>"
                                    >
                                </div>

                            <?php endif; ?>


                            <div class="order-product-name">
                                <?= h($item->name) ?>
                            </div>

                            <div class="order-product-quantity">
                                x<?= h($item->quantity) ?>
                            </div>

                        </div>

                    <?php endforeach; ?>


                    <?php if ($item_count > 3): ?>

                        <div class="more-items">
                            + <?= $item_count - 3 ?> more item(s)
                        </div>

                    <?php endif; ?>

                </div>


                <div class="order-card-footer">

                    <div class="order-total">
                        Total:
                        <strong>
                            RM <?= number_format($o->total_amount, 2) ?>
                        </strong>
                    </div>

                    <div class="order-actions">

                        <?php if ($o->order_status === 'Pending'): ?>

                            <a
                                href="checkout.php?order_id=<?= $o->order_id ?>"
                                class="btn-pay"
                            >
                                Proceed to Payment
                            </a>

                        <?php endif; ?>

                        <a
                            href="../cart/order-details.php?id=<?= $o->order_id ?>"
                            class="btn-details"
                        >
                            View Details
                        </a>

                    </div>

                </div>

            </div>

        <?php endforeach; ?>

    </div>


    <?php if ($pager->item_count == 0): ?>

        <div class="empty-orders">
            <p>No orders found.</p>
        </div>

    <?php endif; ?>


    <?= $pager->links(
        "&status=" . urlencode($status)
    ) ?>


    <div class="back-orders">
        <a href="list.php">
            ← Back to Active Orders
        </a>
    </div>

</div>

<?php require '../_foot.php'; ?>