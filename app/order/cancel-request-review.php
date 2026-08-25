<?php require '../_base.php'; ?>
<?php auth('Admin'); ?>
<?php

$id = get('id');

$stm = $pdo->prepare("SELECT r.*, m.username, m.email
                       FROM cancel_request r
                       JOIN member m ON r.member_id = m.member_id
                       WHERE r.request_id = ?");
$stm->execute([$id]);
$req = $stm->fetch();

if (!$req) {
    temp('info', 'Cancellation request not found.');
    redirect('/order/cancel-requests.php');
}

$stm = $pdo->prepare("SELECT o.*, m.username, m.email
                       FROM orders o
                       JOIN member m ON o.member_id = m.member_id
                       WHERE o.order_id = ?");
$stm->execute([$req->order_id]);
$order = $stm->fetch();

$_err = [];

if (is_post() && $req->status == 'Pending') {
    $action = post('action');

    if ($action == 'approve') {
        $pdo->prepare("UPDATE cancel_request SET status = 'Approved', reviewed_at = NOW(), reviewed_by = ? WHERE request_id = ?")
            ->execute([$_user->member_id, $id]);

        $pdo->prepare("UPDATE orders SET order_status = 'Cancelled' WHERE order_id = ?")
            ->execute([$req->order_id]);

        $pdo->prepare("INSERT INTO order_status_log (order_id, status, note) VALUES (?, 'Cancelled', ?)")
            ->execute([$req->order_id, 'Approved cancellation request — ' . $req->reason]);

        send_email(
            $req->email,
            'Your Cancellation Request Was Approved - Order #' . $req->order_id,
            "<p>Hi {$req->username},</p><p>Your request to cancel Order #{$req->order_id} has been <b>approved</b>. The order is now cancelled.</p><p>Thank you for shopping with us.</p>"
        );

        temp('info', "Cancellation request approved. Order #{$req->order_id} is now Cancelled.");
        redirect('/order/cancel-requests.php');
    } elseif ($action == 'reject') {
        $admin_note = trim(post('admin_note'));
        if ($admin_note == '') {
            $_err['admin_note'] = 'Please explain why this request is being rejected';
        }

        if (!$_err) {
            $pdo->prepare("UPDATE cancel_request SET status = 'Rejected', admin_note = ?, reviewed_at = NOW(), reviewed_by = ? WHERE request_id = ?")
                ->execute([$admin_note, $_user->member_id, $id]);

            send_email(
                $req->email,
                'Your Cancellation Request Was Rejected - Order #' . $req->order_id,
                "<p>Hi {$req->username},</p><p>Your request to cancel Order #{$req->order_id} has been <b>rejected</b>.</p><p><b>Reason:</b> " . h($admin_note) . "</p><p>If you have questions, please contact us.</p>"
            );

            temp('info', "Cancellation request rejected.");
            redirect('/order/cancel-requests.php');
        }
    }
}

$_title = 'Review Cancellation Request';
require '../_head.php';
?>

<h1>Cancellation Request #<?= $req->request_id ?></h1>

<table class="detail">
    <tr><th>Order</th><td><a href="/order/detail.php?id=<?= $order->order_id ?>">#<?= $order->order_id ?></a> — Status: <?= h($order->order_status) ?></td></tr>
    <tr><th>Member</th><td><?= h($req->username) ?> (<?= h($req->email) ?>)</td></tr>
    <tr><th>Requested At</th><td><?= h($req->requested_at) ?></td></tr>
    <tr><th>Reason</th><td><?= nl2br(h($req->reason)) ?></td></tr>
    <?php if ($req->photo): ?>
        <tr><th>Photo</th><td><img src="/uploads/cancel_request/<?= h($req->photo) ?>" style="max-width:300px; border:1px solid var(--border);"></td></tr>
    <?php endif; ?>
    <tr><th>Status</th><td><span class="status-badge status-<?= strtolower($req->status) ?>"><?= h($req->status) ?></span></td></tr>
    <?php if ($req->status != 'Pending'): ?>
        <tr><th>Reviewed At</th><td><?= h($req->reviewed_at) ?></td></tr>
        <?php if ($req->admin_note): ?>
            <tr><th>Admin Note</th><td><?= nl2br(h($req->admin_note)) ?></td></tr>
        <?php endif; ?>
    <?php endif; ?>
</table>

<?php if ($req->status == 'Pending'): ?>
    <h2>Approve</h2>
    <form method="post">
        <input type="hidden" name="action" value="approve">
        <button data-confirm="Approve this request and cancel Order #<?= $order->order_id ?>?">Approve &amp; Cancel Order</button>
    </form>

    <h2>Reject</h2>
    <form method="post">
        <input type="hidden" name="action" value="reject">
        <label for="admin_note">Reason for rejection</label>
        <?= html_textarea('admin_note') ?>
        <?= err('admin_note') ?>
        <button class="btn-danger">Reject Request</button>
    </form>
<?php endif; ?>

<p><a href="/order/cancel-requests.php" class="btn-outline">Back to Cancellation Requests</a></p>

<?php require '../_foot.php'; ?>
