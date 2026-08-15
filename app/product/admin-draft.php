<?php require '../_base.php'; ?>
<?php // auth('Admin'); // TODO: re-enable once login page (teammate's part) is ready ?>
<?php

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

// ---- demo1 + demo2: search by name, filter by category ------------------
$name = get('name', '');
$category_id = get('category_id', '');

$categories = $pdo->query("SELECT id, name FROM category ORDER BY name")
                   ->fetchAll(PDO::FETCH_KEY_PAIR);

// ---- demo5: paging --------------------------------------------------------
$page = get('page', 1);

// ---- demo3: combined WHERE (name LIKE ?) AND (category_id = ? OR ?) -----
$sql = "SELECT p.*, c.name AS category_name
        FROM product p
        JOIN category c ON c.id = p.category_id
        WHERE p.name LIKE ?
          AND (p.category_id = ? OR ?)
        ORDER BY $sort $dir";
$params = ["%$name%", $category_id, $category_id == ''];

$p = new SimplePager($pdo, $sql, $params, 10, $page);
$arr = $p->result;

// ---- demo6: keep search/filter alive across sort + page links -----------
$qs = '&name=' . urlencode($name) . '&category_id=' . urlencode($category_id);

$_title = 'Product Maintenance (Admin Draft)';
require '../_head.php';
?>

<div class="admin-draft-notice" style="background:#fff3cd; border:1px solid #ffe08a; padding:8px 12px; margin-bottom:12px;">
    <strong>Admin Draft</strong> — temporary page for testing before login/auth is wired up.
    Not linked from the public site.
</div>

<h1>Product Listing</h1>

<p><a href="/product/insert.php">+ Add New Product</a></p>

<form method="get" class="filter-form">
    <?= html_search('name', "placeholder='Search product name'") ?>
    <?= html_select('category_id', $categories, 'All Categories') ?>
    <button>Search</button>
    <a href="/product/admin-draft.php">Reset</a>
</form>

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
    <tr>
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
        <td><?= $row->stock_qty ?></td>
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