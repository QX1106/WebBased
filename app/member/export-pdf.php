<?php require '../_base.php'; ?>
<?php auth('Admin'); ?>
<?php

// PDF export — same filter/query as export.php (CSV), different output format
$search = get('search', '');

$stm = $pdo->prepare("SELECT member_id, username, email, phone, address, role, status, created_at
                       FROM member
                       WHERE role = 'Member' AND (username LIKE ? OR email LIKE ? OR phone LIKE ?)
                       ORDER BY member_id");
$stm->execute(["%$search%", "%$search%", "%$search%"]);
$members = $stm->fetchAll();

$rows = [];
foreach ($members as $m) {
    $rows[] = [$m->member_id, $m->username, $m->email, $m->phone, $m->address, $m->role, $m->status, $m->created_at];
}

export_table_pdf(
    'Member List',
    ['ID', 'Username', 'Email', 'Phone', 'Address', 'Role', 'Status', 'Registered'],
    $rows,
    'members_' . date('Y-m-d') . '.pdf'
);
