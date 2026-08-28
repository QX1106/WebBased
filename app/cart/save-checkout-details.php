<?php

require '../_base.php';

auth('Member');

header('Content-Type: application/json');

$mode = post('mode', 'cart');

$buy_now_mode =
    $mode === 'buy_now';

$address_session_key =
    $buy_now_mode
        ? 'buy_now_address'
        : 'cart_address';

$payment_session_key =
    $buy_now_mode
        ? 'buy_now_payment_id'
        : 'cart_payment_id';


$address =
    trim(post('shipping_address'));

$payment_id =
    post('pay_id');


$_SESSION[$address_session_key] =
    $address;

$_SESSION[$payment_session_key] =
    $payment_id;


echo json_encode([
    'success' => true
]);