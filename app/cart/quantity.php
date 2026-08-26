<?php
require '../_base.php';

auth('Member');
header('Content-Type: application/json');

$member_id = $_user->member_id;
$product_id = (int) post('product_id');
$action = post('action');

$stm = $pdo->prepare("SELECT id FROM cart WHERE member_id = ? ORDER BY created_at DESC LIMIT 1");
$stm->execute([$member_id]);
$cart = $stm->fetch();

if (!$cart) {
    echo json_encode(['success' => false, 'message' => 'Cart not found.']);
    exit;
}

$stm = $pdo->prepare("
    SELECT ci.*, p.stock_qty, p.price
    FROM cart_item ci
    JOIN product p ON p.id = ci.product_id
    WHERE ci.cart_id = ? AND ci.product_id = ?
");
$stm->execute([$cart->id, $product_id]);
$item = $stm->fetch();

if (!$item) {
    echo json_encode(['success' => false, 'message' => 'Item not found in cart.']);
    exit;
}

$new_qty = $item->quantity;

if ($action === 'increase') {
    $new_qty = min($item->quantity + 1, $item->stock_qty);
} elseif ($action === 'decrease') {
    $new_qty = max($item->quantity - 1, 1);
}

$stm = $pdo->prepare("UPDATE cart_item SET quantity = ? WHERE id = ?");
$stm->execute([$new_qty, $item->id]);

$stm = $pdo->prepare("UPDATE cart SET updated_at = NOW() WHERE id = ?");
$stm->execute([$cart->id]);

// Recalculate cart total
$stm = $pdo->prepare("
    SELECT SUM(ci.quantity * p.price) AS total
    FROM cart_item ci
    JOIN product p ON p.id = ci.product_id
    WHERE ci.cart_id = ?
");
$stm->execute([$cart->id]);
$total = $stm->fetch()->total ?? 0;

echo json_encode([
    'success'      => true,
    'quantity'     => $new_qty,
    'subtotal'     => number_format($new_qty * $item->price, 2),
    'total'        => number_format($total, 2),
    'maxed_out'    => $new_qty >= $item->stock_qty,
]);