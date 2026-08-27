<?php
require '../_base.php';

auth('Member');

$member_id = $_user->member_id;
$product_id = (int) post('product_id');

$stm = $pdo->prepare("
    DELETE FROM wishlist
    WHERE member_id = ?
      AND product_id = ?
");

$stm->execute([
    $member_id,
    $product_id
]);

temp('info', 'Product removed from wishlist.');

redirect('wishlist.php');