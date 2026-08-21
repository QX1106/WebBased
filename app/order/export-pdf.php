<?php require '../_base.php'; ?>
<?php auth('Admin'); ?>
<?php

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
$orders = $stm->fetchAll();

$rows = [];
foreach ($orders as $o) {
    $rows[] = [$o->order_id, $o->username, $o->email, $o->order_date, number_format($o->total_amount, 2), $o->order_status];
}

export_table_pdf(
    'Order List',
    ['Order ID', 'Member Username', 'Member Email', 'Order Date', 'Total (RM)', 'Status'],
    $rows,
    'orders_' . date('Y-m-d') . '.pdf'
);
