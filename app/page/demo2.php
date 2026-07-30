<?php
require '../_base.php';
// ----------------------------------------------------------------------------

// Associative array
$states = [
    'PJY' => 'Putrajaya',
    'KTN' => 'Kelantan',
    'PRK' => 'Perak',
    'NSN' => 'Negeri Sembilan',
    'PLS' => 'Perlis',
    'PNG' => 'Pulau Pinang',
    'TRG' => 'Terengganu',
    'LBN' => 'Labuan',
    'SBH' => 'Sabah',
    'PHG' => 'Pahang',
    'SRW' => 'Sarawak',
    'MLK' => 'Melaka',
    'KDH' => 'Kedah',
    'SEL' => 'Selangor',
    'JHR' => 'Johor',
];

// TODO
$states['KUL'] = 'Kuala Lumpur';
asort($states);
// ----------------------------------------------------------------------------
$_title = 'Page | Demo 2 | Ordered List';
include '../_head.php';
?>

<!-- TODO -->
$states['KUL'] = 'Kuala Lumpur';
asort($states);


<p><?= count($states) ?> state(s)</p>

<ol>
    <?php
    foreach ($states as $k => $v) {
        echo "<li>$k - $v</li>";
    }
    ?>
</ol>

<?php
include '../_foot.php';