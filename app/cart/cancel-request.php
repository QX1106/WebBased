<?php
require '../_base.php';

auth('Member');

$member_id = $_user->member_id;
$order_id = (int) get('order_id');

if (!$order_id) {
    redirect('list.php');
}

// Make sure this order belongs to this member
// and is still Pending
$stm = $pdo->prepare("
    SELECT *
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

// Check whether there is already an active cancellation request
$stm = $pdo->prepare("
    SELECT *
    FROM cancel_request
    WHERE order_id = ?
    AND member_id = ?
    AND status = 'Pending'
");

$stm->execute([
    $order_id,
    $member_id
]);

$existing_request = $stm->fetch();

if ($existing_request) {
    temp('info', 'A cancellation request has already been submitted for this order.');
    redirect("detail.php?id=$order_id");
}
?>

<?php require '../_head.php'; ?>

<div class="cancel-request-page">

    <div class="cancel-request-header">
        <h1>Cancellation Request</h1>
        <p>
            Order #<?= h($order->order_id) ?>
        </p>
    </div>

    <div class="cancel-request-box">

        <p class="cancel-request-description">
            Please tell us why you would like to cancel this order.
            Your request will be reviewed before the order is cancelled.
        </p>

        <form
            method="post"
            action="cancel-request-submit.php"
            enctype="multipart/form-data"
        >

            <input
                type="hidden"
                name="order_id"
                value="<?= h($order->order_id) ?>"
            >

            <div class="cancel-field">
                <label for="reason">
                    Reason for Cancellation
                </label>

                <textarea
                    id="reason"
                    name="reason"
                    required
                    placeholder="Tell us why you would like to cancel this order..."
                ></textarea>
            </div>

            <div class="cancel-request-actions">
                <a href="detail.php?id=<?= $order->order_id ?>" class="cancel-back">← Back to Order</a>
                <button type="submit" class="submit-cancel-request">Submit Request</button>
            </div>
        </form>
    </div>
</div>

<?php require '../_foot.php'; ?>