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

// If someone is logged in and checking their own username/email while
// editing their profile, exclude their own row from the uniqueness
// check — otherwise their current value would always come back as
// "already taken" (by themselves). We use the session's own id here
// rather than trusting a client-supplied id, so this can't be used to
// probe whether a value belongs to an arbitrary member_id.
$except_id = $_user->member_id ?? null;

echo json_encode(['available' => is_unique('member', $field, $value, $except_id, 'member_id')]);
