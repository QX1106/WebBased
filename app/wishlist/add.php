<?php
require '../_base.php';

auth('Member');

$member_id = $_user->member_id;
$product_id = (int) post('product_id');

if (!$product_id) {
    redirect('../index.php');
}

// Make sure product exists
$stm = $pdo->prepare("
    SELECT id
    FROM product
    WHERE id = ?
");

$stm->execute([$product_id]);

if (!$stm->fetch()) {
    temp('info', 'Product not found.');
    redirect('../index.php');
}

// Add only if it is not already there
$stm = $pdo->prepare("
    INSERT IGNORE INTO wishlist
        (member_id, product_id, created_at)
    VALUES
        (?, ?, NOW())
");

$stm->execute([
    $member_id,
    $product_id
]);

temp('info', 'Product added to wishlist.');

redirect("../product/view.php?id=$product_id");