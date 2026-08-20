<?php require '../_base.php'; ?>
<?php auth('Admin'); ?>
<?php

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$categories = $pdo->query("SELECT id, name FROM category")->fetchAll();
$category_lookup = [];
foreach ($categories as $c) {
    $category_lookup[strtolower(trim($c->name))] = $c->id;
}

$results = null; // ['inserted' => N, 'skipped' => [...]]

if (is_post()) {
    $lines = [];

    $f = get_file('csv_file');
    if ($f) {
        $lines = file($f->tmp_name, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    } else {
        $pasted = post('csv_text', '');
        if (trim($pasted) !== '') {
            $lines = preg_split('/\r\n|\r|\n/', trim($pasted));
        }
    }

    if (!$lines) {
        temp('info', 'No CSV file or text provided.');
    } else {
        $inserted = 0;
        $skipped = [];

        foreach ($lines as $i => $line) {
            if (trim($line) === '') continue;
            $row = str_getcsv($line);
            $line_no = $i + 1;

            // Skip the header row if present (first cell literally says "Name")
            if ($i === 0 && strtolower(trim($row[0] ?? '')) === 'name') {
                continue;
            }

            $name        = trim($row[0] ?? '');
            $category    = trim($row[1] ?? '');
            $price       = trim($row[2] ?? '');
            $stock_qty   = trim($row[3] ?? '');
            $description = trim($row[4] ?? '');
            $video_url   = trim($row[5] ?? '');

            if ($name === '') {
                $skipped[] = "Line $line_no: name is required";
                continue;
            }
            if (!is_unique('product', 'name', $name)) {
                $skipped[] = "Line $line_no ($name): a product with this name already exists";
                continue;
            }

            $category_id = $category_lookup[strtolower($category)] ?? null;
            if (!$category_id) {
                $skipped[] = "Line $line_no ($name): unknown category '$category'";
                continue;
            }

            if (!is_money($price) || (float)$price <= 0) {
                $skipped[] = "Line $line_no ($name): invalid price '$price'";
                continue;
            }

            if (!ctype_digit($stock_qty)) {
                $skipped[] = "Line $line_no ($name): invalid stock quantity '$stock_qty'";
                continue;
            }

            // Video URL is optional — if malformed, just drop it rather than
            // discarding an otherwise-valid product row.
            if ($video_url !== '' && !preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/))[a-zA-Z0-9_-]{11}/', $video_url)) {
                $video_url = '';
            }

            $stm = $pdo->prepare("INSERT INTO product (name, category_id, price, stock_qty, description, photo, video_url)
                                   VALUES (?, ?, ?, ?, ?, NULL, ?)");
            $stm->execute([$name, $category_id, $price, $stock_qty, $description ?: null, $video_url ?: null]);
            $inserted++;
        }

        $results = ['inserted' => $inserted, 'skipped' => $skipped];
    }
}

$_title = 'Batch Insert Products';
require '../_head.php';
?>

<h1>Batch Insert Products</h1>

<p class="hint">
    Upload a .csv file, or paste CSV text directly below. Expected columns (with a header row):<br>
    <code>Name,Category,Price,Stock Qty,Description,Video URL</code><br>
    Category must match an existing category name (not case-sensitive). Photos aren't supported in bulk —
    add them individually via Edit afterward.
</p>

<?php if ($results): ?>
<div style="background:#eafaf1; border:1px solid #a3d9b1; padding:8px 12px; margin-bottom:12px;">
    <strong><?= $results['inserted'] ?> product(s) inserted successfully.</strong>
</div>
<?php if ($results['skipped']): ?>
<div style="background:#fdecea; border:1px solid #f5c2c0; padding:8px 12px; margin-bottom:12px;">
    <strong><?= count($results['skipped']) ?> row(s) skipped:</strong>
    <ul>
        <?php foreach ($results['skipped'] as $msg): ?>
        <li><?= h($msg) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>
<p><a href="/product/list.php">Back to Product Listing</a></p>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
    <label for="csv_file" style="display:block; font-weight:bold; color:inherit; text-decoration:none; margin-bottom:4px;">Upload CSV File</label>
    <input type="file" id="csv_file" name="csv_file" accept=".csv,text/csv"><br><br>

    <label for="csv_text" style="display:block; font-weight:bold; color:inherit; text-decoration:none; margin-bottom:4px;">Or paste CSV text here</label>
    <textarea id="csv_text" name="csv_text" rows="8" style="width:100%;" placeholder="Name,Category,Price,Stock Qty,Description,Video URL
Blue Pen,Pen &amp; Pencil,2.50,100,Smooth blue ink,"></textarea><br><br>

    <button type="submit">Import</button>
    <a href="/product/list.php" class="btn-outline">Cancel</a>
</form>

<?php require '../_foot.php'; ?>