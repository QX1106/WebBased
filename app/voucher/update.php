<?php require '../_base.php'; ?>
<?php auth('Admin', 'Super Admin'); ?>
<?php

$id = get('id');

$stm = $pdo->prepare("SELECT * FROM voucher WHERE voucher_id = ?");
$stm->execute([$id]);
$voucher = $stm->fetch();

if (!$voucher) {
    temp('info', 'Voucher not found.');
    redirect('/voucher/list.php');
}

if (is_get()) {
    $code = $voucher->code;
    $discount_type = $voucher->discount_type;
    $discount_value = $voucher->discount_value;
    $max_discount = $voucher->max_discount;
    $min_spend = $voucher->min_spend;
    $max_uses = $voucher->max_uses;
    $one_per_member = $voucher->one_per_member;
    $valid_from = $voucher->valid_from;
    $valid_until = $voucher->valid_until;
    $status = $voucher->status;
}

$_err = [];

if (is_post()) {
    $code = strtoupper(trim(post('code')));
    $discount_type = post('discount_type');
    $discount_value = post('discount_value');
    $max_discount = post('max_discount');
    $min_spend = post('min_spend', '0');
    $max_uses = post('max_uses');
    $one_per_member = post('one_per_member');
    $valid_from = post('valid_from');
    $valid_until = post('valid_until');
    $status = post('status', 'Active');

    if ($code == '') {
        $_err['code'] = 'Voucher code is required';
    } elseif (!preg_match('/^[A-Z0-9]{3,30}$/', $code)) {
        $_err['code'] = 'Use 3-30 letters/numbers only, e.g. WELCOME10';
    } elseif (!is_unique('voucher', 'code', $code, $id, 'voucher_id')) {
        $_err['code'] = 'A voucher with this code already exists';
    }

    if (!in_array($discount_type, ['Fixed', 'Percentage'], true)) {
        $_err['discount_type'] = 'Choose a discount type';
    }

    if ($discount_value == '') {
        $_err['discount_value'] = 'Discount value is required';
    } elseif (!is_money($discount_value) || (float)$discount_value <= 0) {
        $_err['discount_value'] = 'Enter a valid amount greater than 0';
    } elseif ($discount_type == 'Percentage' && (float)$discount_value > 100) {
        $_err['discount_value'] = 'Percentage cannot exceed 100';
    }

    if ($max_discount !== '' && $max_discount !== null) {
        if (!is_money($max_discount) || (float)$max_discount <= 0) {
            $_err['max_discount'] = 'Enter a valid amount greater than 0, or leave blank for no cap';
        }
    }

    if ($min_spend === '') {
        $min_spend = '0';
    } elseif (!is_money($min_spend)) {
        $_err['min_spend'] = 'Enter a valid amount, e.g. 50.00';
    }

    if ($max_uses !== '' && $max_uses !== null) {
        if (!ctype_digit($max_uses) || (int)$max_uses <= 0) {
            $_err['max_uses'] = 'Enter a whole number greater than 0, or leave blank for unlimited';
        } elseif ((int)$max_uses < (int)$voucher->used_count) {
            $_err['max_uses'] = "Cannot be less than the {$voucher->used_count} time(s) already used";
        }
    }

    if ($valid_from == '' || !is_date($valid_from)) {
        $_err['valid_from'] = 'Enter a valid date';
    }
    if ($valid_until == '' || !is_date($valid_until)) {
        $_err['valid_until'] = 'Enter a valid date';
    } elseif ($valid_from && is_date($valid_from) && $valid_until < $valid_from) {
        $_err['valid_until'] = 'Must be on or after the valid-from date';
    }

    if (!in_array($status, ['Active', 'Inactive'], true)) {
        $status = 'Active';
    }

    if (!$_err) {
        $stm = $pdo->prepare("UPDATE voucher SET
            code = ?, discount_type = ?, discount_value = ?, max_discount = ?, min_spend = ?,
            max_uses = ?, one_per_member = ?, valid_from = ?, valid_until = ?, status = ?
            WHERE voucher_id = ?");
        $stm->execute([
            $code,
            $discount_type,
            $discount_value,
            $max_discount !== '' ? $max_discount : null,
            $min_spend,
            $max_uses !== '' ? $max_uses : null,
            $one_per_member ? 1 : 0,
            $valid_from,
            $valid_until,
            $status,
            $id,
        ]);

        temp('info', "Voucher '$code' updated successfully.");
        redirect('/voucher/list.php');
    }
}

$_title = 'Edit Voucher';
require '../_head.php';
?>

<h1>Edit Voucher</h1>

<p>Used <?= $voucher->used_count ?> time(s) so far.</p>

<form method="post" novalidate>
    <table class="form-table">
        <tr>
            <td>Code</td>
            <td><?= html_text('code', "style='text-transform:uppercase'") ?> <?= err('code') ?></td>
        </tr>
        <tr>
            <td>Discount Type</td>
            <td><?= html_select('discount_type', ['Fixed' => 'Fixed Amount (RM)', 'Percentage' => 'Percentage (%)'], null) ?> <?= err('discount_type') ?></td>
        </tr>
        <tr>
            <td>Discount Value</td>
            <td><?= html_number('discount_value', 0.01, '', 0.01) ?> <?= err('discount_value') ?></td>
        </tr>
        <tr>
            <td>Max Discount (RM)</td>
            <td>
                <?= html_number('max_discount', 0.01, '', 0.01) ?> <?= err('max_discount') ?>
                <p class="hint">Only applies to Percentage vouchers. Sets the maximum RM a customer can save on one order — leave blank for no limit.</p>
            </td>
        </tr>
        <tr>
            <td>Min Spend (RM)</td>
            <td><?= html_number('min_spend', 0, '', 0.01) ?> <?= err('min_spend') ?></td>
        </tr>
        <tr>
            <td>Total Usage Limit</td>
            <td>
                <?= html_number('max_uses', 1, '', 1) ?> <?= err('max_uses') ?>
                <p class="hint">Leave blank for unlimited uses.</p>
            </td>
        </tr>
        <tr>
            <td>One Use Per Member</td>
            <td><?= html_checkbox('one_per_member', 'Each member can only use this voucher once') ?></td>
        </tr>
        <tr>
            <td>Valid From</td>
            <td><?= html_date('valid_from') ?> <?= err('valid_from') ?></td>
        </tr>
        <tr>
            <td>Valid Until</td>
            <td><?= html_date('valid_until') ?> <?= err('valid_until') ?></td>
        </tr>
        <tr>
            <td>Status</td>
            <td><?= html_select('status', ['Active' => 'Active', 'Inactive' => 'Inactive'], null) ?></td>
        </tr>
        <tr>
            <td></td>
            <td>
                <button>Update</button>
                <a href="/voucher/list.php">Cancel</a>
            </td>
        </tr>
    </table>
</form>

<script>
(function () {
    function setErr(id, msg) {
        var el = document.getElementById('err_' + id);
        if (el) el.textContent = msg;
    }

    var code = document.getElementById('code');
    if (code) {
        code.addEventListener('input', function () {
            var v = this.value.trim();
            if (v === '') {
                setErr('code', 'Voucher code is required');
            } else if (!/^[A-Za-z0-9]{3,30}$/.test(v)) {
                setErr('code', 'Use 3-30 letters/numbers only, e.g. WELCOME10');
            } else {
                setErr('code', '');
            }
        });
    }

    var discountType = document.getElementById('discount_type');
    var discountValue = document.getElementById('discount_value');
    function checkDiscountValue() {
        if (!discountValue) return;
        var v = discountValue.value;
        if (v === '') {
            setErr('discount_value', 'Discount value is required');
        } else if (!/^\d+(\.\d{1,2})?$/.test(v) || parseFloat(v) <= 0) {
            setErr('discount_value', 'Enter a valid amount greater than 0');
        } else if (discountType && discountType.value === 'Percentage' && parseFloat(v) > 100) {
            setErr('discount_value', 'Percentage cannot exceed 100');
        } else {
            setErr('discount_value', '');
        }
    }
    if (discountType) {
        discountType.addEventListener('change', function () {
            setErr('discount_type', this.value === '' ? 'Choose a discount type' : '');
            checkDiscountValue();
        });
    }
    if (discountValue) {
        discountValue.addEventListener('input', checkDiscountValue);
    }

    var maxDiscount = document.getElementById('max_discount');
    if (maxDiscount) {
        maxDiscount.addEventListener('input', function () {
            var v = this.value;
            if (v === '') {
                setErr('max_discount', '');
            } else if (!/^\d+(\.\d{1,2})?$/.test(v) || parseFloat(v) <= 0) {
                setErr('max_discount', 'Enter a valid amount greater than 0, or leave blank for no cap');
            } else {
                setErr('max_discount', '');
            }
        });
    }

    var minSpend = document.getElementById('min_spend');
    if (minSpend) {
        minSpend.addEventListener('input', function () {
            var v = this.value;
            if (v !== '' && !/^\d+(\.\d{1,2})?$/.test(v)) {
                setErr('min_spend', 'Enter a valid amount, e.g. 50.00');
            } else {
                setErr('min_spend', '');
            }
        });
    }

    var maxUses = document.getElementById('max_uses');
    if (maxUses) {
        maxUses.addEventListener('input', function () {
            var v = this.value;
            if (v !== '' && (!/^\d+$/.test(v) || parseInt(v, 10) <= 0)) {
                setErr('max_uses', 'Enter a whole number greater than 0, or leave blank for unlimited');
            } else {
                setErr('max_uses', '');
            }
        });
    }

    var validFrom = document.getElementById('valid_from');
    var validUntil = document.getElementById('valid_until');
    function checkDates() {
        if (validFrom && validFrom.value === '') {
            setErr('valid_from', 'Enter a valid date');
        } else if (validFrom) {
            setErr('valid_from', '');
        }
        if (validUntil) {
            if (validUntil.value === '') {
                setErr('valid_until', 'Enter a valid date');
            } else if (validFrom && validFrom.value && validUntil.value < validFrom.value) {
                setErr('valid_until', 'Must be on or after the valid-from date');
            } else {
                setErr('valid_until', '');
            }
        }
    }
    if (validFrom) validFrom.addEventListener('change', checkDates);
    if (validUntil) validUntil.addEventListener('change', checkDates);
})();
</script>

<?php require '../_foot.php'; ?>
