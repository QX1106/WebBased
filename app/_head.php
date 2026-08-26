<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($_title ?? 'Untitled') ?></title>
    <link rel="stylesheet" href="/css/app.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="/js/app.js" defer></script>
    <!-- TEMP: dropdown styling for the Products nav item. Move into css/app.css once finalized. -->
    <style>
        nav a.has-dropdown { position: relative; }
        nav .dropdown {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background: #fff;
            border: 1px solid #ddd;
            min-width: 180px;
            z-index: 10;
            list-style: none;
            margin: 0;
            padding: 4px 0;
        }
        nav a.has-dropdown:hover .dropdown { display: block; }
        nav .dropdown li { padding: 0; }
        nav .dropdown li a { display: block; padding: 8px 12px; white-space: nowrap; }
        nav .dropdown li a:hover { background: #f5f5f5; }
    </style>
</head>
<body>
<script>if (localStorage.getItem('sidebar-hidden') === '1') document.body.classList.add('sidebar-hidden');</script>

<div id="flash"><?= h(temp('info')) ?></div>

<?php
// Shared category list for the Products dropdown (used in both nav variants below)
$_nav_categories = $pdo->query("SELECT id, name FROM category ORDER BY name")->fetchAll();
?>

<?php if ($_user && in_array($_user->role, ['Admin', 'Super Admin'])): ?>
    <?php $_path = $_SERVER['REQUEST_URI']; ?>
    <?php $_is_super = $_user->role == 'Super Admin'; ?>
    <?php $_pending_cancel_requests = $pdo->query("SELECT COUNT(*) FROM cancel_request WHERE status = 'Pending'")->fetchColumn(); ?>
    <div class="admin-topbar">
        <button type="button" id="sidebar-toggle" class="sidebar-toggle" aria-label="Toggle sidebar">&#9776;</button>
    </div>
    <div class="admin-layout">
        <aside class="sidebar">
            <a href="/" class="brand">Stationary Online Store</a>
            <nav>
                <?php if ($_is_super): ?>
                    <a href="/superadmin/dashboard.php" class="<?= $_path == '/superadmin/dashboard.php' ? 'active' : '' ?>">Dashboard</a>
                    <a href="/superadmin/admins/list.php" class="<?= str_starts_with($_path, '/superadmin/admins') ? 'active' : '' ?>">Manage Admin Account</a>
                <?php else: ?>
                    <a href="/dashboard.php" class="<?= $_path == '/dashboard.php' ? 'active' : '' ?>">Dashboard</a>
                <?php endif; ?>
                <a href="/report.php" class="<?= $_path == '/report.php' ? 'active' : '' ?>">Report</a>
                <a href="/member/list.php" class="<?= str_starts_with($_path, '/member') ? 'active' : '' ?>">Members</a>
                <a href="/order/list.php" class="<?= str_starts_with($_path, '/order') && !str_starts_with($_path, '/order/cancel-request') ? 'active' : '' ?>">Orders</a>
                <a href="/order/cancel-requests.php" class="<?= str_starts_with($_path, '/order/cancel-request') ? 'active' : '' ?>">Cancellation Requests<?= $_pending_cancel_requests ? " ($_pending_cancel_requests)" : '' ?></a>
                <a href="/product/list.php" class="<?= str_starts_with($_path, '/product') ? 'active' : '' ?>">Products</a>
                <a href="/voucher/list.php" class="<?= str_starts_with($_path, '/voucher') ? 'active' : '' ?>">Vouchers</a>
                <a href="<?= $_is_super ? '/superadmin/profile.php' : '/admin/profile.php' ?>" class="<?= $_is_super ? (in_array($_path, ['/superadmin/profile.php', '/superadmin/edit.php', '/superadmin/password.php']) ? 'active' : '') : (in_array($_path, ['/admin/profile.php', '/admin/edit.php', '/admin/password.php']) ? 'active' : '') ?>">My Profile</a>
            </nav>
            <div class="sidebar-foot">
                <a href="<?= $_is_super ? '/superadmin/profile.php' : '/admin/profile.php' ?>" class="user-chip" style="text-decoration:none;">
                    <?= user_avatar($_user, 32) ?>
                    <span><?= h($_user->username) ?> (<?= h($_user->role) ?>)</span>
                </a>
                <a href="/user/logout.php">Logout</a>
            </div>
        </aside>
        <main>
<?php elseif (str_starts_with($_path ?? $_SERVER['REQUEST_URI'], '/admin/')): ?>
    <!-- Admin login/forgot-password pages: shown before the admin is signed
         in, so the full admin sidebar (and its auth-gated links) don't apply
         yet — just a minimal header instead. -->

<header>
    <nav>
        <a href="/">Stationary Online Store</a>
    </nav>
</header>

<main>
<?php else: ?>

<header>
    <nav>
        <a href="/">Stationary Online Store</a>

        <?php if ($_user): ?>
            <?php if ($_user->role == 'Member'): ?>
                <a href="/cart/index.php">Cart</a>
                <a href="/cart/order.php">Orders</a>
            <?php endif; ?>
            <span class="user-chip"><a href="/member/profile.php" style="display:flex;align-items:center;gap:8px;text-decoration:none;"><?= user_avatar($_user, 28) ?> <?= h($_user->username) ?> (<?= h($_user->role) ?>)</a></span>
            <a href="/user/logout.php">Logout</a>
        <?php else: ?>
            <a href="/user/login.php">Login</a>
            <a href="/member/register.php">Register</a>
        <?php endif; ?>
    </nav>
</header>

<main>
<?php endif; ?>
