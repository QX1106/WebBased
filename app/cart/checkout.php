<?php
require '../_base.php';

auth('Member');

$member_id = $_user->member_id;
$order_id = $_GET['order_id'] ?? null;

if (!$order_id) {
    header('Location: /cart/index.php');
    exit;
}

// Get the order, and confirm it belongs to this member
$stm = $pdo->prepare("
    SELECT o.*, p.pay_name
    FROM `orders` o
    JOIN payment p ON p.pay_id = o.payment_id
    WHERE o.order_id = ? AND o.member_id = ?
");
$stm->execute([$order_id, $member_id]);
$order = $stm->fetch();

if (!$order) {
    header('Location: /cart/index.php');
    exit;
}

// Get the order items
$stm = $pdo->prepare("
    SELECT oi.*, pr.name, pr.photo
    FROM order_item oi
    JOIN product pr ON pr.id = oi.product_id
    WHERE oi.order_id = ?
");
$stm->execute([$order_id]);
$items = $stm->fetchAll();

$_title = 'Checkout';

require '../_head.php';
?>

<div class="checkout-simple">
    <h1>Checkout</h1>
    <?php if ($order): ?>
        <div class="checkout-simple-price">
            <small>RM</small><?= number_format($order->total_amount, 2) ?>
        </div>
        <div class="checkout-simple-details">
            <div class="checkout-simple-row">
                <span>Order Number</span>
                <span><?= h($order_id) ?></span>
            </div>
            <div class="checkout-simple-row">
                <span>Payment Method</span>
                <span><?= h($order->pay_name) ?></span>
            </div>
        </div>
        <form method="post" action="pay.php">
            <input type="hidden" name="order_id" value="<?= $order->order_id ?>">
            <button type="submit" class="checkout-pay-button">Confirm & Pay</button>
        </form>
    <?php else: ?>
        <div class="checkout-simple-empty">
            Order not found.
        </div>
    <?php endif; ?>
</div>

<?php require '../_foot.php'; ?>