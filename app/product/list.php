<?php require '../_base.php'; ?>
<?php auth('Admin'); ?>
<?php

// Low-In-Stock Alert: flag any product at or below this quantity.
const LOW_STOCK_THRESHOLD = 10;

$fields = [
    'Name'     => 'p.name',
    'Category' => 'c.name',
    'Price'    => 'p.price',
    'Stock'    => 'p.stock_qty',
];

$sort = get('sort', 'p.name');
in_array($sort, $fields) || $sort = 'p.name';

$dir = get('dir', 'asc');
in_array($dir, ['asc', 'desc']) || $dir = 'asc';

$name = get('name', '');
$category_id = get('category_id', '');
$low_stock_only = get('low_stock', '') == '1';

$categories = $pdo->query("SELECT id, name FROM category ORDER BY name")
                   ->fetchAll(PDO::FETCH_KEY_PAIR);

$page = get('page', 1);

$low_stock_count = $pdo->query("SELECT COUNT(*) FROM product WHERE stock_qty <= " . LOW_STOCK_THRESHOLD)
                        ->fetchColumn();

$sql = "SELECT p.*, c.name AS category_name
        FROM product p
        JOIN category c ON c.id = p.category_id
        WHERE p.name LIKE ?
          AND (p.category_id = ? OR ?)
          AND (? OR p.stock_qty <= " . LOW_STOCK_THRESHOLD . ")
        ORDER BY $sort $dir";
$params = ["%$name%", $category_id, $category_id == '', !$low_stock_only];

$p = new SimplePager($pdo, $sql, $params, 10, $page);
$arr = $p->result;

$qs = '&name=' . urlencode($name) . '&category_id=' . urlencode($category_id) . '&low_stock=' . ($low_stock_only ? '1' : '');

$_title = 'Product Maintenance';
require '../_head.php';
?>

<h1>Product Listing</h1>

<?php if ($low_stock_count > 0): ?>
<p style="background:#fdecea; border:1px solid #f5c2c0; padding:8px 12px; margin-bottom:12px;">
    <strong>⚠ Low Stock Alert:</strong>
    <?= $low_stock_count ?> product(s) at or below <?= LOW_STOCK_THRESHOLD ?> units.
    <a href="/product/list.php?low_stock=1">View them</a>
</p>
<?php endif; ?>

<p><a href="/product/insert.php">+ Add New Product</a></p>

<form method="get" class="filter-form">
    <?= html_search('name', "placeholder='Search product name'") ?>
    <?= html_select('category_id', $categories, 'All Categories') ?>
    <label><input type="checkbox" name="low_stock" value="1" <?= $low_stock_only ? 'checked' : '' ?>> Low stock only</label>
    <button>Search</button>
    <a href="/product/list.php" class="btn-outline">Reset</a>
</form>

<div class="toolbar">
    <a href="/product/export-csv.php?name=<?= urlencode($name) ?>&category_id=<?= urlencode($category_id) ?>" class="btn-accent">Export CSV</a>
</div>

<p>
    <?= $p->count ?> of <?= $p->item_count ?> record(s) |
    Page <?= $p->page ?> of <?= $p->page_count ?>
</p>

<table class="table">
    <tr>
        <th>Photo</th>
        <?= table_headers($fields, $sort, $dir, "$qs&page=$page") ?>
        <th>Actions</th>
    </tr>

    <?php foreach ($arr as $row): ?>
    <?php $is_low = $row->stock_qty <= LOW_STOCK_THRESHOLD; ?>
    <tr style="<?= $is_low ? 'background:#fdecea;' : '' ?>">
        <td>
            <?php if ($row->photo): ?>
                <img src="/photos/<?= h($row->photo) ?>" width="50" height="50">
            <?php else: ?>
                <span class="no-photo">No Photo</span>
            <?php endif; ?>
        </td>
        <td><a href="/product/view.php?id=<?= $row->id ?>"><?= h($row->name) ?></a></td>
        <td><?= h($row->category_name) ?></td>
        <td>RM <?= number_format($row->price, 2) ?></td>
        <td>
            <?= $row->stock_qty ?>
            <?php if ($is_low): ?>
                <span style="color:#c0392b; font-weight:bold;" title="Low stock">⚠</span>
            <?php endif; ?>
        </td>
        <td>
            <a href="/product/update.php?id=<?= $row->id ?>">Edit</a> |
            <a href="/product/delete.php?id=<?= $row->id ?>" onclick="return confirm('Delete this product?')">Delete</a>
        </td>
    </tr>
    <?php endforeach ?>

    <?php if (!$arr): ?>
    <tr><td colspan="6">No products found.</td></tr>
    <?php endif ?>
</table>

<br>

<?= $p->links("&sort=$sort&dir=$dir$qs") ?>

<?php require '../_foot.php'; ?>