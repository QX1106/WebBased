<?php
require '../_base.php';

auth('Member');

header('Content-Type: application/json');

$product_id = (int) post('product_id');
$quantity = (int) post('quantity');


if (!$product_id || $quantity < 1) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid product or quantity.'
    ]);
    exit;
}

// Get product and validate stock
$stm = $pdo->prepare("
    SELECT id, stock_qty
    FROM product
    WHERE id = ?
");

$stm->execute([$product_id]);
$product = $stm->fetch();

if (!$product) {
    echo json_encode([
        'success' => false,
        'message' => 'Product not found.'
    ]);
    exit;
}

if ($quantity > $product->stock_qty) {
    echo json_encode([
        'success' => false,
        'message' => 'Not enough stock available.'
    ]);
    exit;
}

// Replace any previous Buy Now session
$_SESSION['buy_now'] = [
    'product_id' => $product_id,
    'quantity'   => $quantity
];

// Unset previous Buy Now voucher
unset($_SESSION['buy_now_voucher_id']);

echo json_encode([
    'success' => true
]);