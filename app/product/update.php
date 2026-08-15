<?php require '../_base.php'; ?>
<?php // auth('Admin'); // TODO: re-enable once JW login page is ready ?>
<?php

$id = get('id');

if (is_get()) {
    $stm = $pdo->prepare("SELECT * FROM product WHERE id = ?");
    $stm->execute([$id]);
    $product = $stm->fetch();

    if (!$product) {
        temp('info', 'Product not found.');
        redirect('/product/admin-draft.php');
    }

    $name        = $product->name;
    $category_id = $product->category_id;
    $price       = $product->price;
    $stock_qty   = $product->stock_qty;
    $description = $product->description;

    // ---- Practical 6: keep photo filename in SESSION -----------------------
   
    $_SESSION['edit_photo'] = $product->photo;
}

$photo = $_SESSION['edit_photo'] ?? null;

$categories = $pdo->query("SELECT id, name FROM category ORDER BY name")
                   ->fetchAll(PDO::FETCH_KEY_PAIR);

$_err = [];

if (is_post()) {
    $name        = post('name');
    $category_id = post('category_id');
    $price       = post('price');
    $stock_qty   = post('stock_qty');
    $description = post('description');

    if ($name == '') {
        $_err['name'] = 'Name is required';
    } elseif (!is_unique('product', 'name', $name, $id, 'id')) {
        $_err['name'] = 'A product with this name already exists';
    }

    if ($category_id == '') {
        $_err['category_id'] = 'Category is required';
    }

    if ($price == '') {
        $_err['price'] = 'Price is required';
    } elseif (!is_money($price)) {
        $_err['price'] = 'Enter a valid price, e.g. 12.50';
    } elseif ((float)$price <= 0) {
        $_err['price'] = 'Price must be greater than RM0.00';
    }

    if ($stock_qty === '') {
        $_err['stock_qty'] = 'Stock quantity is required';
    } elseif (!ctype_digit($stock_qty)) {
        $_err['stock_qty'] = 'Stock quantity must be a whole number, 0 or more';
    }

    // Photo is optional on update — only validate if a new one is chosen
    $f = get_file('photo');
    if ($f) {
        if (strpos($f->type, 'image/') !== 0) {
            $_err['photo'] = 'File must be an image';
        } elseif ($f->size > 1 * 1024 * 1024) {
            $_err['photo'] = 'Image must not exceed 1MB';
        }
    }

    if (!$_err) {
        if ($f) {
            // Practical 6: delete old photo, save new one
            if ($photo && file_exists(root("photos/$photo"))) {
                unlink(root("photos/$photo"));
            }
            $new_photo_raw = save_photo($f, 'photos', 400, 300);

            // Same readable-name treatment as insert.php
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($name)));
            $slug = trim($slug, '-');
            $new_photo = $slug . '-' . substr(uniqid(), -6) . '.jpg';
            rename(root("photos/$new_photo_raw"), root("photos/$new_photo"));

            $photo = $new_photo;
            $_SESSION['edit_photo'] = $photo;
        }

        $stm = $pdo->prepare("UPDATE product
                               SET name = ?, category_id = ?, price = ?, stock_qty = ?, description = ?, photo = ?
                               WHERE id = ?");
        $stm->execute([$name, $category_id, $price, $stock_qty, $description, $photo, $id]);

        unset($_SESSION['edit_photo']);
        temp('info', "Product '$name' updated successfully.");
        redirect('/product/admin-draft.php');
    }
}

$_title = 'Edit Product';
require '../_head.php';
?>

<h1>Edit Product</h1>

<form method="post" enctype="multipart/form-data" novalidate>
    <table class="form-table">
        <tr>
            <td>Photo</td>
            <td>
                <label class="upload" tabindex="0">
                    <img src="<?= $photo ? '/photos/' . h($photo) : '/images/placeholder.png' ?>"
                         data-src="<?= $photo ? '/photos/' . h($photo) : '/images/placeholder.png' ?>">
                    <?= html_file('photo', 'image/*', 'hidden') ?>
                </label>
                <?= err('photo') ?>
                <p class="hint">Leave empty to keep the current photo.</p>
            </td>
        </tr>
        <tr>
            <td>Name</td>
            <td><?= html_text('name') ?> <?= err('name') ?></td>
        </tr>
        <tr>
            <td>Category</td>
            <td><?= html_select('category_id', $categories) ?> <?= err('category_id') ?></td>
        </tr>
        <tr>
            <td>Price</td>
            <td>
                <div style="display:flex; align-items:center; gap:6px;">
                    <span>RM</span>
                    <?= html_number('price', 0.01, '', 0.01) ?>
                </div>
                <?= err('price') ?>
            </td>
        </tr>
        <tr>
            <td>Stock Qty</td>
            <td><?= html_number('stock_qty', 0) ?> <?= err('stock_qty') ?></td>
        </tr>
        <tr>
            <td>Description</td>
            <td><?= html_textarea('description') ?></td>
        </tr>
        <tr>
            <td></td>
            <td>
                <button>Update</button>
                <a href="/product/admin-draft.php">Cancel</a>
            </td>
        </tr>
    </table>
</form>

<script>
// ---- Live (client-side) validation
(function () {
    function setErr(id, msg) {
        var el = document.getElementById('err_' + id);
        if (el) el.textContent = msg;
    }

    var name = document.getElementById('name');
    if (name) {
        name.addEventListener('blur', function () {
            setErr('name', this.value.trim() === '' ? 'Name is required' : '');
        });
    }

    var category = document.getElementById('category_id');
    if (category) {
        category.addEventListener('change', function () {
            setErr('category_id', this.value === '' ? 'Category is required' : '');
        });
    }

    var price = document.getElementById('price');
    if (price) {
        price.addEventListener('input', function () {
            var v = this.value;
            if (v === '') {
                setErr('price', 'Price is required');
            } else if (!/^\d+(\.\d{1,2})?$/.test(v)) {
                setErr('price', 'Enter a valid price, e.g. 12.50');
            } else if (parseFloat(v) <= 0) {
                setErr('price', 'Price must be greater than RM0.00');
            } else {
                setErr('price', '');
            }
        });
    }

    var stock = document.getElementById('stock_qty');
    if (stock) {
        stock.addEventListener('input', function () {
            var v = this.value;
            if (v === '') {
                setErr('stock_qty', 'Stock quantity is required');
            } else if (!/^\d+$/.test(v)) {
                setErr('stock_qty', 'Stock quantity must be a whole number, 0 or more');
            } else {
                setErr('stock_qty', '');
            }
        });
    }
})();
</script>

<?php require '../_foot.php'; ?>