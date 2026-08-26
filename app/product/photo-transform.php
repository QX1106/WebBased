<?php require '../_base.php'; ?>
<?php auth('Admin'); ?>
<?php

$id = post('id');
$action = post('action');

$stm = $pdo->prepare("SELECT * FROM product WHERE id = ?");
$stm->execute([$id]);
$product = $stm->fetch();

if (!$product || !$product->photo) {
    temp('info', 'Product or photo not found.');
    redirect('/product/update.php?id=' . $id);
}

$path = root("photos/{$product->photo}");
if (!file_exists($path)) {
    temp('info', 'Photo file is missing.');
    redirect('/product/update.php?id=' . $id);
}

require_once root('lib/SimpleImage.php');
$img = new \claviska\SimpleImage();
$img->fromFile($path);

switch ($action) {
    case 'rotate_left':
        $img->rotate(-90);
        break;
    case 'rotate_right':
        $img->rotate(90);
        break;
    case 'flip_horizontal':
        $img->flip('x');
        break;
    case 'flip_vertical':
        $img->flip('y');
        break;
    default:
        temp('info', 'Unknown action.');
        redirect('/product/update.php?id=' . $id);
}

$img->toFile($path, 'image/jpeg');

// Keep the SESSION-held filename in sync for the edit form (Practical 6 pattern)
$_SESSION['edit_photo'] = $product->photo;

temp('info', 'Photo updated.');
redirect('/product/update.php?id=' . $id);