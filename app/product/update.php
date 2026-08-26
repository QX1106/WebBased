<?php require '../_base.php'; ?>
<?php auth('Admin', 'Super Admin'); ?>
<?php

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function youtube_video_exists($url) {
    $api = 'https://www.youtube.com/oembed?url=' . urlencode($url) . '&format=json';
    $context = stream_context_create(['http' => ['timeout' => 4, 'ignore_errors' => true]]);
    $result = @file_get_contents($api, false, $context);
    if ($result === false || !isset($http_response_header[0])) {
        return null;
    }
    return (bool) preg_match('/\s200\s/', $http_response_header[0]);
}

function get_files($key) {
    $out = [];
    if (!empty($_FILES[$key]) && is_array($_FILES[$key]['name'])) {
        foreach ($_FILES[$key]['name'] as $i => $filename) {
            if ($_FILES[$key]['error'][$i] === UPLOAD_ERR_OK) {
                $out[] = (object) [
                    'name'     => $filename,
                    'type'     => $_FILES[$key]['type'][$i],
                    'tmp_name' => $_FILES[$key]['tmp_name'][$i],
                    'error'    => $_FILES[$key]['error'][$i],
                    'size'     => $_FILES[$key]['size'][$i],
                ];
            }
        }
    }
    return $out;
}

$id = get('id');

if (is_get()) {
    $stm = $pdo->prepare("SELECT * FROM product WHERE id = ?");
    $stm->execute([$id]);
    $product = $stm->fetch();

    if (!$product) {
        temp('info', 'Product not found.');
        redirect('/product/list.php');
    }

    $name        = $product->name;
    $category_id = $product->category_id;
    $price       = $product->price;
    $stock_qty   = $product->stock_qty;
    $description = $product->description;
    $video_url   = $product->video_url;

    $_SESSION['edit_photo'] = $product->photo;
}

$photo = $_SESSION['edit_photo'] ?? null;

$gallery_photos = $pdo->prepare("SELECT * FROM product_photo WHERE product_id = ? ORDER BY sort_order");
$gallery_photos->execute([$id]);
$gallery_photos = $gallery_photos->fetchAll();

$categories = $pdo->query("SELECT id, name FROM category ORDER BY name")
                   ->fetchAll(PDO::FETCH_KEY_PAIR);

$_err = [];

if (is_post()) {
    $name        = post('name');
    $category_id = post('category_id');
    $price       = post('price');
    $stock_qty   = post('stock_qty');
    $description = post('description');
    $video_url   = post('video_url');

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

    if ($video_url != '') {
        if (!preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/))[a-zA-Z0-9_-]{11}/', $video_url)) {
            $_err['video_url'] = 'Enter a valid YouTube link, e.g. https://youtu.be/dQw4w9WgXcQ';
        } else {
            $exists = youtube_video_exists($video_url);
            if ($exists === false) {
                $_err['video_url'] = 'This YouTube video is unavailable, private, or was removed.';
            }
        }
    }

    $gallery_files = get_files('gallery');

    $f = get_file('photo');
    if ($f) {
        if (strpos($f->type, 'image/') !== 0) {
            $_err['photo'] = 'File must be an image';
        } elseif ($f->size > 3 * 1024 * 1024) {
            $_err['photo'] = 'Image must not exceed 3MB';
        }
    }

    if (!$_err) {
        if ($f) {
            if ($photo && file_exists(root("photos/$photo"))) {
                unlink(root("photos/$photo"));
            }
            $new_photo_raw = save_photo($f, 'photos', 800, 600);

            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($name)));
            $slug = trim($slug, '-');
            $new_photo = $slug . '-' . substr(uniqid(), -6) . '.jpg';
            rename(root("photos/$new_photo_raw"), root("photos/$new_photo"));

            $photo = $new_photo;
            $_SESSION['edit_photo'] = $photo;
        }

        $stm = $pdo->prepare("UPDATE product
                               SET name = ?, category_id = ?, price = ?, stock_qty = ?, description = ?, photo = ?, video_url = ?
                               WHERE id = ?");
        $stm->execute([$name, $category_id, $price, $stock_qty, $description, $photo, $video_url ?: null, $id]);

        $order = count($gallery_photos);
        $skipped = [];
        foreach ($gallery_files as $gf) {
            if (strpos($gf->type, 'image/') !== 0) {
                $skipped[] = $gf->name . ' (not an image)';
                continue;
            }
            if ($gf->size > 3 * 1024 * 1024) {
                $skipped[] = $gf->name . ' (over 3MB)';
                continue;
            }
            $gphoto = save_photo($gf, 'photos', 800, 600);
            $gslug_base = strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($name)));
            $gslug_base = trim($gslug_base, '-');
            $gslug = $gslug_base . '-gallery-' . substr(uniqid(), -6) . '.jpg';
            rename(root("photos/$gphoto"), root("photos/$gslug"));

            $gstm = $pdo->prepare("INSERT INTO product_photo (product_id, photo, sort_order) VALUES (?, ?, ?)");
            $gstm->execute([$id, $gslug, $order++]);
        }

        unset($_SESSION['edit_photo']);
        $msg = "Product '$name' updated successfully.";
        if ($skipped) {
            $msg .= ' Skipped: ' . implode(', ', $skipped) . '.';
        }
        temp('info', $msg);
        redirect('/product/list.php');
    }
}

$_title = 'Edit Product';
require '../_head.php';
?>

<h1>Edit Product</h1>

<form method="post" enctype="multipart/form-data" novalidate>
    <table class="form-table">
        <tr>
            <td style="vertical-align:top;">Photo</td>
            <td>
                <?= err('photo') ?>
                <div class="photo-drop" tabindex="0" role="button" aria-label="Upload product photo">
                    <img src="<?= $photo ? '/photos/' . h($photo) : '' ?>" <?= $photo ? '' : 'style="display:none"' ?>>
                    <div class="photo-drop-hint" <?= $photo ? 'style="display:none"' : '' ?>>Drag &amp; drop a photo here, or click to browse<br><small>Max 3MB</small></div>
                    <?= html_file('photo', 'image/*', "style='display:none'") ?>
                    <button type="button" class="photo-drop-clear">✕ Clear selection</button>
                </div>
                <p class="hint">Leave empty to keep the current photo.</p>

                <?php if ($photo): ?>
                <div style="display:grid; grid-template-columns:repeat(2, max-content); gap:6px; margin-top:6px;">
                    <button type="button" class="btn-outline" style="padding:4px 10px; font-size:0.85em;"
                            onclick="submitPhotoTransform('rotate_left')">⟲ Rotate Left</button>
                    <button type="button" class="btn-outline" style="padding:4px 10px; font-size:0.85em;"
                            onclick="submitPhotoTransform('rotate_right')">⟳ Rotate Right</button>
                    <button type="button" class="btn-outline" style="padding:4px 10px; font-size:0.85em;"
                            onclick="submitPhotoTransform('flip_horizontal')">⇋ Flip Horizontal</button>
                    <button type="button" class="btn-outline" style="padding:4px 10px; font-size:0.85em;"
                            onclick="submitPhotoTransform('flip_vertical')">⇅ Flip Vertical</button>
                </div>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td style="vertical-align:top; padding-top:16px;">Additional Photos</td>
            <td style="padding-top:16px;">
                <?php if ($gallery_photos): ?>
                <div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:8px;">
                    <?php foreach ($gallery_photos as $gp): ?>
                    <div style="text-align:center;">
                        <img src="/photos/<?= h($gp->photo) ?>" width="80" height="60"><br>
                        <a href="/product/photo-delete.php?id=<?= $gp->id ?>&product_id=<?= $id ?>"
                           onclick="return confirm('Remove this photo?')">Remove</a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <input type="file" id="gallery" name="gallery[]" accept="image/*" multiple>
                <p class="hint">Add more gallery photos (optional, max 3MB each).</p>
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
            <td>YouTube Video</td>
            <td>
                <?= html_text('video_url', "placeholder='https://youtu.be/... (optional)'") ?>
                <?= err('video_url') ?>
            </td>
        </tr>
        <tr>
            <td></td>
            <td>
                <button>Update</button>
                <a href="/product/list.php">Cancel</a>
            </td>
        </tr>
    </table>
</form>

<script>
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

    var videoUrl = document.getElementById('video_url');
    if (videoUrl) {
        videoUrl.addEventListener('blur', function () {
            var v = this.value.trim();
            if (v === '') {
                setErr('video_url', '');
                return;
            }
            var ok = /(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/))[a-zA-Z0-9_-]{11}/.test(v);
            setErr('video_url', ok ? '' : 'Enter a valid YouTube link, e.g. https://youtu.be/dQw4w9WgXcQ');
        });
    }
})();
</script>

<form method="post" action="/product/photo-transform.php" id="photo-transform-form" style="display:none;">
    <input type="hidden" name="id" value="<?= $id ?>">
    <input type="hidden" name="action" id="photo-transform-action" value="">
</form>

<script>
    function submitPhotoTransform(action) {
        document.getElementById('photo-transform-action').value = action;
        document.getElementById('photo-transform-form').submit();
    }
</script>

<?php require '../_foot.php'; ?>