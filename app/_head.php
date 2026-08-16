<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($_title ?? 'Untitled') ?></title>
    <link rel="stylesheet" href="/css/app.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="/js/app.js" defer></script>
</head>
<body>
<script>if (localStorage.getItem('sidebar-hidden') === '1') document.body.classList.add('sidebar-hidden');</script>

<div id="flash"><?= h(temp('info')) ?></div>

<?php if ($_user && $_user->role == 'Admin'): ?>
    <?php $_path = $_SERVER['REQUEST_URI']; ?>
    <div class="admin-topbar">
        <button type="button" id="sidebar-toggle" class="sidebar-toggle" aria-label="Toggle sidebar">&#9776;</button>
    </div>
    <div class="admin-layout">
        <aside class="sidebar">
            <a href="/" class="brand">Stationary Online Store</a>
            <nav>
                <a href="/dashboard.php" class="<?= $_path == '/dashboard.php' ? 'active' : '' ?>">Dashboard</a>
                <a href="/member/list.php" class="<?= str_starts_with($_path, '/member') ? 'active' : '' ?>">Members</a>
                <a href="/order/list.php" class="<?= str_starts_with($_path, '/order') ? 'active' : '' ?>">Orders</a>
                <a href="/product/list.php" class="<?= str_starts_with($_path, '/product') ? 'active' : '' ?>">Products</a>
                <!-- TEMP: remove once /product/list.php is built -->
                <a href="/product/admin-draft.php">Product (Admin Draft)</a>
            </nav>
            <div class="sidebar-foot">
                <div class="user-chip">
                    <?= user_avatar($_user, 32) ?>
                    <span><?= h($_user->username) ?> (<?= h($_user->role) ?>)</span>
                </div>
                <a href="/user/logout.php">Logout</a>
            </div>
        </aside>
        <main>
<?php else: ?>

<header>
    <nav>
        <a href="/">Stationary Online Store</a>
        <a href="/product/list.php">Products</a>

        <?php if ($_user): ?>
            <?php if ($_user->role == 'Member'): ?>
                <a href="/cart/index.php">Cart</a>
                <a href="/order/history.php">My Orders</a>
            <?php endif; ?>
            <span class="user-chip"><?= user_avatar($_user, 28) ?> <?= h($_user->username) ?> (<?= h($_user->role) ?>)</span>
            <a href="/user/logout.php">Logout</a>
        <?php else: ?>
            <a href="/user/login.php">Login</a>
            <a href="/member/register.php">Register</a>
        <?php endif; ?>

        <!-- TEMP: remove once /product/list.php is built -->
        <a href="/product/admin-draft.php">Product (Admin Draft)</a>
    </nav>
</header>

<main>
<?php endif; ?>