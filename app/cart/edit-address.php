<?php
require '../_base.php';
auth('Member');

$buy_now = get('mode') === 'buy_now' && isset($_SESSION['buy_now']);
$return_url = $buy_now ? 'index.php?mode=buy_now' : 'index.php';
$key = ($buy_now ? 'buy_now_address_' : 'cart_address_') . $_user->member_id;
$draft = $_SESSION[$key . '_temporary'] ?? [];
$address = $draft['address'] ?? '';
$label = $draft['label'] ?? '';
$save_address = $draft['save'] ?? false;
$set_default = $draft['default'] ?? false;
$_err = [];
if (empty($_SESSION['address_token'])) {
    $_SESSION['address_token'] = bin2hex(random_bytes(32));
}

if (is_post()) {
    $address = post('address', '');
    $label = post('label', '');
    $save_address = post('save_address') === '1';
    $set_default = $save_address && post('set_default') === '1';
    $token = post('token', '');

    if (!is_string($token) || !hash_equals($_SESSION['address_token'], $token)) {
        $_err['address'] = 'Please reload this page and try again.';
    } elseif (!is_string($address) || $address === '') {
        $_err['address'] = 'Address is required.';
    } elseif (strlen($address) > 255) {
        $_err['address'] = 'Address is too long (maximum 255 bytes).';
    }
    if (!is_string($label) || strlen($label) > 50) {
        $_err['label'] = 'Label is too long (maximum 50 bytes).';
    }

    if (!$_err) {
        // A session draft does not change the address book.
        // The order handler saves it there only when requested.
        $_SESSION[$key . '_temporary'] = [
            'address' => $address, 'label' => $label,
            'save' => $save_address, 'default' => $set_default
        ];
        $_SESSION[$key] = 'temporary';
        temp('info', 'Delivery address selected. It will be recorded in your order.');
        redirect($return_url);
    }
    if (!is_string($address)) $address = '';
    if (!is_string($label)) $label = '';
}

$_title = 'Use a Different Address';
require '../_head.php';
?>
<div class="address-page">
    <h1>Use a Different Address</h1>
    <p>This address will be saved in your order, even if you do not save it to your address book.</p>
    <form method="post" class="address-form">
        <input type="hidden" name="token" value="<?= h($_SESSION['address_token']) ?>">
        <label for="address">Delivery address</label>
        <textarea id="address" name="address" rows="5" maxlength="255" required><?= h($address) ?></textarea>
        <?= err('address') ?>
        <label for="label">Label (optional, for your address book)</label>
        <input id="label" name="label" maxlength="50" value="<?= h($label) ?>" placeholder="Home, Office">
        <?= err('label') ?>
        <label><input type="checkbox" id="save-address" name="save_address" value="1" <?= $save_address ? 'checked' : '' ?>> Save this address for future orders</label>
        <label><input type="checkbox" id="set-default" name="set_default" value="1" <?= $set_default ? 'checked' : '' ?> <?= !$save_address ? 'disabled' : '' ?>> Make this my default address</label>
        <p class="address-help">Address-book changes happen when the order is successfully created. Your first saved address automatically becomes the default.</p>
        <div class="address-actions">
            <button>Use this address</button>
            <a href="<?= h($return_url) ?>">Cancel</a>
        </div>
    </form>
</div>
<script>
document.getElementById('save-address').addEventListener('change', function() {
    var defaultBox = document.getElementById('set-default');
    defaultBox.disabled = !this.checked;
    if (!this.checked) defaultBox.checked = false;
});
</script>
<?php require '../_foot.php'; ?>
