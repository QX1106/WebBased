<?php require '../_base.php'; ?>
<?php // auth('Admin'); // TODO: re-enable once login page (teammate's part) is ready ?>
<?php

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
    } elseif (!is_unique('product', 'name', $name)) {
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

    // ---- Practical 6: file type + size validation --------------------------
    $f = get_file('photo');
    if (!$f) {
        $_err['photo'] = 'Product photo is required';
    } elseif (strpos($f->type, 'image/') !== 0) {
        $_err['photo'] = 'File must be an image';
    } elseif ($f->size > 1 * 1024 * 1024) {
        $_err['photo'] = 'Image must not exceed 1MB';
    }

    if (!$_err) {
        // Practical 6: crop/resize to 400x300, save to 'photos' folder
        $photo = save_photo($f, 'photos', 400, 300);

        // Rename to something readable: <product-name-slug>-<short-unique>.jpg
        // The random suffix still guarantees no filename collisions —
        // we're just making it easier to recognize at a glance.
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($name)));
        $slug = trim($slug, '-');
        $new_photo = $slug . '-' . substr(uniqid(), -6) . '.jpg';
        rename(root("photos/$photo"), root("photos/$new_photo"));
        $photo = $new_photo;

        $stm = $pdo->prepare("INSERT INTO product (name, category_id, price, stock_qty, description, photo)
                               VALUES (?, ?, ?, ?, ?, ?)");
        $stm->execute([$name, $category_id, $price, $stock_qty, $description, $photo]);

        temp('info', "Product '$name' added successfully.");
        redirect('/product/admin-draft.php');
    }
}

$_title = 'Add Product';
require '../_head.php';
?>

<h1>Add Product</h1>

<form method="post" enctype="multipart/form-data" novalidate>
    <table class="form-table">
        <tr>
            <td>Photo</td>
            <td>
                <label class="upload" tabindex="0">
                    <img src="/images/placeholder.png" data-src="/images/placeholder.png">
                    <?= html_file('photo', 'image/*', 'hidden') ?>
                </label>
                <?= err('photo') ?>
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
                <button>Save</button>
                <a href="/product/admin-draft.php">Cancel</a>
            </td>
        </tr>
    </table>
</form>

<script>
// ---- Live (client-side) validation, mirrors the server-side rules above.
// This is purely UX feedback — the PHP checks above still run on submit
// and are the real source of truth (never trust client-side alone).
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