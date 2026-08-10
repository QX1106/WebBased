<?php require '../_base.php'; ?>
<?php auth('Admin'); ?>
<?php

// CSV Export (Additional Module) — reuses the same filters as list.php,
// but exports ALL matching rows, not just the current page.
$status = get('status', '');
$search = get('search', '');
$date_from = get('date_from', '');
$date_to = get('date_to', '');

$conditions = [];
$params = [];

if ($status !== '') {
    $conditions[] = 'o.order_status = ?';
    $params[] = $status;
}
if ($search !== '') {
    $conditions[] = '(m.username LIKE ? OR m.email LIKE ? OR o.order_id = ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = ctype_digit($search) ? $search : 0;
}
if ($date_from !== '' && is_date($date_from)) {
    $conditions[] = 'o.order_date >= ?';
    $params[] = "$date_from 00:00:00";
}
if ($date_to !== '' && is_date($date_to)) {
    $conditions[] = 'o.order_date <= ?';
    $params[] = "$date_to 23:59:59";
}

$where_sql = $conditions ? ('WHERE ' . implode(' AND ', $conditions)) : '';

$stm = $pdo->prepare("SELECT o.order_id, m.username, m.email, o.order_date, o.total_amount, o.order_status
                       FROM orders o
                       JOIN member m ON o.member_id = m.member_id
                       $where_sql
                       ORDER BY o.order_id");
$stm->execute($params);
$rows = $stm->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="orders_' . date('Y-m-d') . '.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['Order ID', 'Member Username', 'Member Email', 'Order Date', 'Total (RM)', 'Status']);

foreach ($rows as $o) {
    fputcsv($out, [
        $o->order_id,
        csv_safe($o->username),
        csv_safe($o->email),
        $o->order_date,
        $o->total_amount,
        $o->order_status,
    ]);
}

fclose($out);
