<?php require '../_base.php'; ?>
<?php auth('Admin', 'Super Admin'); ?>
<?php

$ids = post('ids', []);

if (!is_array($ids) || !$ids) {
    temp('info', 'No products selected.');
    redirect('/product/list.php');
}

$deleted = 0;

// Soft delete (see product/delete.php for why): mark Inactive instead of
// removing the rows, so past orders referencing these products stay intact.
foreach ($ids as $id) {
    $stm = $pdo->prepare("SELECT id FROM product WHERE id = ?");
    $stm->execute([$id]);
    if (!$stm->fetch()) continue;

    $pdo->prepare("UPDATE product SET status = 'Inactive' WHERE id = ?")->execute([$id]);
    $deleted++;
}

temp('info', "$deleted product(s) deleted. They can be restored from Product Listing (Show inactive).");
redirect('/product/list.php');
