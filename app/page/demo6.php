<?php
require '../_base.php';
// ----------------------------------------------------------------------------

if (is_post()) {
    $btn = req('btn');
    if ($btn) {
        $output = "POST - You click on Button $btn";
    }
}

// ----------------------------------------------------------------------------
$_title = 'Page | Demo 6 | POST Parameters';
include '../_head.php';
?>

<style>
    form {
        display: inline-block;
    }
</style>

<a href="" data-post="?btn=1">1</a> |
<a href="" data-post="?btn=2">2</a> |

<form method="post">
    <button name="btn" value="3">3</button>
    <button name="btn" value="4">4</button>
</form>

<button data-post="?btn=5">5</button>
<button data-post="?btn=6">6</button>

<p><?= $output ?? '' ?></p>

<?php
include '../_foot.php';