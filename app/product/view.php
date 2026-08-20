<?php require '../_base.php'; ?>
<?php auth('Admin'); ?>
<?php

$id = get('id');

$stm = $pdo->prepare("SELECT p.*, c.name AS category_name
                       FROM product p
                       JOIN category c ON c.id = p.category_id
                       WHERE p.id = ?");
$stm->execute([$id]);
$product = $stm->fetch();

if (!$product) {
    temp('info', 'Product not found.');
    redirect('/product/list.php');
}

function youtube_embed_url($url) {
    if ($url && preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/))([a-zA-Z0-9_-]{11})/', $url, $m)) {
        return 'https://www.youtube-nocookie.com/embed/' . $m[1];
    }
    return null;
}
$embed_url = youtube_embed_url($product->video_url);

// ---- Gather all photos: cover photo first, then gallery photos ------------
$gallery_stm = $pdo->prepare("SELECT photo FROM product_photo WHERE product_id = ? ORDER BY sort_order");
$gallery_stm->execute([$id]);
$all_photos = [];
if ($product->photo) {
    $all_photos[] = $product->photo;
}
foreach ($gallery_stm->fetchAll() as $gp) {
    $all_photos[] = $gp->photo;
}

$slides = [];
if ($embed_url) {
    $slides[] = ['type' => 'video', 'src' => $embed_url];
}
foreach ($all_photos as $ph) {
    $slides[] = ['type' => 'image', 'src' => "/photos/$ph"];
}

$_title = 'Product Detail';
require '../_head.php';
?>

<h1>Product Detail</h1>

<?php if ($slides): ?>
<div id="slider" style="max-width:400px;">
    <div id="slides" style="border:1px solid #ddd;">
        <?php foreach ($slides as $i => $slide): ?>
        <div class="slide" data-index="<?= $i ?>" style="<?= $i === 0 ? '' : 'display:none;' ?>">
            <?php if ($slide['type'] === 'video'): ?>
                <iframe width="400" height="300" src="<?= h($slide['src']) ?>" frameborder="0" allowfullscreen></iframe>
            <?php else: ?>
                <img src="<?= h($slide['src']) ?>" width="400" height="300">
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if (count($slides) > 1): ?>
    <div style="display:flex; gap:4px; margin-top:6px; flex-wrap:wrap;">
        <?php foreach ($slides as $i => $slide): ?>
            <?php if ($slide['type'] === 'video'): ?>
            <div class="slider-thumb" data-index="<?= $i ?>"
                 style="cursor:pointer; width:50px; height:38px; background:#000; color:#fff;
                        display:flex; align-items:center; justify-content:center; font-size:16px;">&#9654;</div>
            <?php else: ?>
            <img src="<?= h($slide['src']) ?>" width="50" height="38"
                 class="slider-thumb" data-index="<?= $i ?>" style="cursor:pointer; border:1px solid #ccc;">
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:6px;">
        <button type="button" id="slider-prev">&larr; Prev</button>
        <span id="slider-count">1 / <?= count($slides) ?></span>
        <button type="button" id="slider-next">Next &rarr;</button>
    </div>
    <?php endif; ?>
</div>
<?php else: ?>
<span class="no-photo">No Photo</span>
<?php endif; ?>

<table class="form-table" style="width:400px;">
    <tr><td style="width:130px;">Name</td><td><?= h($product->name) ?></td></tr>
    <tr><td>Category</td><td><?= h($product->category_name) ?></td></tr>
    <tr><td>Price</td><td>RM <?= number_format($product->price, 2) ?></td></tr>
    <tr><td>Stock Qty</td><td><?= $product->stock_qty ?></td></tr>
    <tr><td>Description</td><td><?= nl2br(h($product->description)) ?></td></tr>
</table>

<p style="width : 400px;">
    <a href="/product/update.php?id=<?= $product->id ?>">Edit</a> |
    <a href="/product/delete.php?id=<?= $product->id ?>" onclick="return confirm('Delete this product?')">Delete</a> |
    <a href="/product/list.php">Back to List</a>
</p>

<?php if (count($slides) > 1): ?>
<script>
(function () {
    var idx = 0;
    var slideCount = <?= count($slides) ?>;
    var slideEls = document.querySelectorAll('.slide');
    var count = document.getElementById('slider-count');

    function show(i) {
        idx = (i + slideCount) % slideCount;
        slideEls.forEach(function (el) {
            el.style.display = (parseInt(el.dataset.index, 10) === idx) ? '' : 'none';
        });
        count.textContent = (idx + 1) + ' / ' + slideCount;
    }

    document.getElementById('slider-prev').addEventListener('click', function () { show(idx - 1); });
    document.getElementById('slider-next').addEventListener('click', function () { show(idx + 1); });
    document.querySelectorAll('.slider-thumb').forEach(function (thumb) {
        thumb.addEventListener('click', function () { show(parseInt(this.dataset.index, 10)); });
    });
})();
</script>
<?php endif; ?>

<?php require '../_foot.php'; ?>