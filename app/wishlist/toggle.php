<?php
require '../_base.php';

auth('Member');
header('Content-Type: application/json');

$member_id = $_user->member_id;
$product_id = (int) post('product_id');

if (!$product_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid product.'
    ]);
    exit;
}

// Make sure product exists
$stm = $pdo->prepare("
    SELECT id
    FROM product
    WHERE id = ?
");

$stm->execute([$product_id]);

if (!$stm->fetch()) {
    echo json_encode([
        'success' => false,
        'message' => 'Product not found.'
    ]);
    exit;
}

// Check if already wishlisted
$stm = $pdo->prepare("
    SELECT wishlist_id
    FROM wishlist
    WHERE member_id = ?
      AND product_id = ?
");

$stm->execute([
    $member_id,
    $product_id
]);

$wishlist = $stm->fetch();

if ($wishlist) {

    // Remove from wishlist
    $stm = $pdo->prepare("
        DELETE FROM wishlist
        WHERE member_id = ?
          AND product_id = ?
    ");

    $stm->execute([
        $member_id,
        $product_id
    ]);

    echo json_encode([
        'success' => true,
        'wishlisted' => false
    ]);

} else {

    // Add to wishlist
    $stm = $pdo->prepare("
        INSERT INTO wishlist
            (member_id, product_id, created_at)
        VALUES
            (?, ?, NOW())
    ");

    $stm->execute([
        $member_id,
        $product_id
    ]);

    echo json_encode([
        'success' => true,
        'wishlisted' => true
    ]);
}