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

$categories = $pdo->query("SELECT id, name FROM category ORDER BY name")->fetchAll(PDO::FETCH_KEY_PAIR);

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

<?php

$wishlist_ids = [];

if ($_user && $_user->role === 'Member') {
    $stm = $pdo->prepare("
        SELECT product_id
        FROM wishlist
        WHERE member_id = ?
    ");
    $stm->execute([$_user->member_id]);
    $wishlist_ids = $stm->fetchAll(PDO::FETCH_COLUMN);
}
?>

<form method="get" class="filter-form">
    <?= html_search('name', "placeholder='Search product name'") ?>
    <?= html_select('category_id', $categories, 'All Categories') ?>
    <button>Search</button>
    <a href="/">Reset</a>
</form>

<div class="product-grid">

    <?php foreach ($arr as $row): ?>
        <?php
        $is_wishlisted = in_array(
            $row->id,
            $wishlist_ids
        );
        ?>

        <a href="/product/details.php?id=<?= $row->id ?>" class="product-card">
            <?php if ($_user && $_user->role === 'Member'): ?>
                <button
                    type="button"
                    class="wishlist-toggle product-card-wishlist"
                    data-product-id="<?= $row->id ?>"
                    title="<?= $is_wishlisted
                        ? 'Remove from Wishlist'
                        : 'Add to Wishlist'
                    ?>"
                >
                    <?= $is_wishlisted ? '♥' : '♡' ?>
                </button>
            <?php endif; ?>
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

<?php if ($_user && $_user->role === 'Member'): ?>

<script>
(function () {

    document
        .querySelectorAll('.wishlist-toggle')
        .forEach(function (button) {

            button.addEventListener('click', function (e) {

                // Stop the product card link from opening
                e.preventDefault();
                e.stopPropagation();

                var productId =
                    button.dataset.productId;

                fetch('/wishlist/toggle.php', {
                    method: 'POST',

                    headers: {
                        'Content-Type':
                            'application/x-www-form-urlencoded'
                    },

                    body:
                        'product_id=' +
                        encodeURIComponent(productId)
                })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {

                    if (!data.success) {
                        alert(
                            data.message ||
                            'Something went wrong.'
                        );
                        return;
                    }

                    if (data.wishlisted) {

                        button.textContent = '♥';

                        button.title =
                            'Remove from Wishlist';

                    }
                    else {

                        button.textContent = '♡';

                        button.title =
                            'Add to Wishlist';
                    }

                })
                .catch(function () {

                    alert(
                        'Something went wrong. Please try again.'
                    );

                });

            });

        });

})();
</script>
<?php endif; ?>

<?php require '_foot.php'; ?>
