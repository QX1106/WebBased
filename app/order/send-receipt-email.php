<?php require '../_base.php'; ?>
<?php auth('Admin'); ?>
<?php

header('Content-Type: application/json');

if (!is_post()) {
    echo json_encode(['ok' => false, 'message' => 'Invalid request.']);
    exit;
}

$id = post('id');

$stm = $pdo->prepare("SELECT o.*, m.username, m.email, m.phone, m.address
                       FROM orders o
                       JOIN member m ON o.member_id = m.member_id
                       WHERE o.order_id = ?");
$stm->execute([$id]);
$order = $stm->fetch();

if (!$order) {
    echo json_encode(['ok' => false, 'message' => 'Order not found.']);
    exit;
}

$stm = $pdo->prepare("SELECT oi.*, p.name AS product_name
                       FROM order_item oi
                       JOIN product p ON oi.product_id = p.id
                       WHERE oi.order_id = ?");
$stm->execute([$id]);
$items = $stm->fetchAll();

$pdf = build_order_receipt_pdf($order, $items);
$pdf_content = $pdf->Output('', 'S');

$body = '
<div style="font-family: Arial, sans-serif; color:#2b2622;">
    <h2 style="margin-bottom:4px;">Stationary Online Store</h2>
    <p>Hi ' . h($order->username) . ',</p>
    <p>Thank you for your order! Here are your order details:</p>
    <table style="border-collapse:collapse; margin:12px 0;">
        <tr><td style="padding:4px 12px 4px 0; color:#8a8175;">Order #</td><td>' . h($order->order_id) . '</td></tr>
        <tr><td style="padding:4px 12px 4px 0; color:#8a8175;">Order Date</td><td>' . h($order->order_date) . '</td></tr>
        <tr><td style="padding:4px 12px 4px 0; color:#8a8175;">Status</td><td>' . h($order->order_status) . '</td></tr>
        <tr><td style="padding:4px 12px 4px 0; color:#8a8175;">Total</td><td><b>RM ' . number_format($order->total_amount, 2) . '</b></td></tr>
    </table>
    <p>Your official receipt is attached as a PDF for your records.</p>
    <p style="color:#8a8175; font-size:12px; margin-top:24px;">Thank you for shopping with us!</p>
</div>
';

$ok = send_email(
    $order->email,
    'Your Receipt for Order #' . $order->order_id,
    $body,
    [['content' => $pdf_content, 'name' => "receipt-order-{$order->order_id}.pdf"]]
);

echo json_encode([
    'ok' => $ok,
    'message' => $ok ? "Receipt emailed to {$order->email}." : 'Failed to send email. Please try again.',
]);
