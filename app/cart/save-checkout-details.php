<?php
require '../_base.php';
auth('Member');
header('Content-Type: application/json');

if (!is_post()) {
    http_response_code(405);
    echo json_encode(['success' => false]);
    exit;
}

$mode = post('mode', 'cart');
$payment_session_key = $mode === 'buy_now'
    ? 'buy_now_payment_id'
    : 'cart_payment_id';

// Addresses are now saved by edit-address.php only.
// This prevents an older cart page from overwriting the new address.
$_SESSION[$payment_session_key] = post('pay_id');

echo json_encode(['success' => true]);
