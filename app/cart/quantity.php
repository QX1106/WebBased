<?php
require '../_base.php';

auth('Member');

header('Content-Type: application/json');

$member_id = $_user->member_id;
$product_id = (int) post('product_id');
$action = post('action');
$mode = post('mode', 'cart');

$buy_now_mode = $mode === 'buy_now';

if ($buy_now_mode) {
    // Make sure Buy Now session exists
    if (!isset($_SESSION['buy_now'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Buy Now session not found.'
        ]);
        exit;
    }

    $session_product_id =
        (int) $_SESSION['buy_now']['product_id'];

    if ($product_id !== $session_product_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid product.'
        ]);
        exit;
    }

    $current_qty =
        (int) $_SESSION['buy_now']['quantity'];

    // Get current product price and stock
    $stm = $pdo->prepare("
        SELECT price, stock_qty
        FROM product
        WHERE id = ?
    ");

    $stm->execute([$product_id]);
    $product = $stm->fetch();

    if (!$product) {
        unset($_SESSION['buy_now']);
        unset($_SESSION['buy_now_voucher_id']);

        echo json_encode([
            'success' => false,
            'message' => 'Product no longer exists.'
        ]);
        exit;
    }

    if ($product->stock_qty <= 0) {
        unset($_SESSION['buy_now']);
        unset($_SESSION['buy_now_voucher_id']);

        echo json_encode([
            'success' => false,
            'message' => 'This product is out of stock.'
        ]);
        exit;
    }

    $new_qty = $current_qty;

    if ($action === 'increase') {
        $new_qty = min(
            $current_qty + 1,
            $product->stock_qty
        );
    }
    elseif ($action === 'decrease') {
        $new_qty = max(
            $current_qty - 1,
            1
        );
    }

    // Save quantity only in Buy Now session
    $_SESSION['buy_now']['quantity'] =
        $new_qty;

    $subtotal =
        $new_qty *
        $product->price;

    $voucher = null;
    $discount = 0;

    $voucher_valid = false;
    $voucher_removed = false;

    if (
        isset(
            $_SESSION['buy_now_voucher_id']
        )
    ) {
        $stm = $pdo->prepare("
            SELECT *
            FROM voucher
            WHERE voucher_id = ?
        ");

        $stm->execute([
            $_SESSION[
                'buy_now_voucher_id'
            ]
        ]);

        $voucher = $stm->fetch();

        if ($voucher) {
            $today = date('Y-m-d');

            $voucher_valid =
                $voucher->status === 'Active' &&
                $today >= $voucher->valid_from &&
                $today <= $voucher->valid_until &&
                $subtotal >= $voucher->min_spend &&
                (
                    $voucher->max_uses === null ||
                    $voucher->used_count <
                        $voucher->max_uses
                );

            // One voucher per member
            if (
                $voucher_valid &&
                $voucher->one_per_member
            ) {

                $stm = $pdo->prepare("
                    SELECT COUNT(*)
                    FROM voucher_usage
                    WHERE voucher_id = ?
                    AND member_id = ?
                ");

                $stm->execute([
                    $voucher->voucher_id,
                    $member_id
                ]);

                if (
                    $stm->fetchColumn() > 0
                ) {
                    $voucher_valid = false;
                }
            }

            // Calculate discount
            if ($voucher_valid) {
                if ($voucher->discount_type === 'Percentage') {
                    $discount = $subtotal * ($voucher->discount_value / 10);

                    if ($voucher->max_discount!== null && $discount > $voucher->max_discount) {
                        $discount = $voucher->max_discount;
                    }
                }
                elseif ($voucher->discount_type === 'Fixed'
                ) {
                    $discount = $voucher->discount_value;
                }

                // Prevent negative total
                $discount = min($discount, $subtotal
                );
            }
            else {
                unset($_SESSION['buy_now_voucher_id']);

                $voucher = null;
                $discount = 0;

                $voucher_removed = true;
            }
        }
        else {
            unset($_SESSION['buy_now_voucher_id']);

            $voucher_removed = true;
        }
    }

    $total = $subtotal - $discount;

    echo json_encode([
        'success' => true,
        'quantity' => $new_qty,
        'item_subtotal' =>
            number_format(
                $subtotal,
                2,
                '.',
                ''
            ),

        'subtotal' =>
            number_format(
                $subtotal,
                2,
                '.',
                ''
            ),

        'discount' =>
            number_format(
                $discount,
                2,
                '.',
                ''
            ),

        'total' =>
            number_format(
                $total,
                2,
                '.',
                ''
            ),

        'voucher_valid' =>
            $voucher_valid,

        'voucher_removed' =>
            $voucher_removed,

        'maxed_out' =>
            $new_qty >=
            $product->stock_qty
    ]);

    exit;
}

// Find member cart
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

// Get cart item
$stm = $pdo->prepare("
    SELECT
        ci.*,
        p.stock_qty,
        p.price
    FROM cart_item ci
    JOIN product p
        ON p.id = ci.product_id
    WHERE ci.cart_id = ?
    AND ci.product_id = ?
");

$stm->execute([
    $cart->id,
    $product_id
]);

$item = $stm->fetch();

if (!$item) {
    echo json_encode([
        'success' => false,
        'message' => 'Item not found in cart.'
    ]);
    exit;
}

$new_qty = $item->quantity;

if ($action === 'increase') {
    $new_qty = min(
        $item->quantity + 1,
        $item->stock_qty
    );
}
elseif ($action === 'decrease') {
    $new_qty = max(
        $item->quantity - 1,
        1
    );
}

$stm = $pdo->prepare("
    UPDATE cart_item
    SET quantity = ?
    WHERE id = ?
");

$stm->execute([
    $new_qty,
    $item->id
]);

$stm = $pdo->prepare("
    UPDATE cart
    SET updated_at = NOW()
    WHERE id = ?
");

$stm->execute([
    $cart->id
]);

$stm = $pdo->prepare("
    SELECT
        SUM(
            ci.quantity * p.price
        ) AS subtotal
    FROM cart_item ci
    JOIN product p
        ON p.id = ci.product_id
    WHERE ci.cart_id = ?
");

$stm->execute([
    $cart->id
]);

$subtotal =
    (float) (
        $stm->fetch()->subtotal ?? 0
    );

$voucher = null;
$discount = 0;

$voucher_valid = false;
$voucher_removed = false;

if (isset($_SESSION['voucher_id'])) {
    $stm = $pdo->prepare("
        SELECT *
        FROM voucher
        WHERE voucher_id = ?
    ");

    $stm->execute([
        $_SESSION['voucher_id']
    ]);

    $voucher = $stm->fetch();

    if ($voucher) {
        $today = date('Y-m-d');
        $voucher_valid =
            $voucher->status === 'Active' &&
            $today >= $voucher->valid_from &&
            $today <= $voucher->valid_until &&
            $subtotal >= $voucher->min_spend &&
            (
                $voucher->max_uses === null ||
                $voucher->used_count <
                    $voucher->max_uses
            );

        // One voucher per member
        if ($voucher_valid && $voucher->one_per_member) {
            $stm = $pdo->prepare("
                SELECT COUNT(*)
                FROM voucher_usage
                WHERE voucher_id = ?
                AND member_id = ?
            ");

            $stm->execute([
                $voucher->voucher_id,
                $member_id
            ]);

            if ($stm->fetchColumn() > 0) {
                $voucher_valid = false;
            }
        }

        if ($voucher_valid) {
            if ($voucher->discount_type === 'Percentage') {
                $discount = $subtotal * ($voucher->discount_value/ 100);

                if ($voucher->max_discount!== null && $discount > $voucher->max_discount) {
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

            $voucher = null;
            $discount = 0;

            $voucher_removed = true;
        }
    }
}

$total = $subtotal - $discount;

echo json_encode([
    'success' => true,
    'quantity' => $new_qty,
    'item_subtotal' =>
        number_format(
            $new_qty *
            $item->price,
            2,
            '.',
            ''
        ),

    'subtotal' =>
        number_format(
            $subtotal,
            2,
            '.',
            ''
        ),

    'discount' =>
        number_format(
            $discount,
            2,
            '.',
            ''
        ),

    'total' =>
        number_format(
            $total,
            2,
            '.',
            ''
        ),

    'voucher_valid' =>
        $voucher_valid,

    'voucher_removed' =>
        $voucher_removed,

    'maxed_out' =>
        $new_qty >=
        $item->stock_qty
]);