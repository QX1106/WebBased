<?php
require '../_base.php';
auth('Member');
header('Content-Type: application/json');

$token = post('token', '');
if (!is_post() || !is_string($token) || $token === '' || !hash_equals($_SESSION['address_token'] ?? '', $token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Please reload the cart and try again.']);
    exit;
}

$member_id = $_user->member_id;
$mode = post('mode', 'cart');
$key = ($mode === 'buy_now' ? 'buy_now_address_' : 'cart_address_') . $member_id;
$address_id = post('address_id', '');
$valid = false;

if ($address_id === 'temporary') {
    $valid = !empty($_SESSION[$key . '_temporary']['address']);
} elseif (is_string($address_id) && ctype_digit($address_id)) {
    $stm = $pdo->prepare('SELECT address_id FROM member_address WHERE address_id = ? AND member_id = ?');
    $stm->execute([$address_id, $member_id]);
    $valid = (bool) $stm->fetch();
} elseif ($address_id === '') {
    // Payment can be selected before an address is entered.
    $valid = true;
}

if (!$valid) {
    echo json_encode(['success' => false, 'message' => 'That address is unavailable. Reload the cart and choose again.']);
    exit;
}

$_SESSION[$key] = $address_id;
$payment_key = $mode === 'buy_now' ? 'buy_now_payment_id' : 'cart_payment_id';
$_SESSION[$payment_key] = post('pay_id');

// No INSERT or UPDATE: payment and voucher changes never edit addresses.
echo json_encode(['success' => true]);
