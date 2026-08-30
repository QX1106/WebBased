<?php
require '../_base.php';
auth('Member');
header('Content-Type: application/json');

if (!is_post()) {
    http_response_code(405);
    echo json_encode(['success' => false]);
    exit;
}

$member_id = $_user->member_id;

$mode = post('mode', 'cart');
$payment_session_key = $mode === 'buy_now'
    ? 'buy_now_payment_id'
    : 'cart_payment_id';
$address_session_key = $mode === 'buy_now'
    ? 'buy_now_address_' . $member_id
    : 'cart_address_' . $member_id;

$_SESSION[$payment_session_key] = post('pay_id');

// The cart lets a shopper pick which *existing* saved address to ship
// this order to, and now also lets them tweak that address's text
// inline on the checkout page. Either way we only ever accept an
// address_id, and only once we've confirmed it actually belongs to
// this member; free text is never trusted on its own — it's only ever
// applied to a row we've already verified ownership of.
$address_id = post('address_id');

// Optional: the (possibly edited) address text from the inline box.
// Only present when the shopper has a saved address selected.
$address_text = post('address_text');

if ($address_id) {
    $stm = $pdo->prepare("
        SELECT address_id
        FROM member_address
        WHERE address_id = ?
          AND member_id = ?
    ");
    $stm->execute([$address_id, $member_id]);

    if ($stm->fetch()) {
        $_SESSION[$address_session_key] = $address_id;

        // If the shopper edited the address text inline, save that
        // edit straight back to their saved address record.
        if (is_string($address_text)) {
            $address_text = trim($address_text);

            if ($address_text !== '' && strlen($address_text) <= 255) {
                $pdo->prepare("
                    UPDATE member_address
                    SET address = ?
                    WHERE address_id = ?
                      AND member_id = ?
                ")->execute([$address_text, $address_id, $member_id]);
            }
        }
    }
}

echo json_encode(['success' => true]);
