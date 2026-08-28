<?php require '_base.php'; ?>
<?php auth('Admin', 'Super Admin'); ?>
<?php

auto_complete_shipped_orders();

// Revenue trend 
$range = get('range', '6');
if (!in_array($range, ['3', '6', '12', 'weekly'], true)) $range = '6';

$chart_points = [];

if ($range === 'weekly') {
    $period_count = 8;
    for ($i = $period_count - 1; $i >= 0; $i--) {
        $end = new DateTime('today');
        $end->modify('-' . ($i * 7) . ' days');
        $start = (clone $end)->modify('-6 days');

        $stm = $pdo->prepare("SELECT SUM(total_amount) FROM orders
                               WHERE order_status = 'Completed' AND DATE(order_date) BETWEEN ? AND ?");
        $stm->execute([$start->format('Y-m-d'), $end->format('Y-m-d')]);
        $revenue = (float) ($stm->fetchColumn() ?? 0);

        $chart_points[] = ['label' => $start->format('d/m') . '-' . $end->format('d/m'), 'revenue' => $revenue];
    }
} else {
    $months_count = (int) $range;
    $months = [];
    for ($i = $months_count - 1; $i >= 0; $i--) {
        $months[date('Y-m', strtotime("-$i months"))] = 0.0;
    }

    $stm = $pdo->query("SELECT DATE_FORMAT(order_date, '%Y-%m') AS ym, SUM(total_amount) AS revenue
                         FROM orders
                         WHERE order_status = 'Completed'
                         GROUP BY ym");
    foreach ($stm as $row) {
        if (array_key_exists($row->ym, $months)) {
            $months[$row->ym] = (float) $row->revenue;
        }
    }

    foreach ($months as $ym => $revenue) {
        $chart_points[] = ['label' => date('M Y', strtotime("$ym-01")), 'revenue' => $revenue];
    }
}

// Top Selling Products
$top_products = $pdo->query("SELECT p.name, SUM(oi.quantity) AS total_qty, SUM(oi.quantity * oi.unit_price) AS total_revenue
                              FROM order_item oi
                              JOIN orders o ON oi.order_id = o.order_id
                              JOIN product p ON oi.product_id = p.id
                              WHERE o.order_status = 'Completed'
                              GROUP BY oi.product_id, p.name
                              ORDER BY total_qty DESC
                              LIMIT 5")->fetchAll();

$_title = 'Report';
require '_head.php';
?>

<h1>Report</h1>

<h2 id="revenue-trend">Revenue Trend</h2>

<p class="status-filter">
    <?php foreach (['3' => '3 Months', '6' => '6 Months', '12' => '12 Months', 'weekly' => 'Weekly'] as $val => $label): ?>
        <a href="?range=<?= $val ?>#revenue-trend" class="<?= $range === (string) $val ? 'active' : '' ?>"><?= h($label) ?></a>
    <?php endforeach; ?>
</p>

<div class="chart-wrap">
    <canvas id="revenue-chart" height="90"></canvas>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('revenue-chart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($chart_points, 'label')) ?>,
        datasets: [{
            label: 'Revenue (RM)',
            data: <?= json_encode(array_map(fn($p) => round($p['revenue'], 2), $chart_points)) ?>,
            backgroundColor: '#c98a5e',
            borderRadius: 0,
            maxBarThickness: 60
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: (ctx) => 'RM ' + ctx.parsed.y.toLocaleString(undefined, {minimumFractionDigits: 2})
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

<h2>Top Selling Products</h2>
<?php if ($top_products): ?>
    <table class="table">
        <tr><th>Rank</th><th>Product</th><th>Units Sold</th><th>Revenue</th></tr>
        <?php foreach ($top_products as $i => $p): ?>
            <tr>
                <td>#<?= $i + 1 ?></td>
                <td><?= h($p->name) ?></td>
                <td><?= (int) $p->total_qty ?></td>
                <td>RM <?= number_format($p->total_revenue, 2) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p>No completed orders yet.</p>
<?php endif; ?>

<?php require '_foot.php'; ?>
