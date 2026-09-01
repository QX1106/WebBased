<?php

require '../_base.php';
auth('Member');

$member_id = $_user->member_id;

$code = trim(post('voucher_code'));
$mode = post('mode', 'cart');

$buy_now_mode = $mode === 'buy_now';

$return_url = $buy_now_mode
    ? 'index.php?mode=buy_now'
    : 'index.php?mode=cart';

// Use a different voucher session depending on mode
$voucher_session_key = $buy_now_mode
    ? 'buy_now_voucher_id'
    : 'voucher_id';

if ($code === '') {
    unset($_SESSION[$voucher_session_key]);
    temp('info', 'Please enter a voucher code.');
    redirect($return_url);
}

$stm = $pdo->prepare("
    SELECT *
    FROM voucher
    WHERE code = ?
");

$stm->execute([$code]);
$voucher = $stm->fetch();

if (!$voucher) {
    unset($_SESSION[$voucher_session_key]);
    temp('info', 'Voucher does not exist.');
    redirect($return_url);
}

if ($voucher->status !== 'Active') {
    unset($_SESSION[$voucher_session_key]);
    temp('info', 'This voucher is inactive.');
    redirect($return_url);
}

$today = date('Y-m-d');

if ($today < $voucher->valid_from) {
    unset($_SESSION[$voucher_session_key]);
    temp('info', 'This voucher is not active yet.');
    redirect($return_url);
}


if ($today > $voucher->valid_until) {
    unset($_SESSION[$voucher_session_key]);
    temp('info', 'Voucher has expired.');
    redirect($return_url);
}

if ($voucher->max_uses !== null && $voucher->used_count >= $voucher->max_uses
) {
    unset($_SESSION[$voucher_session_key]);
    temp('info', 'This voucher has reached its usage limit.');
    redirect($return_url);
}

if ($voucher->one_per_member) {
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
        unset($_SESSION[$voucher_session_key]);

        temp('info', 'You have already used this voucher.');
        redirect($return_url);
    }
}

if ($buy_now_mode) {
    if (!isset($_SESSION['buy_now'])) {
        unset($_SESSION['buy_now_voucher_id']);
        temp('info', 'Your Buy Now session has expired.'
        );
        redirect('/');
    }

    $product_id = (int) $_SESSION['buy_now']['product_id'];

    $quantity = (int) $_SESSION['buy_now']['quantity'];

    // Get current product information
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

        temp('info', 'Product no longer exists.'
        );
        redirect('/');
    }

    // Make sure quantity is still valid
    if ($quantity < 1 || $quantity > $product->stock_qty
    ) {
        unset($_SESSION['buy_now_voucher_id']);

        temp('info', 'The selected quantity is no longer available.');
        redirect($return_url);
    }
    $subtotal = (float) $product->price * $quantity;
} else {
    $stm = $pdo->prepare("
        SELECT SUM(ci.quantity * p.price)
        FROM cart_item ci
        JOIN product p
            ON p.id = ci.product_id
        JOIN cart c
            ON c.id = ci.cart_id
        WHERE c.member_id = ?
    ");

    $stm->execute([$member_id]);
    $subtotal = (float) $stm->fetchColumn();
}

if ($subtotal < $voucher->min_spend) {
    unset($_SESSION[$voucher_session_key]);
    temp(
        'info',
        'Minimum spend of RM ' .
            number_format(
                $voucher->min_spend,
                2
            ) .
            ' is required for this voucher.'
    );
    redirect($return_url);
}

$_SESSION[$voucher_session_key] = $voucher->voucher_id;

temp('info','Voucher applied successfully.');
redirect($return_url);
