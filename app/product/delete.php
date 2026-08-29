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

// Soft delete: past orders reference this product by id (order_item.product_id),
// so hard-deleting the row would either be blocked by that foreign key or, worse,
// leave existing order history pointing at nothing. Marking it Inactive instead
// hides it from listings while keeping every past order intact and restorable.
$stm = $pdo->prepare("UPDATE product SET status = 'Inactive' WHERE id = ?");
$stm->execute([$id]);

temp('info', "Product '{$product->name}' deleted. It can be restored from Product Listing (Show inactive).");
redirect('/product/list.php');
