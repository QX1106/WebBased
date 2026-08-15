<?php require '_base.php'; ?>
<?php auth('Admin'); ?>
<?php

auto_complete_shipped_orders();

$member_stats = $pdo->query("SELECT
        COUNT(*) AS total,
        SUM(status = 'Active') AS active,
        SUM(status = 'Blocked') AS blocked,
        SUM(MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())) AS new_this_month,
        SUM(last_active IS NOT NULL AND last_active >= NOW() - INTERVAL 10 MINUTE) AS online_now
    FROM member")->fetch();

$order_stats = $pdo->query("SELECT
        COUNT(*) AS total,
        SUM(order_status = 'Pending') AS pending,
        SUM(MONTH(order_date) = MONTH(CURDATE()) AND YEAR(order_date) = YEAR(CURDATE())) AS this_month_count,
        SUM(CASE WHEN MONTH(order_date) = MONTH(CURDATE()) AND YEAR(order_date) = YEAR(CURDATE())
                 AND order_status != 'Cancelled' THEN total_amount ELSE 0 END) AS this_month_revenue
    FROM orders")->fetch();

// Revenue trend (Additional Module): selectable range, cancelled orders excluded
$range = (int) get('range', 6);
if (!in_array($range, [3, 6, 12], true)) $range = 6;

$months = [];
for ($i = $range - 1; $i >= 0; $i--) {
    $months[date('Y-m', strtotime("-$i months"))] = 0.0;
}

$stm = $pdo->query("SELECT DATE_FORMAT(order_date, '%Y-%m') AS ym, SUM(total_amount) AS revenue
                     FROM orders
                     WHERE order_status != 'Cancelled'
                     GROUP BY ym");
foreach ($stm as $row) {
    if (array_key_exists($row->ym, $months)) {
        $months[$row->ym] = (float)$row->revenue;
    }
}

$chart_max = max(max($months), 1);

?>
<?php require '_head.php'; ?>

<h1>Dashboard</h1>

<h2>Members</h2>
<div class="stats">
    <a class="stat" href="/member/list.php"><b><?= $member_stats->total ?></b><span>Total Members</span></a>
    <a class="stat" href="/member/list.php"><b><?= $member_stats->active ?></b><span>Active</span></a>
    <a class="stat" href="/member/list.php"><b><?= $member_stats->blocked ?></b><span>Blocked</span></a>
    <a class="stat" href="/member/list.php"><b><?= $member_stats->new_this_month ?></b><span>New This Month</span></a>
    <a class="stat" href="/member/list.php"><b><?= $member_stats->online_now ?></b><span>Online Now</span></a>
</div>

<h2>Orders</h2>
<div class="stats">
    <a class="stat" href="/order/list.php"><b><?= $order_stats->total ?></b><span>Total Orders</span></a>
    <a class="stat" href="/order/list.php?status=Pending"><b><?= $order_stats->pending ?></b><span>Pending</span></a>
    <a class="stat" href="/order/list.php"><b><?= $order_stats->this_month_count ?></b><span>Orders This Month</span></a>
    <a class="stat" href="/order/list.php"><b>RM <?= number_format($order_stats->this_month_revenue, 2) ?></b><span>Revenue This Month</span></a>
</div>

<h2 id="revenue-trend">Revenue Trend</h2>

<p class="status-filter">
    <?php foreach ([3, 6, 12] as $r): ?>
        <a href="?range=<?= $r ?>#revenue-trend" class="<?= $range === $r ? 'active' : '' ?>"><?= $r ?> Months</a>
    <?php endforeach; ?>
</p>

<?php
    $bar_w = 60;
    $gap = 30;
    $chart_h = 140;
    $top_margin = 24; // room for the value label above the tallest bar
    $n = count($months);
    $svg_w = $n * ($bar_w + $gap) + $gap;
    $svg_h = $top_margin + $chart_h + 50;
    $i = 0;
?>
<svg class="chart" viewBox="0 0 <?= $svg_w ?> <?= $svg_h ?>" width="<?= $svg_w ?>" height="<?= $svg_h ?>">
    <?php foreach ($months as $ym => $revenue): ?>
        <?php
            $x = $gap + $i * ($bar_w + $gap);
            $h = $chart_max > 0 ? round(($revenue / $chart_max) * $chart_h) : 0;
            $y = $top_margin + $chart_h - $h;
            $label = date('M Y', strtotime("$ym-01"));
            $i++;
        ?>
        <text x="<?= $x + $bar_w / 2 ?>" y="<?= $y - 8 ?>" class="chart-value" text-anchor="middle">RM <?= number_format($revenue, 0) ?></text>
        <rect x="<?= $x ?>" y="<?= $y ?>" width="<?= $bar_w ?>" height="<?= $h ?>" class="chart-bar"></rect>
        <text x="<?= $x + $bar_w / 2 ?>" y="<?= $top_margin + $chart_h + 22 ?>" class="chart-label" text-anchor="middle"><?= h($label) ?></text>
    <?php endforeach; ?>
    <line x1="0" y1="<?= $top_margin + $chart_h ?>" x2="<?= $svg_w ?>" y2="<?= $top_margin + $chart_h ?>" class="chart-axis"></line>
</svg>

<?php require '_foot.php'; ?>
