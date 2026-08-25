<?php require '../_base.php'; ?>
<?php

// live username/email availability check 
header('Content-Type: application/json');

$field = get('field');
$value = get('value', '');

if (!in_array($field, ['username', 'email'], true) || $value == '') {
    echo json_encode(['available' => null]);
    exit;
}

echo json_encode(['available' => is_unique('member', $field, $value)]);
