<?php
require '../_base.php';

auth('Member');
header('Content-Type: application/json');

$member_id = $_user->member_id;

unset($_SESSION['voucher_id']);

// Get this member's cart
$stm = $pdo->prepare("
    SELECT id
    FROM cart
    WHERE member_id = ?
    ORDER BY created_at DESC
    LIMIT 1
");
$stm->execute([$member_id]);
$cart = $stm->fetch();

$subtotal = 0;

if ($cart) {
    $stm = $pdo->prepare("
        SELECT SUM(ci.quantity * p.price) AS subtotal
        FROM cart_item ci
        JOIN product p ON p.id = ci.product_id
        WHERE ci.cart_id = ?
    ");
    $stm->execute([$cart->id]);
    $subtotal = (float)($stm->fetch()->subtotal ?? 0);
}

echo json_encode([
    'success'  => true,
    'subtotal' => number_format($subtotal, 2, '.', ''),
    'total'    => number_format($subtotal, 2, '.', '') // discount is now 0
]);
