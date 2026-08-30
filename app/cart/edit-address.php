<?php
require '../_base.php';
auth('Member');

// Always use the logged-in member, never a member ID from the URL.
$member_id = $_user->member_id;
$mode = get('mode', 'cart');
$buy_now_mode = $mode === 'buy_now' && isset($_SESSION['buy_now']);
$return_url = $buy_now_mode ? 'index.php?mode=buy_now' : 'index.php';

// Include the member ID so different accounts cannot share an address.
$address_session_key = $buy_now_mode
    ? 'buy_now_address_' . $member_id
    : 'cart_address_' . $member_id;

$stm = $pdo->prepare('SELECT address FROM member WHERE member_id = ?');
$stm->execute([$member_id]);
$default_address = $stm->fetchColumn();
$address = $_SESSION[$address_session_key] ?? $default_address ?? '';
$save_default = false;
$_err = [];

// This token checks that the submitted form came from our website.
if (empty($_SESSION['address_token'])) {
    $_SESSION['address_token'] = bin2hex(random_bytes(32));
}

if (is_post()) {
    $address = post('address', '');
    $save_default = post('save_default') === '1';
    $token = post('token', '');

    if (!is_string($token) || !hash_equals($_SESSION['address_token'], $token)) {
        $_err['address'] = 'Invalid form. Please reload this page and try again.';
    } elseif (!is_string($address) || $address === '') {
        $_err['address'] = 'Please enter a shipping address.';
    } elseif (strlen($address) > 255) {
        $_err['address'] = 'Address is too long. Please shorten it (maximum 255 bytes).';
    }

    if (!$_err) {
        if ($save_default) {
            $stm = $pdo->prepare('UPDATE member SET address = ?, updated_at = NOW() WHERE member_id = ?');
            $stm->execute([$address, $member_id]);

            // Keep the profile page in sync with the database.
            $_user->address = $address;
            $_SESSION['user'] = $_user;
        }

        // Without the checkbox, only this checkout address changes.
        $_SESSION[$address_session_key] = $address;
        temp('info', 'Shipping address saved.');
        redirect($return_url);
    }

    // Keep invalid array input out of the textarea.
    if (!is_string($address)) {
        $address = '';
    }
}

$_title = 'Edit Shipping Address';
require '../_head.php';
?>

<link rel="stylesheet" href="/css/edit-address.css">

<div class="address-page">
    <h1>Edit Shipping Address</h1>
    <p>Enter the address where you want this purchase delivered.</p>

    <form method="post" class="address-form">
        <input type="hidden" name="token" value="<?= h($_SESSION['address_token']) ?>">

        <label for="address">Shipping address</label>
        <textarea id="address" name="address" rows="5" maxlength="255" required
            placeholder="House number, street, city, state and postcode"><?= h($address) ?></textarea>
        <?= err('address') ?>

        <label class="address-checkbox">
            <input type="checkbox" name="save_default" value="1" <?= $save_default ? 'checked' : '' ?>>
            Also save as my default address
        </label>
        <p class="address-help">Leave this unticked to keep your current default address.</p>

        <div class="address-actions">
            <button type="submit">Save address</button>
            <a href="<?= h($return_url) ?>">Cancel</a>
        </div>
    </form>
</div>

<?php require '../_foot.php'; ?>
