<?php
require '../_base.php';

auth('Member');

header('Content-Type: application/json');

unset($_SESSION['buy_now']);
unset($_SESSION['buy_now_voucher_id']);

echo json_encode([
    'success' => true
]);