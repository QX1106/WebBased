<?php
require '../_base.php';

auth('Member');

header('Content-Type: application/json');

$member_id = $_user->member_id;
$payment_id = $_POST['pay_id'] ?? null;
$shipping_address = trim($_POST['shipping_address'] ?? '');

if (!$payment_id) {
    echo json_encode(['success' => false, 'message' => 'Payment method is required.']);
    exit;
}

if (!$shipping_address) {
    echo json_encode(['success' => false, 'message' => 'Shipping address is required.']);
    exit;
}

// Get member's cart
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
    echo json_encode(['success' => false, 'message' => 'Your cart is empty.']);
    exit;
}

// Get cart items, re-checking stock server-side
$stm = $pdo->prepare("
    SELECT ci.product_id, ci.quantity, p.price, p.stock_qty, p.name
    FROM cart_item ci
    JOIN product p ON p.id = ci.product_id
    WHERE ci.cart_id = ?
");
$stm->execute([$cart->id]);
$items = $stm->fetchAll();

if (!$items) {
    echo json_encode(['success' => false, 'message' => 'Your cart is empty.']);
    exit;
}

foreach ($items as $item) {
    if ($item->quantity > $item->stock_qty) {
        echo json_encode([
            'success' => false,
            'message' => $item->name . ' no longer has enough stock.'
        ]);
        exit;
    }
}

$total_amount = 0;
foreach ($items as $item) {
    $total_amount += $item->price * $item->quantity;
}

try {
    $pdo->beginTransaction();

    // Create the order
    $stm = $pdo->prepare("
        INSERT INTO `orders`
            (member_id, order_date, total_amount, order_status, shipping_address, payment_id)
        VALUES
            (?, NOW(), ?, 'pending', ?, ?)
    ");
    $stm->execute([$member_id, $total_amount, $shipping_address, $payment_id]);
    $order_id = $pdo->lastInsertId();

    // Copy items into order_item (snapshot price at time of order)
    $stm = $pdo->prepare("
        INSERT INTO order_item (order_id, product_id, quantity, unit_price)
        VALUES (?, ?, ?, ?)
    ");
    foreach ($items as $item) {
        $stm->execute([$order_id, $item->product_id, $item->quantity, $item->price]);
    }

    // Deduct stock immediately
    $stm = $pdo->prepare("UPDATE product SET stock_qty = stock_qty - ? WHERE id = ?");
    foreach ($items as $item) {
        $stm->execute([$item->quantity, $item->product_id]);
    }

    // Clear the cart now that it's become an order
    $pdo->prepare("DELETE FROM cart_item WHERE cart_id = ?")->execute([$cart->id]);

    $pdo->commit();

    echo json_encode(['success' => true, 'order_id' => $order_id]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Could not create order. Please try again.',
        'debug' => $e->getMessage() // remove this line once it's working
    ]);
}