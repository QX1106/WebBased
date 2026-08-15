<?php require '../_base.php'; ?>
<?php // auth('Admin'); // TODO: re-enable once JW login page  is ready ?>
<?php

// ---- Check a YouTube link actually exists (not just correctly formatted) ---
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
        redirect('/product/admin-draft.php');
    }

    $name        = $product->name;
    $category_id = $product->category_id;
    $price       = $product->price;
    $stock_qty   = $product->stock_qty;
    $description = $product->description;
    $video_url   = $product->video_url;

    // ---- Practical 6: keep photo filename in SESSION -----------------------
   
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

    // Video URL is optional; if given, it must actually be a YouTube link
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
                               SET name = ?, category_id = ?, price = ?, stock_qty = ?, description = ?, photo = ?, video_url = ?
                               WHERE id = ?");
        $stm->execute([$name, $category_id, $price, $stock_qty, $description, $photo, $video_url ?: null, $id]);

        // Append any newly added gallery photos (existing ones are untouched)
        $order = count($gallery_photos);
        foreach ($gallery_files as $gf) {
            if (strpos($gf->type, 'image/') !== 0 || $gf->size > 1 * 1024 * 1024) {
                continue;
            }
            $gphoto = save_photo($gf, 'photos', 400, 300);
            $gslug_base = strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($name)));
            $gslug_base = trim($gslug_base, '-');
            $gslug = $gslug_base . '-gallery-' . substr(uniqid(), -6) . '.jpg';
            rename(root("photos/$gphoto"), root("photos/$gslug"));

            $gstm = $pdo->prepare("INSERT INTO product_photo (product_id, photo, sort_order) VALUES (?, ?, ?)");
            $gstm->execute([$id, $gslug, $order++]);
        }

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
                <label class="upload" id="upload-zone" tabindex="0">
                    <img src="<?= $photo ? '/photos/' . h($photo) : '/images/placeholder.png' ?>"
                         data-src="<?= $photo ? '/photos/' . h($photo) : '/images/placeholder.png' ?>"
                         id="upload-preview">
                    <?= html_file('photo', 'image/*', 'hidden') ?>
                </label>
                <p class="hint">Click to browse, or drag &amp; drop an image here. Leave empty to keep the current photo.</p>
                <?= err('photo') ?>
            </td>
        </tr>
        <tr>
            <td>Additional Photos</td>
            <td>
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
                <p class="hint">Add more gallery photos (optional, max 1MB each).</p>
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

    // ---- Drag-and-drop photo upload -----------------------------------
    var zone = document.getElementById('upload-zone');
    if (zone) {
        var fileInput = zone.querySelector('input[type=file]');
        var preview = document.getElementById('upload-preview');

        ['dragenter', 'dragover'].forEach(function (evt) {
            zone.addEventListener(evt, function (e) {
                e.preventDefault();
                e.stopPropagation();
                zone.classList.add('drag-over');
            });
        });
        ['dragleave', 'drop'].forEach(function (evt) {
            zone.addEventListener(evt, function (e) {
                e.preventDefault();
                e.stopPropagation();
                zone.classList.remove('drag-over');
            });
        });
        zone.addEventListener('drop', function (e) {
            var files = e.dataTransfer.files;
            if (files && files.length) {
                fileInput.files = files;
                if (preview && files[0].type.indexOf('image/') === 0) {
                    preview.src = URL.createObjectURL(files[0]);
                }
                fileInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    }
})();
</script>

<style>
    #upload-zone.drag-over { outline: 2px dashed #4a90d9; outline-offset: 2px; }
</style>

<?php require '../_foot.php'; ?>