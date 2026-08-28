<?php
require '../_base.php';

auth('Member');

$member_id = $_user->member_id;

$order_id = (int) post('order_id');
$reason = trim(post('reason'));

if (!$order_id || $reason === '') {
    temp('info', 'Please provide a reason for cancellation.');
    redirect("cancel-request.php?order_id=$order_id");
}

// Make sure the order belongs to this member
// and is still Pending
$stm = $pdo->prepare("
    SELECT order_id
    FROM orders
    WHERE order_id = ?
    AND member_id = ?
    AND order_status = 'Pending'
");

$stm->execute([
    $order_id,
    $member_id
]);

$order = $stm->fetch();

if (!$order) {
    temp('info', 'This order cannot be cancelled.');
    redirect('list.php');
}


// Prevent duplicate pending requests
$stm = $pdo->prepare("
    SELECT COUNT(*)
    FROM cancel_request
    WHERE order_id = ?
    AND member_id = ?
    AND status = 'Pending'
");

$stm->execute([
    $order_id,
    $member_id
]);

if ($stm->fetchColumn() > 0) {
    temp('info', 'You already have a pending cancellation request for this order.');
    redirect("detail.php?id=$order_id");
}

// Photo not handled yet
$photo = null;

// Insert cancellation request
$stm = $pdo->prepare("
    INSERT INTO cancel_request
        (
            order_id,
            member_id,
            reason,
            photo,
            status,
            requested_at
        )
    VALUES
        (?, ?, ?, ?, 'Pending', NOW())
");

$stm->execute([
    $order_id,
    $member_id,
    $reason,
    $photo
]);

temp('info', 'Cancellation request submitted successfully.');

redirect("order-details.php?id=$order_id");