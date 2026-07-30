<?php
require '../_base.php';
// ----------------------------------------------------------------------------

$btn = get('btn'); // demo5.php?btn=x
if ($btn) {
    $output = "GET - You click on Button $btn";
}

// ----------------------------------------------------------------------------
$_title = 'Page | Demo 5 | GET Parameters';
include '../_head.php';
?>

<style>
    form {
        display: inline-block;
    }
</style>

<a href="?btn=1">1</a> |
<a href="?btn=2">2</a> |

<form>
    <button name="btn" value="3">3</button>
    <button name="btn" value="4">4</button>
</form>

<button data-get="?btn=5">5</button>
<button data-get="?btn=6">6</button>

<p><?= $output ?? '' ?></p>

<?php
include '../_foot.php';