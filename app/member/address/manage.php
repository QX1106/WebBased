<?php require '../../_base.php'; ?>
<?php auth('Admin', 'Super Admin'); ?>
<?php

$id = get('id');

$stm = $pdo->prepare("SELECT * FROM member WHERE member_id = ? AND role = 'Member'");
$stm->execute([$id]);
$m = $stm->fetch();

if (!$m) {
    temp('info', 'Member not found.');
    redirect('/member/list.php');
}

$_err = [];
$action = post('action');

// ==========================
// Add new address
// ==========================
if (is_post() && $action == 'add') {
    $label = trim(post('label'));
    $address = trim(post('address'));
    $set_default = post('is_default') ? 1 : 0;

    if (!$address) {
        $_err['address'] = 'Address is required';
    } elseif (strlen($address) > 255) {
        $_err['address'] = 'Maximum 255 characters';
    }

    if ($label && strlen($label) > 50) {
        $_err['label'] = 'Maximum 50 characters';
    }

    if (!$_err) {
        $stm = $pdo->prepare("SELECT COUNT(*) FROM member_address WHERE member_id = ?");
        $stm->execute([$id]);
        $has_any = $stm->fetchColumn() > 0;

        if ($set_default || !$has_any) {
            $pdo->prepare("UPDATE member_address SET is_default = 0 WHERE member_id = ?")->execute([$id]);
        }

        $pdo->prepare("INSERT INTO member_address (member_id, label, address, is_default, created_at)
                       VALUES (?, ?, ?, ?, NOW())")
            ->execute([$id, $label ?: null, $address, ($set_default || !$has_any) ? 1 : 0]);

        temp('info', 'Address added for ' . $m->username . '.');
        redirect("/member/address/manage.php?id=$id");
    }
}

// ==========================
// Set as default
// ==========================
if (is_post() && $action == 'set_default') {
    $address_id = post('address_id');

    $stm = $pdo->prepare("SELECT address_id FROM member_address WHERE address_id = ? AND member_id = ?");
    $stm->execute([$address_id, $id]);

    if ($stm->fetch()) {
        $pdo->prepare("UPDATE member_address SET is_default = 0 WHERE member_id = ?")->execute([$id]);
        $pdo->prepare("UPDATE member_address SET is_default = 1 WHERE address_id = ?")->execute([$address_id]);
        temp('info', 'Default address updated.');
    }

    redirect("/member/address/manage.php?id=$id");
}

// ==========================
// Delete
// ==========================
if (is_post() && $action == 'delete') {
    $address_id = post('address_id');

    $stm = $pdo->prepare("SELECT * FROM member_address WHERE address_id = ? AND member_id = ?");
    $stm->execute([$address_id, $id]);
    $addr = $stm->fetch();

    if ($addr) {
        $pdo->prepare("DELETE FROM member_address WHERE address_id = ?")->execute([$address_id]);

        if ($addr->is_default) {
            $stm = $pdo->prepare("SELECT address_id FROM member_address WHERE member_id = ? ORDER BY created_at DESC LIMIT 1");
            $stm->execute([$id]);
            $next = $stm->fetchColumn();
            if ($next) {
                $pdo->prepare("UPDATE member_address SET is_default = 1 WHERE address_id = ?")->execute([$next]);
            }
        }

        temp('info', 'Address deleted.');
    } else {
        temp('info', 'Address not found.');
    }

    redirect("/member/address/manage.php?id=$id");
}

$stm = $pdo->prepare("SELECT * FROM member_address WHERE member_id = ? ORDER BY is_default DESC, created_at DESC");
$stm->execute([$id]);
$addresses = $stm->fetchAll();

$_title = 'Manage Addresses';
require '../../_head.php';
?>

<h1>Manage Addresses</h1>
<p style="color:var(--muted); max-width:480px;">Viewing and managing saved addresses for <strong><?= h($m->username) ?></strong>.</p>

<?php if ($addresses): ?>
<?php foreach ($addresses as $a): ?>
<div style="border:1px solid var(--border); padding:12px 16px; margin-bottom:12px; border-radius:6px; max-width:480px;">
    <?php if ($a->is_default): ?><span style="background:#e4ddd0; padding:2px 8px; border-radius:4px; font-size:12px;">Default</span><?php endif; ?>
    <?php if ($a->label): ?><strong style="display:block; margin-top:4px;"><?= h($a->label) ?></strong><?php endif; ?>
    <p style="margin:4px 0;"><?= nl2br(h($a->address)) ?></p>

    <?php if (!$a->is_default): ?>
    <form method="post" style="display:inline; max-width:none;">
        <input type="hidden" name="action" value="set_default">
        <input type="hidden" name="address_id" value="<?= $a->address_id ?>">
        <button type="submit" class="btn-outline">Set as Default</button>
    </form>
    <?php endif; ?>

    <form method="post" style="display:inline; max-width:none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="address_id" value="<?= $a->address_id ?>">
        <button type="submit" class="btn-danger" data-confirm="Delete this address?">Delete</button>
    </form>
</div>
<?php endforeach; ?>
<?php else: ?>
<p>This member has no saved addresses yet.</p>
<?php endif; ?>

<h2>Add New Address</h2>
<form method="post" novalidate>
    <input type="hidden" name="action" value="add">

    <label for="label">Label (Optional)</label>
    <?= html_text('label', "maxlength='50' placeholder='e.g. Home, Office'") ?>
    <?= err('label') ?>

    <label for="address">Address</label>
    <?= html_textarea('address', "maxlength='255' placeholder='Street, city, state, postcode'") ?>
    <?= err('address') ?>

    <?= html_checkbox('is_default', 'Set as default address') ?>

    <button>Add Address</button>
</form>

<p style="margin-top:24px;"><a href="/member/detail.php?id=<?= $id ?>" class="btn-outline">Back to Member Detail</a></p>

<?php require '../../_foot.php'; ?>
