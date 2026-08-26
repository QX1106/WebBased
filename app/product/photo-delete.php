<?php require '../_base.php'; ?>
<?php auth('Admin', 'Super Admin'); ?>
<?php

$id = get('id');
$product_id = get('product_id');

$stm = $pdo->prepare("SELECT * FROM product_photo WHERE id = ?");
$stm->execute([$id]);
$photo = $stm->fetch();

if ($photo) {
    if (file_exists(root("photos/{$photo->photo}"))) {
        unlink(root("photos/{$photo->photo}"));
    }
    $stm = $pdo->prepare("DELETE FROM product_photo WHERE id = ?");
    $stm->execute([$id]);
}

redirect('/product/update.php?id=' . $product_id);