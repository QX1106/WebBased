<?php require '../../_base.php'; ?>
<?php auth('Super Admin'); ?>
<?php

$search = get('search', '');

$stm = $pdo->prepare("SELECT member_id, username, email, phone, status, created_at
                       FROM member
                       WHERE role = 'Admin'
                         AND username LIKE ?
                       ORDER BY member_id");
$stm->execute(["%$search%"]);
$rows = $stm->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="admins_' . date('Y-m-d') . '.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['ID', 'Username', 'Email', 'Phone', 'Status', 'Registered']);

foreach ($rows as $m) {
    fputcsv($out, [
        $m->member_id,
        csv_safe($m->username),
        csv_safe($m->email),
        csv_safe($m->phone),
        $m->status,
        $m->created_at,
    ]);
}

fclose($out);
