<?php
require '../../_base.php';
auth('Member');

$id = get('id');
$return = get('return', '');
$return_safe = is_string($return) && preg_match('#^/(?!/)[A-Za-z0-9/_\-.?=&]*$#', $return) ? $return : '';
$list_url = '/member/address/list.php' . ($return_safe ? '?return=' . urlencode($return_safe) : '');

// Only the owner can edit this saved address.
$stm = $pdo->prepare('SELECT * FROM member_address WHERE address_id = ? AND member_id = ?');
$stm->execute([$id, $_user->member_id]);
$saved = $stm->fetch();
if (!$saved) {
    temp('info', 'Address not found.');
    redirect($list_url);
}
$address = $saved->address;
$label = $saved->label ?? '';
$_err = [];
if (empty($_SESSION['address_token'])) {
    $_SESSION['address_token'] = bin2hex(random_bytes(32));
}

if (is_post()) {
    $address = post('address', '');
    $label = post('label', '');
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
        $stm = $pdo->prepare('UPDATE member_address SET label = ?, address = ? WHERE address_id = ? AND member_id = ?');
        $stm->execute([$label ?: null, $address, $id, $_user->member_id]);
        // Never update orders.shipping_address: past orders keep their snapshot.
        temp('info', 'Saved address updated. Existing order addresses have not changed.');
        redirect($list_url);
    }
    if (!is_string($address)) $address = '';
    if (!is_string($label)) $label = '';
}
$_title = 'Edit Saved Address';
require '../../_head.php';
?>
<div class="address-page">
    <h1>Edit Saved Address</h1>
    <p>This changes the address book, not the delivery address of existing orders.</p>
    <form method="post" class="address-form">
        <input type="hidden" name="token" value="<?= h($_SESSION['address_token']) ?>">
        <label for="label">Label (optional)</label>
        <input id="label" name="label" maxlength="50" value="<?= h($label) ?>">
        <?= err('label') ?>
        <label for="address">Address</label>
        <textarea id="address" name="address" rows="5" maxlength="255" required><?= h($address) ?></textarea>
        <?= err('address') ?>
        <div class="address-actions">
            <button>Save changes</button>
            <a href="<?= h($list_url) ?>">Cancel</a>
        </div>
    </form>
</div>
<?php require '../../_foot.php'; ?>
