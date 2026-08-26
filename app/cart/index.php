<?php
require '../_base.php';

auth('Member');

// Get current member ID from session (not URL)
$member_id = $_user->member_id;

// Get this member's address
$stm = $pdo->prepare("
    SELECT address
    FROM member
    WHERE member_id = ?
");

$stm->execute([$member_id]);
$address = $stm->fetchColumn();

// Get payment method
$stm = $pdo->query("
    SELECT *
    FROM payment
");

$payment_methods = $stm->fetchAll();

// Get this member's cart
$stm = $pdo->prepare("
    SELECT id
    FROM cart
    WHERE member_id = ?
    ORDER BY created_at DESC
    LIMIT 1
");

$stm->execute([$member_id]);
$cart = $stm->fetch();

$items = [];

if ($cart) {

    // Automatically make sure quantity does not exceed available stock
    $stm = $pdo->prepare("
        UPDATE cart_item ci
        JOIN product p ON p.id = ci.product_id
        SET ci.quantity = p.stock_qty
        WHERE ci.cart_id = ?
        AND ci.quantity > p.stock_qty
    ");

    $stm->execute([$cart->id]);

    // Get cart products
    $stm = $pdo->prepare("
        SELECT
            ci.product_id,
            ci.quantity,
            p.name,
            p.price,
            p.stock_qty,
            p.photo
        FROM cart_item ci
        JOIN product p ON p.id = ci.product_id
        WHERE ci.cart_id = ?
        ORDER BY p.name
    ");

    $stm->execute([$cart->id]);
    $items = $stm->fetchAll();
}

// Calculate total
$total = 0;

foreach ($items as $item) {
    $total += $item->price * $item->quantity;
}

$_title = 'Shopping Cart';

require '../_head.php';
?>

<h1>Shopping Cart</h1>

<?php if ($items): ?>

<div class="cart-page">
    <!-- LEFT: CART ITEMS -->
    <div class="cart-items" style="min-width: 600px;">

        <div class="cart-header">
            <span>Product</span>
            <span>Unit Price</span>
            <span>Quantity</span>
            <span>Total</span>
            <span></span>
        </div>

        <?php foreach ($items as $item): ?>
    <div class="cart-item" data-row-product-id="<?= $item->product_id ?>">

        <!-- Product -->
        <div class="cart-product">
            <div class="cart-product-image">
                <?php if ($item->photo): ?>
                    <img
                        src="/photos/<?= h($item->photo) ?>"
                        alt="<?= h($item->name) ?>"
                    >
                <?php else: ?>
                    <div class="no-photo">
                        No Photo
                    </div>
                <?php endif; ?>
            </div>

            <div class="cart-product-info">
                <a href="/product/view.php?id=<?= $item->product_id ?>">
                    <?= h($item->name) ?>
                </a>
                <?php if ($item->stock_qty <= 5): ?>
                    <small>
                        Only <?= $item->stock_qty ?> left
                    </small>
                <?php endif; ?>
            </div>
        </div>

        <!-- Price -->
        <div class="cart-price">
            RM <?= number_format($item->price, 2) ?>
        </div>

        <!-- Quantity -->
        <div class="cart-quantity" data-product-id="<?= $item->product_id ?>">
            <button type="button" class="qty-button qty-decrease">−</button>
            <span class="qty-value"><?= $item->quantity ?></span>
            <button
                type="button"
                class="qty-button qty-increase"
                <?= $item->quantity >= $item->stock_qty ? 'disabled' : '' ?>
            >
                +
            </button>
        </div>

        <!-- Item subtotal -->
        <div class="cart-subtotal" data-product-id="<?= $item->product_id ?>">
            RM <?= number_format($item->price * $item->quantity, 2) ?>
        </div>
    </div>
<?php endforeach; ?>

    </div>

    <!-- RIGHT: INVOICE / CHECKOUT SUMMARY -->
    <aside class="cart-summary">
        <h2>Order Summary</h2>
        <div class="summary-row">
            <span>Subtotal</span>
            <span id="cart-subtotal">
                RM <?= number_format($total, 2) ?>
            </span>
        </div>

        <!-- Voucher placeholder -->
        <div class="summary-section">
            <label>Voucher</label>
            <div class="voucher-box">
                <input
                    type="text"
                    placeholder="Enter voucher code"
                    disabled
                >
                <button type="button" disabled>
                    Apply
                </button>
            </div>
            <small>
                Voucher feature will be available during checkout.
            </small>
        </div>

        <!-- Address-->
        <div class="summary-section">
            <label>Address</label>
            <textarea id="shipping-address"><?= $address ?></textarea>
        </div>

        <!-- Payment method placeholder -->
        <div class="summary-section">
            <label>Payment Method</label>
            <select name="pay_id" required>
                <option value="" disabled selected>Select Payment Method</option>

                <?php foreach ($payment_methods as $payment): ?>
                    <option value="<?= $payment->pay_id ?>">
                        <?= htmlspecialchars($payment->pay_name) ?>
                    </option>
                <?php endforeach ?>
            </select>
        </div>
        <div class="summary-divider"></div>

        <div class="summary-total">
            <span>Total</span>
            <strong>
                RM <?= number_format($total, 2) ?>
            </strong>
        </div>

        <button type="button" id="checkout-btn" class="checkout-button">Proceed to Checkout</button>
    </aside>
</div>

<?php else: ?>
    <div class="empty-cart">
        <h2>Your cart is empty</h2>
        <p>
            Looks like you haven't added anything yet.
        </p>
        <a href="../index.php" class="btn-accent">
            Continue Shopping
        </a>
    </div>

<?php endif; ?>

<script>
(function () {

    function postJSON(url, data) {
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: Object.keys(data).map(function (k) {
                return encodeURIComponent(k) + '=' + encodeURIComponent(data[k]);
            }).join('&')
        }).then(function (res) { return res.json(); });
    }

    // Quantity buttons
    document.querySelectorAll('.qty-decrease, .qty-increase').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var wrap = btn.closest('.cart-quantity');
            var productId = wrap.dataset.productId;
            var action = btn.classList.contains('qty-increase') ? 'increase' : 'decrease';

            postJSON('quantity.php', { product_id: productId, action: action })
                .then(function (data) {
                    if (!data.success) {
                        alert(data.message || 'Something went wrong.');
                        return;
                    }

                    wrap.querySelector('.qty-value').textContent = data.quantity;
                    wrap.querySelector('.qty-increase').disabled = data.maxed_out;

                    var subtotalEl = document.querySelector('.cart-subtotal[data-product-id="' + productId + '"]');
                    if (subtotalEl) subtotalEl.textContent = 'RM ' + data.subtotal;

                    var cartSubtotalEl = document.getElementById('cart-subtotal');
                    if (cartSubtotalEl) cartSubtotalEl.textContent = 'RM ' + data.total;

                    var totalEl = document.getElementById('cart-total');
                    if (totalEl) totalEl.textContent = 'RM ' + data.total;
                })
            });
    });

    // Remove buttons
    document.querySelectorAll('.remove-button').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!confirm('Remove this item from your cart?')) return;

            var productId = btn.dataset.productId;

            postJSON('delete.php', { product_id: productId })
                .then(function (data) {
                    if (!data.success) {
                        alert(data.message || 'Something went wrong.');
                        return;
                    }

                    var row = document.querySelector('.cart-item[data-row-product-id="' + productId + '"]');
                    if (row) row.remove();

                    var totalEl = document.getElementById('cart-total');
                    if (totalEl) totalEl.textContent = 'RM ' + data.total;

                    if (data.is_empty) {
                        location.reload(); // switch to the "empty cart" view
                    }
                })
                .catch(function () {
                    alert('Something went wrong. Please try again.');
                });
        });
    });

    // Checkout button
var checkoutBtn = document.getElementById('checkout-btn');
if (checkoutBtn) {
    checkoutBtn.addEventListener('click', function () {
        var paySelect = document.querySelector('select[name="pay_id"]');
        var payId = paySelect ? paySelect.value : '';

        var shippingAddress = document.getElementById('shipping-address').value.trim();

        if (!payId) {
            alert('Please select a payment method.');
            return;
        }

        if (!shippingAddress) {
            alert('Please enter a shipping address.');
            return;
        }

        checkoutBtn.disabled = true;
        checkoutBtn.textContent = 'Processing...';

        postJSON('/order/create-order.php', { pay_id: payId, shipping_address: shippingAddress })
            .then(function (data) {
                if (!data.success) {
                    alert(data.message || 'Could not create order.');
                    checkoutBtn.disabled = false;
                    checkoutBtn.textContent = 'Proceed to Checkout';
                    return;
                }

                window.location.href = 'checkout.php?order_id=' + data.order_id;
            })
            .catch(function () {
                alert('Something went wrong. Please try again.');
                checkoutBtn.disabled = false;
                checkoutBtn.textContent = 'Proceed to Checkout';
            });
    });
}

})();
</script>

<?php require '../_foot.php'; ?>