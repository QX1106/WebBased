<?php
require '../_base.php';

auth('Member');

header('Content-Type: application/json');

$member_id = $_user->member_id;
$payment_id = $_POST['pay_id'] ?? null;

// Either an existing saved address (address book) or a brand new one
// typed at checkout — exactly one of these two paths is used.
$address_id = $_POST['address_id'] ?? null;
$new_address = trim($_POST['new_address'] ?? '');
$new_address_label = trim($_POST['new_address_label'] ?? '');
$save_new_address = !empty($_POST['save_address']);
$set_as_default = !empty($_POST['set_default']);


// ==========================
// Basic validation
// ==========================

if (!$payment_id) {
    echo json_encode([
        'success' => false,
        'message' =>
            'Payment method is required.'
    ]);
    exit;
}

if (!$address_id && !$new_address) {
    echo json_encode([
        'success' => false,
        'message' =>
            'Shipping address is required.'
    ]);
    exit;
}

if ($new_address && strlen($new_address) > 255) {
    echo json_encode([
        'success' => false,
        'message' =>
            'Shipping address is too long.'
    ]);
    exit;
}

if ($new_address_label && strlen($new_address_label) > 50) {
    echo json_encode([
        'success' => false,
        'message' =>
            'Address label is too long.'
    ]);
    exit;
}


// ==========================
// Resolve the shipping address
// ==========================

$shipping_address = null;

if ($address_id) {

    // Must belong to this member — never trust a raw address_id from
    // the client without checking ownership.
    $stm = $pdo->prepare("
        SELECT *
        FROM member_address
        WHERE address_id = ?
          AND member_id = ?
    ");

    $stm->execute([$address_id, $member_id]);
    $saved_address = $stm->fetch();

    if (!$saved_address) {
        echo json_encode([
            'success' => false,
            'message' =>
                'Selected address is no longer available.'
        ]);
        exit;
    }

    $shipping_address = $saved_address->address;

} else {
    $shipping_address = $new_address;
}


// ==========================
// Validate payment method
// ==========================

$stm = $pdo->prepare("
    SELECT pay_id
    FROM payment
    WHERE pay_id = ?
");

$stm->execute([$payment_id]);

if (!$stm->fetch()) {
    echo json_encode([
        'success' => false,
        'message' =>
            'Invalid payment method.'
    ]);
    exit;
}


// ==========================
// Get member cart
// ==========================

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
        'message' =>
            'Your cart is empty.'
    ]);
    exit;
}


// ==========================
// Get cart items
// ==========================

$stm = $pdo->prepare("
    SELECT
        ci.product_id,
        ci.quantity,
        p.price,
        p.stock_qty,
        p.name
    FROM cart_item ci
    JOIN product p
        ON p.id = ci.product_id
    WHERE ci.cart_id = ?
");

$stm->execute([$cart->id]);

$items = $stm->fetchAll();

if (!$items) {
    echo json_encode([
        'success' => false,
        'message' =>
            'Your cart is empty.'
    ]);
    exit;
}


// ==========================
// Check stock
// ==========================

foreach ($items as $item) {

    if ($item->quantity > $item->stock_qty) {

        echo json_encode([
            'success' => false,
            'message' =>
                $item->name .
                ' no longer has enough stock.'
        ]);

        exit;
    }
}


// ==========================
// Calculate subtotal
// ==========================

$subtotal = 0;

foreach ($items as $item) {

    $subtotal +=
        $item->price *
        $item->quantity;
}


// ==========================
// Revalidate voucher
// ==========================

$voucher = null;
$discount = 0;

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

    if (!$voucher) {

        unset($_SESSION['voucher_id']);

        echo json_encode([
            'success' => false,
            'message' =>
                'The applied voucher no longer exists.'
        ]);

        exit;
    }


    $today = date('Y-m-d');

    if ($voucher->status !== 'Active') {

        unset($_SESSION['voucher_id']);

        echo json_encode([
            'success' => false,
            'message' =>
                'The applied voucher is no longer active.'
        ]);

        exit;
    }


    if ($today < $voucher->valid_from) {

        unset($_SESSION['voucher_id']);

        echo json_encode([
            'success' => false,
            'message' =>
                'The applied voucher is not active yet.'
        ]);

        exit;
    }

    if ($today > $voucher->valid_until) {

        unset($_SESSION['voucher_id']);

        echo json_encode([
            'success' => false,
            'message' =>
                'The applied voucher has expired.'
        ]);

        exit;
    }


    if (
        $subtotal < $voucher->min_spend
    ) {

        unset($_SESSION['voucher_id']);

        echo json_encode([
            'success' => false,
            'message' =>
                'The cart no longer meets the ' .
                'minimum spend for this voucher.'
        ]);

        exit;
    }


    if (
        $voucher->max_uses !== null &&
        $voucher->used_count >=
            $voucher->max_uses
    ) {

        unset($_SESSION['voucher_id']);

        echo json_encode([
            'success' => false,
            'message' =>
                'This voucher has reached its usage limit.'
        ]);

        exit;
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

            echo json_encode([
                'success' => false,
                'message' =>
                    'You have already used this voucher.'
            ]);

            exit;
        }
    }


    // ======================
    // Calculate discount
    // ======================

    if (
        $voucher->discount_type ===
        'Percentage'
    ) {

        $discount =
            $subtotal *
            ($voucher->discount_value / 100);

        if (
            $voucher->max_discount !== null &&
            $discount > $voucher->max_discount
        ) {

            $discount =
                $voucher->max_discount;
        }

    }
    elseif (
        $voucher->discount_type === 'Fixed'
    ) {

        $discount =
            $voucher->discount_value;
    }


    // Never allow negative totals
    $discount =
        min($discount, $subtotal);
}


// ==========================
// Final order total
// ==========================

$total_amount =
    $subtotal - $discount;


// ==========================
// Create order transaction
// ==========================

try {

    $pdo->beginTransaction();


    // ======================
    // Save new address, if requested
    // ======================

    // If the shopper typed a brand-new address rather than picking a
    // saved one, add it to their address book now — inside the same
    // transaction, so a failed order doesn't leave an orphan address
    // behind.
    if (!$address_id && $new_address) {

        $stm = $pdo->prepare("SELECT COUNT(*) FROM member_address WHERE member_id = ?");
        $stm->execute([$member_id]);
        $has_any = $stm->fetchColumn() > 0;

        $make_default = $set_as_default || !$has_any;

        if ($make_default) {
            $pdo->prepare("UPDATE member_address SET is_default = 0 WHERE member_id = ?")
                ->execute([$member_id]);
        }

        $pdo->prepare("
            INSERT INTO member_address
                (member_id, label, address, is_default, created_at)
            VALUES
                (?, ?, ?, ?, NOW())
        ")->execute([
            $member_id,
            $new_address_label ?: null,
            $new_address,
            $make_default ? 1 : 0
        ]);

        $address_id = $pdo->lastInsertId();
    }


    // Create order
    $stm = $pdo->prepare("
        INSERT INTO orders
            (
                member_id,
                order_date,
                total_amount,
                order_status,
                shipping_address,
                address_id,
                payment_id
            )
        VALUES
            (?, NOW(), ?, 'Pending', ?, ?, ?)
    ");

    $stm->execute([
        $member_id,
        $total_amount,
        $shipping_address,
        $address_id,
        $payment_id
    ]);

    $order_id =
        $pdo->lastInsertId();


    // ======================
    // Copy cart into order
    // ======================

    $stm = $pdo->prepare("
        INSERT INTO order_item
            (
                order_id,
                product_id,
                quantity,
                unit_price
            )
        VALUES
            (?, ?, ?, ?)
    ");

    foreach ($items as $item) {

        $stm->execute([
            $order_id,
            $item->product_id,
            $item->quantity,
            $item->price
        ]);
    }


    // ======================
    // Deduct stock
    // ======================

    $stm = $pdo->prepare("
        UPDATE product
        SET stock_qty =
            stock_qty - ?
        WHERE id = ?
    ");

    foreach ($items as $item) {

        $stm->execute([
            $item->quantity,
            $item->product_id
        ]);
    }


    // ======================
    // Record voucher usage
    // ======================

    if ($voucher) {

        $stm = $pdo->prepare("
            INSERT INTO voucher_usage
                (
                    voucher_id,
                    member_id,
                    order_id,
                    used_at
                )
            VALUES
                (?, ?, ?, NOW())
        ");

        $stm->execute([
            $voucher->voucher_id,
            $member_id,
            $order_id
        ]);


        $stm = $pdo->prepare("
            UPDATE voucher
            SET used_count =
                used_count + 1
            WHERE voucher_id = ?
        ");

        $stm->execute([
            $voucher->voucher_id
        ]);
    }


    // ======================
    // Clear cart
    // ======================

    $stm = $pdo->prepare("
        DELETE FROM cart_item
        WHERE cart_id = ?
    ");

    $stm->execute([
        $cart->id
    ]);


    // Remove voucher session
    unset($_SESSION['voucher_id']);


    $pdo->commit();


    echo json_encode([
        'success' => true,
        'order_id' => $order_id
    ]);

}
catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        'success' => false,
        'message' =>
            'Could not create order. Please try again.',

        // Remove after testing
        'debug' => $e->getMessage()
    ]);
}
