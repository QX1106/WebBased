<?php
// DEV-ONLY helper — lets you test admin/member pages before teammate 1
// finishes the real login.php. NOT part of the assignment deliverables.
// Delete this file before final submission.
require '_base.php';

$username = $_GET['u'] ?? null;

if ($username) {
    $stm = $pdo->prepare("SELECT * FROM member WHERE username = ?");
    $stm->execute([$username]);
    $u = $stm->fetch();
    if ($u) {
        $_SESSION['user'] = $u;
    }
}
?>
<?php require '_head.php'; ?>

<h1>Dev Login (temporary)</h1>

<?php if ($_user): ?>
    <p>Logged in as <b><?= h($_user->username) ?></b> (<?= h($_user->role) ?>)</p>
<?php else: ?>
    <p>Not logged in.</p>
<?php endif; ?>

<p>Switch account:</p>
<ul>
    <?php
    $stm = $pdo->query("SELECT username, role FROM member ORDER BY role, username");
    foreach ($stm as $m):
    ?>
        <li><a href="?u=<?= urlencode($m->username) ?>"><?= h($m->username) ?> (<?= h($m->role) ?>)</a></li>
    <?php endforeach; ?>
</ul>

<p><a href="/">Home</a> · <a href="/member/list.php">Member List</a> · <a href="/order/list.php">Order List</a></p>

<?php require '_foot.php'; ?>
