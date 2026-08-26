<?php
require '../_base.php';
auth('Member');

$member_id = $_user->member_id;
$order_id = post('order_id');

if (!$order_id) {
    redirect('orders.php');
}

// Make sure the order belongs to the logged-in member
$stm = $pdo->prepare("
    SELECT *
    FROM orders
    WHERE order_id = ?
    AND member_id = ?
");

$stm->execute([$order_id, $member_id]);
$order = $stm->fetch();

if (!$order) {
    redirect('orders.php');
}

// Only allow pending orders to be paid
if ($order->order_status !== 'pending') {
    redirect("order_details.php?id=$order_id");
}

// For now, simulate successful payment
$stm = $pdo->prepare("
    UPDATE orders
    SET order_status = 'processing'
    WHERE order_id = ?
");

$stm->execute([$order_id]);

temp('info', 'Payment Succesful. Order Created.');
redirect("order_details.php?id=$order_id");