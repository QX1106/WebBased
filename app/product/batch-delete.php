<?php require '../_base.php'; ?>
<?php auth('Admin'); ?>
<?php

$ids = post('ids', []);

if (!is_array($ids) || !$ids) {
    temp('info', 'No products selected.');
    redirect('/product/list.php');
}

$deleted = 0;

foreach ($ids as $id) {
    $stm = $pdo->prepare("SELECT * FROM product WHERE id = ?");
    $stm->execute([$id]);
    $product = $stm->fetch();
    if (!$product) continue;

    // Same cleanup as single delete.php: photo file, gallery files + rows
    if ($product->photo && file_exists(root("photos/{$product->photo}"))) {
        unlink(root("photos/{$product->photo}"));
    }

    $gallery_stm = $pdo->prepare("SELECT * FROM product_photo WHERE product_id = ?");
    $gallery_stm->execute([$id]);
    foreach ($gallery_stm->fetchAll() as $gp) {
        if (file_exists(root("photos/{$gp->photo}"))) {
            unlink(root("photos/{$gp->photo}"));
        }
    }
    $pdo->prepare("DELETE FROM product_photo WHERE product_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM product WHERE id = ?")->execute([$id]);

    $deleted++;
}

temp('info', "$deleted product(s) deleted.");
redirect('/product/list.php');