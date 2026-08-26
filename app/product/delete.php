<?php require '../_base.php'; ?>
<?php auth('Admin'); ?>
<?php

$id = get('id');

$stm = $pdo->prepare("SELECT * FROM product WHERE id = ?");
$stm->execute([$id]);
$product = $stm->fetch();

if (!$product) {
    temp('info', 'Product not found.');
    redirect('/product/list.php');
}

// Practical 6: get photo file name, delete photo, THEN delete the record
if ($product->photo && file_exists(root("photos/{$product->photo}"))) {
    unlink(root("photos/{$product->photo}"));
}

// Clean up gallery photos too — done explicitly here rather than relying
// on a DB foreign key CASCADE, since that step in phpMyAdmin was optional.
$gallery_stm = $pdo->prepare("SELECT * FROM product_photo WHERE product_id = ?");
$gallery_stm->execute([$id]);
foreach ($gallery_stm->fetchAll() as $gp) {
    if (file_exists(root("photos/{$gp->photo}"))) {
        unlink(root("photos/{$gp->photo}"));
    }
}
$pdo->prepare("DELETE FROM product_photo WHERE product_id = ?")->execute([$id]);

$stm = $pdo->prepare("DELETE FROM product WHERE id = ?");
$stm->execute([$id]);

temp('info', "Product '{$product->name}' deleted.");
redirect('/product/list.php');