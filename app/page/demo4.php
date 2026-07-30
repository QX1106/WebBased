<?php
require '../_base.php';
// ----------------------------------------------------------------------------

if (is_get()) {
    $output = 'GET request';
}

if (is_post()) {
    $output = 'POST request';
}

// ----------------------------------------------------------------------------
$_title = 'Page | Demo 4 | GET and POST Requests';
include '../_head.php';
?>

<style>
    form {
        display: inline-block;
    }
</style>

<form>
    <button>GET</button>
</form>

<form method="post">
    <button>POST</button>
</form>

<button data-get>GET</button>
<button data-post>POST</button>

<p><?= $output ?? '' ?></p>

<?php
include '../_foot.php';