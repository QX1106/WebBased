<?php require '../_base.php'; ?>
<?php // auth('Admin'); // TODO: re-enable once JW login page is ready ?>
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
    redirect('/product/admin-draft.php');
}

$_title = 'Product Detail (Admin Draft)';
require '../_head.php';
?>

<div class="admin-draft-notice" style="background:#fff3cd; border:1px solid #ffe08a; padding:8px 12px; margin-bottom:12px;">
    <strong>Admin Draft</strong> — temporary page for testing before login/auth is wired up.
</div>

<h1>Product Detail</h1>

<?php
function youtube_embed_url($url) {
    if ($url && preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/))([a-zA-Z0-9_-]{11})/', $url, $m)) {
        return 'https://www.youtube.com/embed/' . $m[1];
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
?>

<table class="form-table">
    <tr>
        <td>Photo</td>
        <td>
            <?php if ($all_photos): ?>
            <div id="slider" style="max-width:320px;">
                <img id="slider-img" src="/photos/<?= h($all_photos[0]) ?>" width="320" height="240" style="display:block; border:1px solid #ddd;">
                <?php if (count($all_photos) > 1): ?>
                <div style="display:flex; justify-content:space-between; margin-top:6px;">
                    <button type="button" id="slider-prev">&larr; Prev</button>
                    <span id="slider-count">1 / <?= count($all_photos) ?></span>
                    <button type="button" id="slider-next">Next &rarr;</button>
                </div>
                <div style="display:flex; gap:4px; margin-top:6px; flex-wrap:wrap;">
                    <?php foreach ($all_photos as $i => $ph): ?>
                    <img src="/photos/<?= h($ph) ?>" width="50" height="38"
                         class="slider-thumb" data-index="<?= $i ?>"
                         style="cursor:pointer; border:1px solid #ccc;">
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php else: ?>
                <span class="no-photo">No Photo</span>
            <?php endif; ?>
        </td>
    </tr>
    <tr><td>Name</td><td><?= h($product->name) ?></td></tr>
    <tr><td>Category</td><td><?= h($product->category_name) ?></td></tr>
    <tr><td>Price</td><td>RM <?= number_format($product->price, 2) ?></td></tr>
    <tr><td>Stock Qty</td><td><?= $product->stock_qty ?></td></tr>
    <tr><td>Description</td><td><?= nl2br(h($product->description)) ?></td></tr>
    <?php if ($embed_url): ?>
    <tr>
        <td>Video</td>
        <td>
            <iframe width="320" height="180" src="<?= h($embed_url) ?>"
                    frameborder="0" allowfullscreen></iframe>
        </td>
    </tr>
    <?php endif; ?>
</table>

<p>
    <a href="/product/update.php?id=<?= $product->id ?>">Edit</a> |
    <a href="/product/delete.php?id=<?= $product->id ?>" onclick="return confirm('Delete this product?')">Delete</a> |
    <a href="/product/admin-draft.php">Back to List</a>
</p>

<?php if (count($all_photos) > 1): ?>
<script>
(function () {
    var photos = <?= json_encode(array_map(fn($p) => "/photos/$p", $all_photos)) ?>;
    var idx = 0;
    var img = document.getElementById('slider-img');
    var count = document.getElementById('slider-count');

    function show(i) {
        idx = (i + photos.length) % photos.length;
        img.src = photos[idx];
        count.textContent = (idx + 1) + ' / ' + photos.length;
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