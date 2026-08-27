<?php

require '../_base.php';
auth('Member');

$member_id = $_user->member_id;

$code = trim(post('voucher_code'));


// Nothing entered
if ($code === '') {

    unset($_SESSION['voucher_id']);

    temp('info', 'Please enter a voucher code.');
    redirect('index.php');
}


// Find voucher
$stm = $pdo->prepare("
    SELECT *
    FROM voucher
    WHERE code = ?
");

$stm->execute([$code]);
$voucher = $stm->fetch();


// Does not exist
if (!$voucher) {

    unset($_SESSION['voucher_id']);

    temp('info', 'Voucher does not exist.');
    redirect('index.php');
}


// Inactive
if ($voucher->status !== 'Active') {

    unset($_SESSION['voucher_id']);

    temp('info', 'This voucher is inactive.');
    redirect('index.php');
}


// Date validation
$today = date('Y-m-d');

if ($today < $voucher->valid_from) {

    unset($_SESSION['voucher_id']);

    temp('info', 'This voucher is not active yet.');
    redirect('index.php');
}

if ($today > $voucher->valid_until) {

    unset($_SESSION['voucher_id']);

    temp('info', 'Voucher has expired.');
    redirect('index.php');
}


// Maximum usage
if (
    $voucher->max_uses !== null &&
    $voucher->used_count >= $voucher->max_uses
) {

    unset($_SESSION['voucher_id']);

    temp('info', 'This voucher has reached its usage limit.');
    redirect('index.php');
}


// One voucher per member
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

        unset($_SESSION['voucher_id']);

        temp('info', 'You have already used this voucher.');
        redirect('index.php');
    }
}


// Get the member's cart subtotal
$stm = $pdo->prepare("
    SELECT SUM(ci.quantity * p.price)
    FROM cart_item ci
    JOIN product p ON p.id = ci.product_id
    JOIN cart c ON c.id = ci.cart_id
    WHERE c.member_id = ?
");

$stm->execute([$member_id]);

$subtotal = (float)$stm->fetchColumn();


// Minimum spending requirement
if ($subtotal < $voucher->min_spend) {

    unset($_SESSION['voucher_id']);

    temp(
        'info',
        'Minimum spend of RM ' .
        number_format($voucher->min_spend, 2) .
        ' is required for this voucher.'
    );

    redirect('index.php');
}


// Voucher is valid
$_SESSION['voucher_id'] = $voucher->voucher_id;

temp('info', 'Voucher applied successfully.');

redirect('index.php');