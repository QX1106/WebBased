<?php
require '../_base.php';

auth('Member');

// Get current member ID from session (not URL)
$member_id = $_user->member_id;

$mode = get('mode', 'cart');

if ($mode === 'cart') {
    unset($_SESSION['buy_now']);
    unset($_SESSION['buy_now_voucher_id']);
}

$buy_now_mode =
    $mode === 'buy_now' &&
    isset($_SESSION['buy_now']);

$buy_now = $buy_now_mode
    ? $_SESSION['buy_now']
    : null;

$address_session_key = $buy_now_mode
    ? 'buy_now_address_' . $member_id
    : 'cart_address_' . $member_id;

$payment_session_key = $buy_now_mode
    ? 'buy_now_payment_id'
    : 'cart_payment_id';

// All of this member's saved addresses, so they can pick which one
// this order ships to.
$stm = $pdo->prepare("
    SELECT *
    FROM member_address
    WHERE member_id = ?
    ORDER BY is_default DESC, created_at DESC
");
$stm->execute([$member_id]);
$saved_addresses = $stm->fetchAll();

// Which one is currently selected: a session override (if it's still
// one of this member's addresses), else their default.
$selected_address_id = $_SESSION[$address_session_key] ?? null;
$selected_address = null;

foreach ($saved_addresses as $addr) {
    if ((string) $addr->address_id === (string) $selected_address_id) {
        $selected_address = $addr;
        break;
    }
}

if (!$selected_address && $saved_addresses) {
    $selected_address = $saved_addresses[0];
}

$selected_address_id = $selected_address ? $selected_address->address_id : null;

// Temporary addresses are checkout drafts, not address-book records.
$temporary_address = $_SESSION[$address_session_key . '_temporary'] ?? null;
$use_temporary = $temporary_address && (($_SESSION[$address_session_key] ?? '') === 'temporary' || !$saved_addresses);
if ($use_temporary) $selected_address_id = 'temporary';
$address_preview = $use_temporary ? $temporary_address['address'] : ($selected_address->address ?? '');
if (empty($_SESSION['address_token'])) {
    $_SESSION['address_token'] = bin2hex(random_bytes(32));
}

// Get payment method
$stm = $pdo->query("
    SELECT *
    FROM payment
");

$payment_methods = $stm->fetchAll();
$selected_payment_id =
    $_SESSION[$payment_session_key]
    ?? '';

$items = [];
$cart = null;

if ($buy_now_mode) {

    // --------------------------------------------------------------
    // BUY NOW MODE
    // --------------------------------------------------------------

    $product_id =
        (int) $buy_now['product_id'];

    $quantity =
        (int) $buy_now['quantity'];

    $stm = $pdo->prepare("
        SELECT
            p.id AS product_id,
            ? AS quantity,
            p.name,
            p.price,
            p.stock_qty,
            p.photo
        FROM product p
        WHERE p.id = ?
    ");

    $stm->execute([
        $quantity,
        $product_id
    ]);

    $item = $stm->fetch();

    if ($item) {
        if ($item && $item->stock_qty > 0) {

            if ($item->quantity > $item->stock_qty) {
                $item->quantity = $item->stock_qty;
                $_SESSION['buy_now']['quantity'] = $item->stock_qty;
            }

            $items = [$item];
        } else {

            unset($_SESSION['buy_now']);
            unset($_SESSION['buy_now_voucher_id']);

            temp('info', 'This product is currently unavailable.');
            redirect('../index.php');
        }
    }
} else {

    // --------------------------------------------------------------
    // NORMAL CART MODE
    // --------------------------------------------------------------

    $stm = $pdo->prepare("
        SELECT id
        FROM cart
        WHERE member_id = ?
        ORDER BY created_at DESC
        LIMIT 1
    ");

    $stm->execute([$member_id]);

    $cart = $stm->fetch();


    if ($cart) {

        $stm = $pdo->prepare("
            UPDATE cart_item ci
            JOIN product p
                ON p.id = ci.product_id
            SET ci.quantity = p.stock_qty
            WHERE ci.cart_id = ?
              AND ci.quantity > p.stock_qty
        ");

        $stm->execute([$cart->id]);


        $stm = $pdo->prepare("
            SELECT
                ci.product_id,
                ci.quantity,
                p.name,
                p.price,
                p.stock_qty,
                p.photo
            FROM cart_item ci

            JOIN product p
                ON p.id = ci.product_id

            WHERE ci.cart_id = ?

            ORDER BY p.name
        ");

        $stm->execute([$cart->id]);

        $items =
            $stm->fetchAll();
    }
}

// Calculate total
$subtotal = 0;

foreach ($items as $item) {
    $subtotal += $item->price * $item->quantity;
}

$voucher = null;
$discount = 0;

$voucher_session_key = $buy_now_mode
    ? 'buy_now_voucher_id'
    : 'voucher_id';

// Check whether a voucher has already been applied
if (isset($_SESSION[$voucher_session_key])) {

    $stm = $pdo->prepare("
        SELECT *
        FROM voucher
        WHERE voucher_id = ?
    ");

    $stm->execute([$_SESSION[$voucher_session_key]]);
    $voucher = $stm->fetch();

    if ($voucher) {

        $today = date('Y-m-d');

        $valid =
            $voucher->status === 'Active' &&
            $today >= $voucher->valid_from &&
            $today <= $voucher->valid_until &&
            $subtotal >= $voucher->min_spend &&
            (
                $voucher->max_uses === null ||
                $voucher->used_count < $voucher->max_uses
            );

        // One voucher use per member
        if ($valid && $voucher->one_per_member) {

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
                $valid = false;
            }
        }

        if ($valid) {

            if ($voucher->discount_type === 'Percentage') {
                $discount =
                    $subtotal *
                    ($voucher->discount_value / 100);

                if (
                    $voucher->max_discount !== null &&
                    $discount > $voucher->max_discount
                ) {
                    $discount = $voucher->max_discount;
                }
            } elseif ($voucher->discount_type === 'Fixed') {
                $discount = $voucher->discount_value;
            }

            // Total must never become negative
            $discount = min($discount, $subtotal);
        } else {
            unset($_SESSION[$voucher_session_key]);
            $voucher = null;
            $discount = 0;
        }
    }
}

$total = $subtotal - $discount;

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
            </div>

            <?php foreach ($items as $item): ?>
                <div class="cart-item" data-row-product-id="<?= $item->product_id ?>">

                    <!-- Product -->
                    <div class="cart-product">
                        <div class="cart-product-image">
                            <?php if ($item->photo): ?>
                                <img
                                    src="/photos/<?= h($item->photo) ?>"
                                    alt="<?= h($item->name) ?>">
                            <?php else: ?>
                                <div class="no-photo">
                                    No Photo
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="cart-product-info">
                            <a href="/product/details.php?id=<?= $item->product_id ?>">
                                <?= h($item->name) ?>
                            </a>
                            <?php if ($item->stock_qty <= 5): ?>
                                <small>Only <?= $item->stock_qty ?> left</small>
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
                            <?= $item->quantity >= $item->stock_qty ? 'disabled' : '' ?>>+</button>
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
                    RM <?= number_format($subtotal, 2) ?>
                </span>
            </div>

            <!-- Voucher -->
            <div class="summary-section">
                <label>Voucher</label>
                <form
                    method="post"
                    action="voucher.php"
                    id="voucher-form">

                    <input
                        type="hidden"
                        name="mode"
                        value="<?= $buy_now_mode
                                    ? 'buy_now'
                                    : 'cart'
                                ?>">

                    <div class="voucher-box">

                        <input
                            type="text"
                            name="voucher_code"
                            id="voucher-code"
                            placeholder="Enter voucher code"
                            value="<?= $voucher
                                        ? h($voucher->code)
                                        : ''
                                    ?>">

                        <button type="submit">
                            Apply
                        </button>

                    </div>

                </form>
            </div>

            <!-- Address-->
            <div class="summary-section">
                <label for="shipping-address-select">Shipping Address</label>

                <?php if ($saved_addresses || $temporary_address): ?>
                    <select id="shipping-address-select">
                        <?php if ($temporary_address): ?>
                            <option value="temporary" data-address="<?= h($temporary_address['address']) ?>" <?= $use_temporary ? 'selected' : '' ?>>Different address for this purchase</option>
                        <?php endif; ?>
                        <?php foreach ($saved_addresses as $addr): ?>
                            <option
                                value="<?= $addr->address_id ?>"
                                data-address="<?= h($addr->address) ?>"
                                <?= (string) $selected_address_id === (string) $addr->address_id
                                    ? 'selected'
                                    : ''
                                ?>>
                                <?= h($addr->label ?: 'Address') ?><?= $addr->is_default ? ' (Default)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <textarea
                        class="address-preview"
                        id="shipping-address-display"
                        readonly
                        maxlength="255"
                        rows="3"
                        style="border:1px solid var(--border); border-radius:6px; padding:8px 10px; margin-top:6px; width:100%; resize:vertical; font:inherit; white-space:pre-line;"
                    ><?= h($address_preview) ?></textarea>
                    <p style="color:var(--muted); font-size:12px; margin:4px 0 0;">
                        Selecting an address does not change your default. The delivery address is copied into your order.
                    </p>
                <?php else: ?>
                    <p>You don't have a shipping address on file yet.</p>
                <?php endif; ?>

                <a href="edit-address.php?mode=<?= $buy_now_mode ? 'buy_now' : 'cart' ?>" style="text-decoration: underline;">Use a different address</a>
                <br>
                <a
                    href="/member/address/list.php?return=<?= urlencode(
                        '/cart/index.php' . ($buy_now_mode ? '?mode=buy_now' : '')
                    ) ?>"
                    style="text-decoration: underline;">
                    Manage saved addresses
                </a>
            </div>

            <!-- Payment method -->
            <div class="summary-section">
                <label>Payment Method</label>
                <select
                    name="pay_id"
                    id="payment-method"
                    required>
                    <option
                        value=""
                        disabled
                        <?= $selected_payment_id === '' ? 'selected' : '' ?>>
                        Select Payment Method
                    </option>

                    <?php foreach ($payment_methods as $payment): ?>
                        <option
                            value="<?= $payment->pay_id ?>"
                            <?= (string)$selected_payment_id === (string)$payment->pay_id
                                ? 'selected'
                                : ''
                            ?>>
                            <?= h($payment->pay_name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($voucher && $discount > 0): ?>
                <div class="summary-divider" id="voucher-divider"></div>
                <div class="summary-row" id="voucher-discount-row">
                    <span>Voucher Discount<small>(<?= h($voucher->code) ?>)</small></span>
                    <span id="voucher-discount">
                        - RM <?= number_format($discount, 2) ?>
                        <button type="button" id="remove-voucher-btn" class="remove-voucher-link">REMOVE</button>
                    </span>
                </div>
            <?php endif; ?>

            <div class="summary-total">
                <span>Total</span>
                <strong id="cart-total">RM <?= number_format($total, 2) ?></strong>
            </div>

            <button type="button" id="checkout-btn" class="checkout-pay-button">Proceed to Checkout</button>
        </aside>
    </div>

<?php else: ?>
    <div class="empty-cart">
        <h2>Your cart is empty</h2>
        <p>Looks like you haven't added anything yet.</p>
        <a href="../index.php" class="btn-accent">Continue Shopping</a>
    </div>

<?php endif; ?>

<script>
    (function() {

        function postJSON(url, data) {
            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: Object.keys(data).map(function(k) {
                    return encodeURIComponent(k) + '=' + encodeURIComponent(data[k]);
                }).join('&')
            }).then(function(res) {
                return res.json();
            });
        }

        // Reusable: remove an item from the cart (used by the remove button
        // and by "decrease past 1" on the quantity buttons)
        function removeItem(productId, row) {

            var buyNowMode =
                <?= $buy_now_mode ? 'true' : 'false' ?>;

            if (buyNowMode) {

                return postJSON(
                        'buy-now-remove.php', {}
                    )
                    .then(function(data) {

                        if (!data.success) {
                            alert(
                                data.message ||
                                'Something went wrong.'
                            );
                            return;
                        }

                        window.location.href =
                            '/cart/index.php?mode=buy_now';
                    });
            }

            return postJSON(
                    'delete.php', {
                        product_id: productId
                    }
                )
                .then(function(data) {

                    if (!data.success) {
                        alert(
                            data.message ||
                            'Something went wrong.'
                        );
                        return;
                    }

                    if (row) {
                        row.remove();
                    }

                    var subtotalEl =
                        document.getElementById(
                            'cart-subtotal'
                        );

                    if (subtotalEl) {
                        subtotalEl.textContent =
                            'RM ' + data.subtotal;
                    }

                    var discountEl =
                        document.getElementById(
                            'voucher-discount'
                        );

                    if (discountEl) {
                        discountEl.textContent =
                            '- RM ' + data.discount;
                    }

                    var totalEl =
                        document.getElementById(
                            'cart-total'
                        );

                    if (totalEl) {
                        totalEl.textContent =
                            'RM ' + data.total;
                    }

                    if (data.is_empty) {
                        location.reload();
                    }

                });
        }

        // Quantity buttons
        document.querySelectorAll('.qty-decrease, .qty-increase').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var wrap = btn.closest('.cart-quantity');
                var productId = wrap.dataset.productId;
                var action = btn.classList.contains('qty-increase') ? 'increase' : 'decrease';
                var currentQty = parseInt(wrap.querySelector('.qty-value').textContent, 10);

                // Decreasing from 1 (or landing on 0) -> offer to remove instead
                if (action === 'decrease' && currentQty <= 1) {
                    if (!confirm('Remove this item from your cart?')) return;

                    var row = btn.closest('.cart-item');
                    removeItem(productId, row);
                    return;
                }

                postJSON('quantity.php', {
                        product_id: productId,
                        action: action,
                        mode: '<?= $buy_now_mode ? 'buy_now' : 'cart' ?>'
                    })
                    .then(function(data) {

                        if (!data.success) {
                            alert(data.message || 'Something went wrong.');
                            return;
                        }

                        wrap.querySelector('.qty-value').textContent = data.quantity;
                        wrap.querySelector('.qty-increase').disabled = data.maxed_out;

                        var subtotalEl = document.querySelector(
                            '.cart-subtotal[data-product-id="' + productId + '"]'
                        );
                        if (subtotalEl) subtotalEl.textContent = 'RM ' + data.item_subtotal;

                        var cartSubtotalEl = document.getElementById('cart-subtotal');
                        if (cartSubtotalEl) cartSubtotalEl.textContent = 'RM ' + data.subtotal;

                        var discountEl = document.getElementById('voucher-discount');
                        if (discountEl) discountEl.textContent = '- RM ' + data.discount;

                        var totalEl = document.getElementById('cart-total');
                        if (totalEl) totalEl.textContent = 'RM ' + data.total;

                        if (data.voucher_removed) {
                            var voucherRow = document.getElementById('voucher-discount-row');
                            if (voucherRow) voucherRow.remove();

                            alert('Voucher removed because the requirements are no longer met.');
                        }
                    });
            });
        });

        // Remove buttons (per cart item)
        document.querySelectorAll('.remove-button').forEach(function(btn) {
            btn.addEventListener('click', function() {
                if (!confirm('Remove this item from your cart?')) return;

                var productId = btn.dataset.productId;
                var row = document.querySelector('.cart-item[data-row-product-id="' + productId + '"]');
                removeItem(productId, row);
            });
        });

        // Remove voucher
        var removeVoucherBtn = document.getElementById('remove-voucher-btn');
        if (removeVoucherBtn) {
            removeVoucherBtn.addEventListener('click', function() {
                postJSON(
                        'voucher-remove.php', {
                            mode: '<?= $buy_now_mode ? 'buy_now' : 'cart' ?>'
                        }
                    )
                    .then(function(data) {
                        if (!data.success) {
                            alert(data.message || 'Something went wrong.');
                            return;
                        }

                        var voucherRow = document.getElementById('voucher-discount-row');
                        if (voucherRow) voucherRow.remove();

                        var voucherInput = document.querySelector('input[name="voucher_code"]');
                        if (voucherInput) voucherInput.value = '';

                        var subtotalEl = document.getElementById('cart-subtotal');
                        if (subtotalEl) subtotalEl.textContent = 'RM ' + data.subtotal;

                        var totalEl = document.getElementById('cart-total');
                        if (totalEl) totalEl.textContent = 'RM ' + data.total;
                    })
                    .catch(function() {
                        alert('Something went wrong. Please try again.');
                    });
            });
        }

        // Checkout button
        var checkoutBtn =
            document.getElementById('checkout-btn');

        if (checkoutBtn) {

            checkoutBtn.addEventListener(
                'click',
                function() {

                    var paySelect =
                        document.querySelector(
                            'select[name="pay_id"]'
                        );

                    var payId =
                        paySelect ?
                        paySelect.value :
                        '';

                    var addressSelect =
                        document.getElementById('shipping-address-select');

                    var addressId =
                        addressSelect ? addressSelect.value : '';


                    // ------------------------------------------------------
                    // Check unapplied voucher
                    // ------------------------------------------------------

                    var voucherInput =
                        document.getElementById(
                            'voucher-code'
                        );

                    var enteredVoucher =
                        voucherInput ?
                        voucherInput.value.trim() :
                        '';

                    var appliedVoucher =
                        <?= json_encode(
                            $voucher
                                ? $voucher->code
                                : ''
                        ) ?>;


                    if (
                        enteredVoucher !== '' &&
                        enteredVoucher.toUpperCase() !==
                        appliedVoucher.toUpperCase()
                    ) {

                        var shouldApply =
                            confirm(
                                'You entered a voucher code but have not applied it.\n\n' +
                                'Press OK to apply the voucher first.\n' +
                                'Press Cancel to continue without the voucher.'
                            );


                        if (shouldApply) {

                            document
                                .getElementById(
                                    'voucher-form'
                                )
                                .submit();

                            return;
                        }
                    }


                    // ------------------------------------------------------
                    // Payment validation
                    // ------------------------------------------------------

                    if (!payId) {

                        alert(
                            'Please select a payment method.'
                        );

                        return;
                    }


                    // ------------------------------------------------------
                    // Address validation
                    // ------------------------------------------------------

                    if (!addressId) {

                        alert(
                            'Please add a shipping address before checking out.'
                        );

                        return;
                    }


                    checkoutBtn.disabled = true;

                    checkoutBtn.textContent =
                        'Processing...';


                    // ------------------------------------------------------
                    // Remember the selection only, without editing saved addresses.
                    // ------------------------------------------------------

                    saveCheckoutDetails()
                        .then(function() {
                            return postJSON(
                                '/order/create-order.php', {
                                    pay_id: payId,

                                    address_id: addressId,
                                    token: '<?= h($_SESSION['address_token']) ?>',

                                    mode: '<?= $buy_now_mode ? 'buy_now' : 'cart' ?>'
                                }
                            );
                        })
                        .then(function(data) {

                            if (!data.success) {

                                alert(
                                    data.message ||
                                    'Could not create order.'
                                );

                                checkoutBtn.disabled =
                                    false;

                                checkoutBtn.textContent =
                                    'Proceed to Checkout';

                                return;
                            }


                            window.location.href =
                                'checkout.php?order_id=' +
                                data.order_id;

                        })
                        .catch(function(error) {

                            alert(
                                error.message || 'Something went wrong. Please try again.'
                            );

                            checkoutBtn.disabled =
                                false;

                            checkoutBtn.textContent =
                                'Proceed to Checkout';
                        });
                }
            );
        }

        function saveCheckoutDetails() {
            var payment =
                document
                .getElementById('payment-method')
                .value;

            var addressEl =
                document.getElementById('shipping-address-select');

            return postJSON(
                'save-checkout-details.php', {
                    pay_id: payment,
                    address_id: addressEl ? addressEl.value : '',
                    token: '<?= h($_SESSION['address_token']) ?>',
                    mode: '<?= $buy_now_mode ? 'buy_now' : 'cart' ?>'
                }
            ).then(function(data) {
                if (!data.success) throw new Error(data.message || 'Could not save checkout details.');
                return data;
            });
        }

        // Save when payment method changes
        document
            .getElementById('payment-method')
            .addEventListener(
                'change',
                function() { saveCheckoutDetails().catch(function(error) { alert(error.message); }); }
            );

        // Update the preview and save when a different saved address
        // is picked
        var addressSelectEl =
            document.getElementById('shipping-address-select');

        if (addressSelectEl) {
            addressSelectEl.addEventListener('change', function() {

                var opt =
                    addressSelectEl.options[
                        addressSelectEl.selectedIndex
                    ];

                var preview =
                    document.getElementById('shipping-address-display');

                if (preview) {
                    preview.value =
                        opt ? (opt.dataset.address || '') : '';
                }

                saveCheckoutDetails().catch(function(error) { alert(error.message); });
            });
        }


        // Save checkout details before applying voucher
        var voucherForm =
            document.getElementById('voucher-form');

        if (voucherForm) {

            voucherForm.addEventListener(
                'submit',
                function(e) {

                    e.preventDefault();

                    saveCheckoutDetails()
                        .then(function() {
                            voucherForm.submit();
                        }).catch(function(error) { alert(error.message); });
                }
            );
        }
    })();
</script>

<?php require '../_foot.php'; ?>
