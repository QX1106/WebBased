<?php require '../_base.php'; ?>
<?php auth('Member'); ?>
<?php

auto_complete_shipped_orders();

$member_id = $_user->member_id;

$status = get('status', '');

$active_statuses = ['Pending', 'Processing', 'Shipped'];

$conditions = [
    'member_id = ?',
    "order_status IN ('Pending', 'Processing', 'Shipped')"
];

$params = [$member_id];

if ($status !== '' && in_array($status, $active_statuses)) {
    $conditions[] = 'order_status = ?';
    $params[] = $status;
}

$where_sql = 'WHERE ' . implode(' AND ', $conditions);

$query = "
    SELECT *
    FROM orders
    $where_sql
    ORDER BY order_date DESC, order_id DESC
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

<h1>My Orders</h1>

<div class="order-status-filter">
    <a href="?status="
       class="<?= $status === '' ? 'active' : '' ?>">
        All Active
    </a>

    <?php foreach ($active_statuses as $s): ?>
        <span>|</span>

        <a href="?status=<?= urlencode($s) ?>"
           class="<?= $status === $s ? 'active' : '' ?>">
            <?= h($s) ?>
        </a>
    <?php endforeach; ?>
</div>

<p><?= $pager->item_count ?> active order(s).</p>

<div class="order-list">

    <?php foreach ($pager->result as $o): ?>

        <?php
        // Get a few products for preview
        $stm = $pdo->prepare("
            SELECT 
                oi.quantity,
                oi.unit_price,
                p.name
            FROM order_item oi
            JOIN product p ON oi.product_id = p.id
            WHERE oi.order_id = ?
            ORDER BY oi.order_item_id
            LIMIT 3
        ");

        $stm->execute([$o->order_id]);
        $items = $stm->fetchAll();
        ?>

        <div class="order-card">

            <div class="order-card-header">
                <div>
                    <div class="order-number">
                        Order #<?= h($o->order_id) ?>
                    </div>

                    <div class="order-date">
                        <?= date('d M Y', strtotime($o->order_date)) ?>
                    </div>
                </div>

                <span class="order-status status-<?= strtolower($o->order_status) ?>">
                    <?= h($o->order_status) ?>
                </span>
            </div>

            <div class="order-products">

                <?php foreach ($items as $item): ?>
                    <div class="order-product-row">

                        <span>
                            <?= h($item->name) ?>
                        </span>

                        <span>
                            x<?= h($item->quantity) ?>
                        </span>

                    </div>
                <?php endforeach; ?>

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

                        <form method="post"
                              action="pay.php"
                              style="display:inline;">

                            <input
                                type="hidden"
                                name="order_id"
                                value="<?= $o->order_id ?>"
                            >

                            <button type="submit" class="btn-pay">
                                Pay Now
                            </button>

                        </form>

                    <?php endif; ?>

                    <a href="order-details.php?id=<?= $o->order_id ?>"
                    class="btn-details">
                        View Details
                    </a>

                </div>

            </div>

        </div>

    <?php endforeach; ?>

</div>

<?php if ($pager->item_count == 0): ?>

    <div class="empty-cart">
        <h2>No Orders Found</h2>
        <p></p>
        <a href="../product/list.php" class="btn-accent">Continue Shopping</a>
    </div>

<?php endif; ?>

<?= $pager->links("&status=" . urlencode($status)) ?>

<div class="order-history-link">
    <a href="../order/history.php">
        View Order History →
    </a>
</div>

<?php require '../_foot.php'; ?>