<?php
require '../_base.php';
// ----------------------------------------------------------------------------

// Indexed array
$animals = [
    'Dog 🐶',
    'Cat 🐱',
    'Mouse 🐭',
    'Hamster 🐹',
    'Rabbit 🐰',
    'Fox 🦊',
    'Bear 🐻',
    'Panda 🐼',
    'Koala 🐨',
    'Tiger 🐯',
    'Lion 🦁',
    'Cow 🐮',
    'Pig 🐷',
    'Frog 🐸',
    'Monkey 🐵',
    'Wolf 🐺',
    'Boar 🐗',
    'Raccon 🦝',
    'Horse 🐴',
];

// Unicorn 🦄
// TODO
$animals[] = 'Unicorn 🦄';
sort($animals);

// ----------------------------------------------------------------------------
$_title = 'Page | Demo 1 | Unordered List';
include '../_head.php';
?>
<!-- TODO -->
<p><?= count($animals) ?> animal(s)</p>

<ul>
    <?php
    foreach ($animals as $k => $v) {
        echo "<li>$k => $v</li>";
    }
    ?>
</ul>


<?php
include '../_foot.php';
