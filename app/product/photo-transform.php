<?php require '../_base.php'; ?>
<?php auth('Admin', 'Super Admin'); ?>
<?php

$id = post('id');
$action = post('action');
$gallery_id = post('gallery_id');
$photo_idx = post('photo_idx', 0);
$back_url = '/product/update.php?id=' . $id . '&photo_idx=' . $photo_idx;

if ($gallery_id) {
    $stm = $pdo->prepare("SELECT * FROM product_photo WHERE id = ? AND product_id = ?");
    $stm->execute([$gallery_id, $id]);
    $gallery_photo = $stm->fetch();

    if (!$gallery_photo) {
        temp('info', 'Photo not found.');
        redirect($back_url);
    }

    $photo_filename = $gallery_photo->photo;
} else {
    $stm = $pdo->prepare("SELECT * FROM product WHERE id = ?");
    $stm->execute([$id]);
    $product = $stm->fetch();

    if (!$product || !$product->photo) {
        temp('info', 'Product or photo not found.');
        redirect($back_url);
    }

    $photo_filename = $product->photo;
}

$path = root("photos/$photo_filename");
if (!file_exists($path)) {
    temp('info', 'Photo file is missing.');
    redirect($back_url);
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
        redirect($back_url);
}

$img->toFile($path, 'image/jpeg');

if (!$gallery_id) {
    
    $_SESSION['edit_photo'] = $product->photo;
}

temp('info', 'Photo updated.');
redirect($back_url);