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

$categories = $pdo->query("SELECT id, name FROM category ORDER BY name")
                   ->fetchAll(PDO::FETCH_KEY_PAIR);

$_err = [];

if (is_post()) {
    $name        = post('name');
    $category_id = post('category_id');
    $price       = post('price');
    $cost_price  = post('cost_price');
    $stock_qty   = post('stock_qty');
    $description = post('description');
    $video_url   = post('video_url');

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

    if ($cost_price == '') {
        $_err['cost_price'] = 'Cost price is required';
    } elseif (!is_money($cost_price)) {
        $_err['cost_price'] = 'Enter a valid cost price, e.g. 8.50';
    } elseif ((float)$cost_price < 0) {
        $_err['cost_price'] = 'Cost price cannot be negative';
    } elseif (!isset($_err['price']) && (float)$cost_price >= (float)$price) {
        $_err['cost_price'] = 'Cost price should be less than the selling price';
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

    // ---- Multiple gallery photos (optional, in addition to the cover photo)
    $gallery_files = get_files('gallery');

    // ---- Practical 6: file type + size validation --------------------------
    $f = get_file('photo');
    if (!$f) {
        $_err['photo'] = 'Product photo is required';
    } elseif (strpos($f->type, 'image/') !== 0) {
        $_err['photo'] = 'File must be an image';
    } elseif ($f->size > 3 * 1024 * 1024) {
        $_err['photo'] = 'Image must not exceed 3MB';
    }

    if (!$_err) {
        // Practical 6: crop/resize to 400x300, save to 'photos' folder
        $photo = save_photo($f, 'photos', 800, 600);

       
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($name)));
        $slug = trim($slug, '-');
        $new_photo = $slug . '-' . substr(uniqid(), -6) . '.jpg';
        rename(root("photos/$photo"), root("photos/$new_photo"));
        $photo = $new_photo;

        $stm = $pdo->prepare("INSERT INTO product (name, category_id, price, cost_price, stock_qty, description, photo, video_url)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stm->execute([$name, $category_id, $price, $cost_price, $stock_qty, $description, $photo, $video_url ?: null]);
        $product_id = $pdo->lastInsertId();

        $pdo->prepare("INSERT INTO product_cost_history (product_id, cost_price, effective_from) VALUES (?, ?, CURDATE())")
            ->execute([$product_id, $cost_price]);


        $order = 0;
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
            $gslug = $slug . '-gallery-' . substr(uniqid(), -6) . '.jpg';
            rename(root("photos/$gphoto"), root("photos/$gslug"));

            $gstm = $pdo->prepare("INSERT INTO product_photo (product_id, photo, sort_order) VALUES (?, ?, ?)");
            $gstm->execute([$product_id, $gslug, $order++]);
        }

        $msg = "Product '$name' added successfully.";
        if ($skipped) {
            $msg .= ' Skipped: ' . implode(', ', $skipped) . '.';
        }
        temp('info', $msg);
        redirect('/product/list.php');
    }
}

$_title = 'Add Product';
require '../_head.php';
?>

<h1>Add Product</h1>

<form method="post" enctype="multipart/form-data" novalidate>
    <table class="form-table">
        <tr>
            <td style="vertical-align:top;">Photo</td>
            <td>
                <?= err('photo') ?>
                <div class="photo-drop" tabindex="0" role="button" aria-label="Upload product photo">
                    <img src="" style="display:none">
                    <div class="photo-drop-hint">Drag &amp; drop a photo here, or click to browse<br><small>Max 3MB</small></div>
                    <?= html_file('photo', 'image/*', "style='display:none'") ?>
                    <button type="button" class="photo-drop-clear">✕ Clear selection</button>
                </div>
            </td>
        </tr>
        <tr>
            <td style="vertical-align:top; padding-top:16px;">Additional Photos</td>
            <td style="padding-top:16px;">
                <input type="file" id="gallery" name="gallery[]" accept="image/*" multiple>
                <p class="hint">Optional — pick several images for the product's photo gallery (max 3MB each).</p>
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
            <td>Cost Price (per unit)</td>
            <td>
                <div class="input-prefix">
                    <span>RM</span>
                    <?= html_number('cost_price', 0, '', 0.01) ?>
                </div>
                <?= err('cost_price') ?>
            </td>
        </tr>
        <tr>
            <td>Price</td>
            <td>
                <div class="input-prefix">
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
                <button>Save</button>
                <a href="/product/list.php">Cancel</a>
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

    var costPrice = document.getElementById('cost_price');
    var priceInput = document.getElementById('price');
    if (costPrice) {
        costPrice.addEventListener('input', function () {
            var v = this.value;
            if (v === '') {
                setErr('cost_price', 'Cost price is required');
            } else if (!/^\d+(\.\d{1,2})?$/.test(v)) {
                setErr('cost_price', 'Enter a valid cost price, e.g. 8.50');
            } else if (parseFloat(v) < 0) {
                setErr('cost_price', 'Cost price cannot be negative');
            } else if (priceInput.value !== '' && parseFloat(v) >= parseFloat(priceInput.value)) {
                setErr('cost_price', 'Cost price should be less than the selling price');
            } else {
                setErr('cost_price', '');
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

<?php require '../_foot.php'; ?>