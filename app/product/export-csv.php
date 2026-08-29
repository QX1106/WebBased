<?php require '../_base.php'; ?>
<?php auth('Admin', 'Super Admin'); ?>
<?php

$name = get('name', '');
$category_id = get('category_id', '');

$sql = "SELECT p.*, c.name AS category_name
        FROM product p
        JOIN category c ON c.id = p.category_id
        WHERE p.name LIKE ?
          AND (p.category_id = ? OR ?)
        ORDER BY p.name";
$params = ["%$name%", $category_id, $category_id == ''];

$stm = $pdo->prepare($sql);
$stm->execute($params);
$rows = $stm->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="products.csv"');

$out = fopen('php://output', 'w');

// Header row
fputcsv($out, ['ID', 'Name', 'Category', 'Price (RM)', 'Cost Price (RM)', 'Stock Qty', 'Status', 'Description', 'Photo', 'Video URL']);

foreach ($rows as $row) {
    fputcsv($out, [
        $row->id,
        csv_safe($row->name),
        csv_safe($row->category_name),
        number_format($row->price, 2),
        number_format($row->cost_price, 2),
        $row->stock_qty,
        csv_safe($row->status),
        csv_safe($row->description),
        csv_safe($row->photo),
        csv_safe($row->video_url),
    ]);
}

fclose($out);
exit;