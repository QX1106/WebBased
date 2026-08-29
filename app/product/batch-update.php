<?php require '../_base.php'; ?>
<?php auth('Admin', 'Super Admin'); ?>
<?php

$ids = post('ids', []);
$direction = post('direction', '');
$unit = post('unit', '');
$amount = post('amount', '');

if (!is_array($ids) || !$ids) {
    temp('info', 'No products selected.');
    redirect('/product/list.php');
}

if (!in_array($direction, ['increase', 'decrease'], true)) {
    temp('info', 'Invalid direction.');
    redirect('/product/list.php');
}

if (!in_array($unit, ['percent', 'fixed'], true)) {
    temp('info', 'Invalid unit.');
    redirect('/product/list.php');
}

if (!is_numeric($amount) || (float)$amount < 0) {
    temp('info', 'Enter a valid amount, 0 or more.');
    redirect('/product/list.php');
}

$amount = (float) $amount;

$updated = 0;
$skipped = [];

foreach ($ids as $id) {
    $stm = $pdo->prepare("SELECT id, name, price FROM product WHERE id = ?");
    $stm->execute([$id]);
    $product = $stm->fetch();
    if (!$product) continue;

    $delta = $unit === 'percent' ? $product->price * ($amount / 100) : $amount;
    $new_price = $direction === 'increase' ? $product->price + $delta : $product->price - $delta;
    $new_price = round($new_price, 2);

    // A batch adjustment should never be able to push a price to zero or
    // below — skip that product rather than silently clamping it, so the
    // admin notices and can adjust it individually instead.
    if ($new_price <= 0) {
        $skipped[] = $product->name . ' (would be RM0.00 or less)';
        continue;
    }

    $pdo->prepare("UPDATE product SET price = ? WHERE id = ?")->execute([$new_price, $id]);
    $updated++;
}

$msg = "$updated product(s) updated.";
if ($skipped) {
    $msg .= ' Skipped: ' . implode(', ', $skipped) . '.';
}
temp('info', $msg);
redirect('/product/list.php');
