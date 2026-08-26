<?php require '../_base.php'; ?>
<?php auth('Member'); ?>
<?php

$product_id = (int) post('product_id');
$qty = (int) post('qty');

$back_url = "/product/view.php?id=$product_id";

// Validate product + stock
$stm = $pdo->prepare("SELECT * FROM product WHERE id = ?");
$stm->execute([$product_id]);
$product = $stm->fetch();

if (!$product || $qty < 1) {
    temp('info', 'Invalid product or quantity.');
    redirect($back_url);
}

if ($qty > $product->stock_qty) {
    temp('info', 'Not enough stock available.');
    redirect($back_url);
}

// Find or create this member's cart
$stm = $pdo->prepare("SELECT id FROM cart WHERE member_id = ? ORDER BY created_at DESC LIMIT 1");
$stm->execute([$_user->member_id]);
$cart = $stm->fetch();

if ($cart) {
    $cart_id = $cart->id;
} else {
    $stm = $pdo->prepare("INSERT INTO cart (member_id, created_at, updated_at) VALUES (?, NOW(), NOW())");
    $stm->execute([$_user->member_id]);
    $cart_id = $pdo->lastInsertId();
}

// Check if this product is already in the cart
$stm = $pdo->prepare("SELECT * FROM cart_item WHERE cart_id = ? AND product_id = ?");
$stm->execute([$cart_id, $product_id]);
$item = $stm->fetch();

if ($item) {
    $new_qty = $item->quantity + $qty;
    if ($new_qty > $product->stock_qty) {
        $new_qty = $product->stock_qty;
    }
    $stm = $pdo->prepare("UPDATE cart_item SET quantity = ? WHERE id = ?");
    $stm->execute([$new_qty, $item->id]);
} else {
    $stm = $pdo->prepare("INSERT INTO cart_item (cart_id, product_id, quantity) VALUES (?, ?, ?)");
    $stm->execute([$cart_id, $product_id, $qty]);
}

// Keep cart's updated_at fresh
$stm = $pdo->prepare("UPDATE cart SET updated_at = NOW() WHERE id = ?");
$stm->execute([$cart_id]);

temp('info', 'Added to cart.');
redirect($back_url);