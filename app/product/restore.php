<?php require '../_base.php'; ?>
<?php auth('Admin', 'Super Admin'); ?>
<?php

$id = get('id');

$stm = $pdo->prepare("SELECT * FROM product WHERE id = ?");
$stm->execute([$id]);
$product = $stm->fetch();

if (!$product) {
    temp('info', 'Product not found.');
    redirect('/product/list.php');
}

$pdo->prepare("UPDATE product SET status = 'Active' WHERE id = ?")->execute([$id]);

temp('info', "Product '{$product->name}' restored.");
redirect('/product/list.php');
