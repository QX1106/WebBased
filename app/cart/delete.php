<?php
require '../_base.php';

auth('Member');
header('Content-Type: application/json');

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

if (!$cart) {
    echo json_encode([
        'success' => false,
        'message' => 'Cart not found.'
    ]);
    exit;
}

// Delete scoped to THIS member's cart_id, not product_id alone
$stm = $pdo->prepare("
    DELETE FROM cart_item
    WHERE cart_id = ? AND product_id = ?
");
$stm->execute([$cart->id, $product_id]);

if ($stm->rowCount() === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Item not found in cart.'
    ]);
    exit;
}

// Keep cart's updated_at fresh
$stm = $pdo->prepare("UPDATE cart SET updated_at = NOW() WHERE id = ?");
$stm->execute([$cart->id]);

// Recalculate subtotal after removal
$stm = $pdo->prepare("
    SELECT SUM(ci.quantity * p.price) AS subtotal
    FROM cart_item ci
    JOIN product p ON p.id = ci.product_id
    WHERE ci.cart_id = ?
");
$stm->execute([$cart->id]);
$subtotal = (float)($stm->fetch()->subtotal ?? 0);

// Re-check voucher validity against the new subtotal
$discount = 0;
$voucher_removed = false;

if (isset($_SESSION['voucher_id'])) {

    $stm = $pdo->prepare("SELECT * FROM voucher WHERE voucher_id = ?");
    $stm->execute([$_SESSION['voucher_id']]);
    $voucher = $stm->fetch();

    if ($voucher) {

        $today = date('Y-m-d');

        $valid =
            $voucher->status === 'Active' &&
            $today >= $voucher->valid_from &&
            $today <= $voucher->valid_until &&
            $subtotal >= $voucher->min_spend &&
            (
                $voucher->max_uses === null ||
                $voucher->used_count < $voucher->max_uses
            );

        // One voucher use per member
        if ($valid && $voucher->one_per_member) {

            $stm = $pdo->prepare("
                SELECT COUNT(*)
                FROM voucher_usage
                WHERE voucher_id = ?
                AND member_id = ?
            ");
            $stm->execute([$voucher->voucher_id, $member_id]);

            if ($stm->fetchColumn() > 0) {
                $valid = false;
            }
        }

        if ($valid) {

            if ($voucher->discount_type === 'Percentage') {
                $discount = $subtotal * ($voucher->discount_value / 100);

                if (
                    $voucher->max_discount !== null &&
                    $discount > $voucher->max_discount
                ) {
                    $discount = $voucher->max_discount;
                }
            }
            elseif ($voucher->discount_type === 'Fixed') {
                $discount = $voucher->discount_value;
            }

            $discount = min($discount, $subtotal);
        }
        else {
            unset($_SESSION['voucher_id']);
            $voucher_removed = true;
        }
    }
}

$total = $subtotal - $discount;

// Check if cart is now empty
$stm = $pdo->prepare("SELECT COUNT(*) FROM cart_item WHERE cart_id = ?");
$stm->execute([$cart->id]);
$is_empty = $stm->fetchColumn() == 0;

echo json_encode([
    'success'          => true,
    'subtotal'         => number_format($subtotal, 2, '.', ''),
    'discount'         => number_format($discount, 2, '.', ''),
    'total'            => number_format($total, 2, '.', ''),
    'voucher_removed'  => $voucher_removed,
    'is_empty'         => $is_empty
]);