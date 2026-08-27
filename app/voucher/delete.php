<?php require '../_base.php'; ?>
<?php auth('Admin', 'Super Admin'); ?>
<?php

$id = get('id');

$stm = $pdo->prepare("SELECT * FROM voucher WHERE voucher_id = ?");
$stm->execute([$id]);
$voucher = $stm->fetch();

if (!$voucher) {
    temp('info', 'Voucher not found.');
    redirect('/voucher/list.php');
}

$pdo->prepare("DELETE FROM voucher_usage WHERE voucher_id = ?")->execute([$id]);
$pdo->prepare("DELETE FROM voucher WHERE voucher_id = ?")->execute([$id]);

temp('info', "Voucher '{$voucher->code}' deleted.");
redirect('/voucher/list.php');
