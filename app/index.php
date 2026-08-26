<?php require '_base.php'; ?>
<?php require '_head.php'; ?>

<h1>Welcome to Stationary Online Store</h1>
<p>Your one-stop shop for pens, notebooks, and office supplies.</p>

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

$name = get('name', '');
$category_id = get('category_id', '');

$categories = $pdo->query("SELECT id, name FROM category ORDER BY name")
                   ->fetchAll(PDO::FETCH_KEY_PAIR);

$page = get('page', 1);

$sql = "SELECT p.*, c.name AS category_name
        FROM product p
        JOIN category c ON c.id = p.category_id
        WHERE p.name LIKE ?
          AND (p.category_id = ? OR ?)
        ORDER BY $sort $dir";
$params = ["%$name%", $category_id, $category_id == ''];

$p = new SimplePager($pdo, $sql, $params, 10, $page);
$arr = $p->result;

$qs = '&name=' . urlencode($name) . '&category_id=' . urlencode($category_id);

$_title = 'Product Maintenance';
?>

<form method="get" class="filter-form">
    <?= html_search('name', "placeholder='Search product name'") ?>
    <?= html_select('category_id', $categories, 'All Categories') ?>
    <button>Search</button>
    <a href="/">Reset</a>
</form>

<div class="product-grid">

    <?php foreach ($arr as $row): ?>

        <a href="/product/details.php?id=<?= $row->id ?>" class="product-card">

            <div class="product-image">
                <?php if ($row->photo): ?>
                    <img
                        src="/photos/<?= h($row->photo) ?>"
                        alt="<?= h($row->name) ?>"
                    >
                <?php else: ?>
                    <div class="no-photo">No Photo</div>
                <?php endif; ?>
            </div>

            <div class="product-info">

                <h3><?= h($row->name) ?></h3>

                <p class="product-category">
                    <?= h($row->category_name) ?>
                </p>

                <div class="product-bottom">
                    <span class="product-price">
                        RM <?= number_format($row->price, 2) ?>
                    </span>

                    <span class="product-stock">
                        <?= $row->stock_qty ?> left
                    </span>
                </div>

            </div>

        </a>

    <?php endforeach; ?>

</div>

<?php if (!$arr): ?>
    <p>No products found.</p>
<?php endif; ?>

<?php require '_foot.php'; ?>
