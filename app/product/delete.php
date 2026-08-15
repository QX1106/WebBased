<?php require '../_base.php'; ?>
<?php // auth('Admin'); // TODO: re-enable once login page (teammate's part) is ready ?>
<?php

$id = get('id');

$stm = $pdo->prepare("SELECT * FROM product WHERE id = ?");
$stm->execute([$id]);
$product = $stm->fetch();

if (!$product) {
    temp('info', 'Product not found.');
    redirect('/product/admin-draft.php');
}

// Practical 6: get photo file name, delete photo, THEN delete the record
if ($product->photo && file_exists(root("photos/{$product->photo}"))) {
    unlink(root("photos/{$product->photo}"));
}

$stm = $pdo->prepare("DELETE FROM product WHERE id = ?");
$stm->execute([$id]);

temp('info', "Product '{$product->name}' deleted.");
redirect('/product/admin-draft.php');