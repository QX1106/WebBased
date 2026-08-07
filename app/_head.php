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

<div id="flash"><?= h(temp('info')) ?></div>

<header>
    <nav>
        <a href="/">Stationary Online Store</a>
        <a href="/product/list.php">Products</a>

        <?php if ($_user): ?>
            <?php if ($_user->role == 'Admin'): ?>
                <a href="/member/list.php">Members</a>
                <a href="/order/list.php">Orders (Admin)</a>
            <?php endif; ?>
            <?php if ($_user->role == 'Member'): ?>
                <a href="/cart/index.php">Cart</a>
                <a href="/order/history.php">My Orders</a>
            <?php endif; ?>
            <span><?= h($_user->username) ?> (<?= h($_user->role) ?>)</span>
            <a href="/user/logout.php">Logout</a>
        <?php else: ?>
            <a href="/user/login.php">Login</a>
            <a href="/member/register.php">Register</a>
        <?php endif; ?>
    </nav>
</header>

<main>
