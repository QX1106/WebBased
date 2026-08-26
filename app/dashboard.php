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
                 AND order_status = 'Completed' THEN total_amount ELSE 0 END) AS this_month_revenue
    FROM orders")->fetch();

// 10 matches product/list.php's own LOW_STOCK_THRESHOLD
$product_stats = $pdo->query("SELECT
        COUNT(*) AS total,
        SUM(stock_qty <= 10) AS low_stock,
        SUM(stock_qty = 0) AS out_of_stock
    FROM product")->fetch();

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

<h2>Products</h2>
<div class="stats">
    <a class="stat" href="/product/list.php"><b><?= $product_stats->total ?></b><span>Total Products</span></a>
    <a class="stat" href="/product/list.php?low_stock=1"><b><?= $product_stats->low_stock ?></b><span>Low Stock</span></a>
    <a class="stat" href="/product/list.php?low_stock=1"><b><?= $product_stats->out_of_stock ?></b><span>Out of Stock</span></a>
</div>

<?php require '_foot.php'; ?>