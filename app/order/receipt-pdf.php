<?php require '../_base.php'; ?>
<?php auth('Admin', 'Super Admin'); ?>
<?php

$id = get('id');

$stm = $pdo->prepare("SELECT o.*, m.username, m.email, m.phone, m.address, p.pay_name
                       FROM orders o
                       JOIN member m ON o.member_id = m.member_id
                       LEFT JOIN payment p ON o.payment_id = p.pay_id
                       WHERE o.order_id = ?");
$stm->execute([$id]);
$order = $stm->fetch();

if (!$order) {
    temp('info', 'Order not found.');
    redirect('list.php');
}

$stm = $pdo->prepare("SELECT oi.*, p.name AS product_name
                       FROM order_item oi
                       JOIN product p ON oi.product_id = p.id
                       WHERE oi.order_id = ?");
$stm->execute([$id]);
$items = $stm->fetchAll();

$pdf = build_order_receipt_pdf($order, $items);
$pdf->Output("receipt-order-{$order->order_id}.pdf", 'D');
