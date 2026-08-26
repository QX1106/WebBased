<?php
require '../_base.php';

auth('Member');

$member_id = $_user->member_id;
$product_id = (int) post('product_id');

// Find this member's cart
$stm = $pdo->prepare("
    SELECT id
    FROM cart
    WHERE member_id = ?
    ORDER BY created_at DESC
    LIMIT 1
");
$stm->execute([$member_id]);
$cart = $stm->fetch();

if ($cart) {
    // Delete scoped to THIS member's cart_id, not product_id alone
    $stm = $pdo->prepare("
        DELETE FROM cart_item
        WHERE cart_id = ? AND product_id = ?
    ");
    $stm->execute([$cart->id, $product_id]);

    // Keep cart's updated_at fresh
    $stm = $pdo->prepare("UPDATE cart SET updated_at = NOW() WHERE id = ?");
    $stm->execute([$cart->id]);
}

temp('info', 'Item removed from cart.');
redirect('/cart/index.php');