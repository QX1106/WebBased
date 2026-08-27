<?php require '../_base.php'; ?>
<?php auth('Admin', 'Super Admin'); ?>
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


$cost_history_stm = $pdo->prepare("SELECT cost_price, effective_from FROM product_cost_history
                                    WHERE product_id = ? ORDER BY effective_from ASC, id ASC");
$cost_history_stm->execute([$id]);
$cost_history = $cost_history_stm->fetchAll();

function cost_price_on($cost_history, $date, $fallback) {
    $applicable = $fallback;
    foreach ($cost_history as $h) {
        if ($h->effective_from > $date) {
            break;
        }
        $applicable = $h->cost_price;
    }
    return (float) $applicable;
}

$sales_stm = $pdo->prepare("SELECT oi.quantity, oi.unit_price, o.order_date
                             FROM order_item oi
                             JOIN orders o ON o.order_id = oi.order_id
                             WHERE oi.product_id = ? AND o.order_status = 'Completed'
                             ORDER BY o.order_date");
$sales_stm->execute([$id]);
$sales_rows = $sales_stm->fetchAll();
$has_sales = count($sales_rows) > 0;

$months = [];
for ($i = 5; $i >= 0; $i--) {
    $months[date('Y-m', strtotime("-$i months"))] = ['sell' => 0.0, 'cost' => 0.0];
}

foreach ($sales_rows as $row) {
    $order_date = date('Y-m-d', strtotime($row->order_date));
    $ym = substr($order_date, 0, 7);
    if (!array_key_exists($ym, $months)) {
        continue;
    }
    $unit_cost = cost_price_on($cost_history, $order_date, $product->cost_price);
    $months[$ym]['sell'] += $row->quantity * $row->unit_price;
    $months[$ym]['cost'] += $row->quantity * $unit_cost;
}

$chart_labels = [];
$chart_sell = [];
$chart_cost = [];
foreach ($months as $ym => $vals) {
    $chart_labels[] = date('M', strtotime($ym . '-01'));
    $chart_sell[] = round($vals['sell'], 2);
    $chart_cost[] = round($vals['cost'], 2);
}
$total_margin = array_sum($chart_sell) - array_sum($chart_cost);

$_title = 'Product Detail';
require '../_head.php';
?>

<h1>Product Detail</h1>

<div class="product-detail">
<?php if ($slides): ?>
<div id="slider">
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

<div class="detail-info">
    <table class="form-table">
        <tr><td style="width:130px;">Name</td><td><?= h($product->name) ?></td></tr>
        <tr><td>Category</td><td><?= h($product->category_name) ?></td></tr>
        <tr><td>Cost Price (per unit)</td><td>RM <?= number_format($product->cost_price, 2) ?></td></tr>
        <tr><td>Price</td><td>RM <?= number_format($product->price, 2) ?></td></tr>
        <tr><td>Stock Qty</td><td>
            <?= $product->stock_qty ?>
            <?php if ($product->stock_qty <= 10): ?>
                <span style="color:#c0392b; font-weight:bold;">⚠ Low Stock</span>
            <?php endif; ?>
        </td></tr>
        <tr><td>Description</td><td><?= nl2br(h($product->description)) ?></td></tr>
    </table>

    <p>
        <a href="/product/update.php?id=<?= $product->id ?>">Edit</a> |
        <a href="/product/delete.php?id=<?= $product->id ?>" onclick="return confirm('Delete this product?')">Delete</a> |
        <a href="/product/list.php">Back to List</a>
    </p>
</div>
</div>

<section class="sales-panel">
    <div class="sales-panel-head">
        <div>
            <h2>Sales Performance</h2>
            <p class="hint">Monthly sell price vs. cost — the shaded gap is margin</p>
        </div>
        <?php if ($has_sales): ?>
        <div class="margin-total">
            <span>Margin (6&nbsp;mo)</span>
            <b>RM <?= number_format($total_margin, 2) ?></b>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($has_sales): ?>
    <div class="chart-wrap">
        <canvas id="sales-chart" height="90"></canvas>
    </div>
    <?php else: ?>
    <p class="hint">No sales recorded yet for this product.</p>
    <?php endif; ?>
</section>

<?php if ($has_sales): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('sales-chart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [
            {
                label: 'Cost (RM)',
                data: <?= json_encode($chart_cost) ?>,
                borderColor: '#5b7d8c',
                backgroundColor: '#5b7d8c',
                borderWidth: 2,
                pointRadius: 3,
                tension: 0,
                fill: false
            },
            {
                label: 'Sell Price (RM)',
                data: <?= json_encode($chart_sell) ?>,
                borderColor: '#c98a5e',
                backgroundColor: 'rgba(122, 155, 113, 0.18)',
                borderWidth: 2,
                pointRadius: 3,
                pointBackgroundColor: '#c98a5e',
                tension: 0,
                fill: '-1'
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' },
            tooltip: {
                callbacks: {
                    label: (ctx) => ctx.dataset.label + ': RM ' + ctx.parsed.y.toLocaleString(undefined, {minimumFractionDigits: 2})
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { callback: (v) => 'RM ' + v.toLocaleString() }
            }
        }
    }
});
</script>
<?php endif; ?>

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