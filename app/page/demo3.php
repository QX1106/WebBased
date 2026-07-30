<?php
require '../_base.php';
// ----------------------------------------------------------------------------

$genders = [
    'F' => 'Female',
    'M' => 'Male',
];

$countries = [
    'AU' => 'Australia',
    'CA' => 'Canada',
    'GB' => 'Great Britain',
    'NZ' => 'New Zealand',
    'US' => 'United States',
];

// TODO
$users = [];
$f = fopen('users.csv', 'r');
while ($row = fgetcsv($f)) {
$users[] = (object)[
        'id' => (int)$row[0],
        'name' => $row[1],
        'gender' => $row[2],
        'age' => (int)$row[3],
        'country' => $row[4],
        'photo' => $row[5]
    ];
}
fclose($f);

$users = array_filter($users, fn ($u) => str_starts_with($u->name, 'A'));

usort($users, fn ($a, $b) => $a->age <=> $b->age); //asc

// ----------------------------------------------------------------------------
$_title = 'Page | Demo 3 | Table';
include '../_head.php';
?>

<!-- TODO -->
<p><?= count($users) ?> user(s)</p>

<table class="table">
    <tr>
        <th>Id</th>
        <th>Name</th>
        <th>Gender</th>
        <th>Age</th>
        <th>Country</th>
    </tr>
    <?php foreach ($users as $u): ?>
<tr>
        <td><?= $u->id ?></td>
        <td><?= $u->name ?></td>
        <td><?= $genders[$u->gender] ?></td>
        <td><?= $u->age ?></td>
        <td>
            <?= $countries[$u->country] ?>
            <img src="<?= $u->photo ?>" class="popup">
        </td>
    </tr>
    <?php endforeach ?>
</table>

<?php
include '../_foot.php';