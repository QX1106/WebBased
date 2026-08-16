<?php require '../_base.php'; ?>
<?php auth('Admin'); ?>
<?php

// CSV 
$search = get('search', '');

$stm = $pdo->prepare("SELECT member_id, username, email, phone, address, role, status, created_at
                       FROM member
                       WHERE username LIKE ? OR email LIKE ?
                       ORDER BY member_id");
$stm->execute(["%$search%", "%$search%"]);
$rows = $stm->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="members_' . date('Y-m-d') . '.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['ID', 'Username', 'Email', 'Phone', 'Address', 'Role', 'Status', 'Registered']);

foreach ($rows as $m) {
    fputcsv($out, [
        $m->member_id,
        csv_safe($m->username),
        csv_safe($m->email),
        csv_safe($m->phone),
        csv_safe($m->address),
        $m->role,
        $m->status,
        $m->created_at,
    ]);
}

fclose($out);
