<?php require '../_base.php'; ?>
<?php auth('Admin', 'Super Admin'); ?>
<?php

// LOW_STOCK_THRESHOLD is defined in _base.php — shared with the sidebar alert badge.

$fields = [
    'Name'     => 'p.name',
    'Category' => 'c.name',
    'Price'    => 'p.price',
    'Stock'    => 'p.stock_qty',
];

$sort = get('sort', 'p.name');
in_array($sort, $fields) || $sort = 'p.name';

$dir = get('dir', 'asc');
in_array($dir, ['asc', 'desc']) || $dir = 'asc';

$name = get('name', '');
$category_id = get('category_id', '');
$low_stock_only = get('low_stock', '') == '1';
$show_inactive = get('show_inactive', '') == '1';

$view = get('view', 'table');
in_array($view, ['table', 'photo']) || $view = 'table';

$categories = $pdo->query("SELECT id, name FROM category ORDER BY name")
                   ->fetchAll(PDO::FETCH_KEY_PAIR);

$page = get('page', 1);

$low_stock_count = low_stock_count();

$sql = "SELECT p.*, c.name AS category_name
        FROM product p
        JOIN category c ON c.id = p.category_id
        WHERE p.name LIKE ?
          AND (p.category_id = ? OR ?)
          AND (? OR p.stock_qty <= " . LOW_STOCK_THRESHOLD . ")
          AND (? OR p.status = 'Active')
        ORDER BY $sort $dir";
$params = ["%$name%", $category_id, $category_id == '', !$low_stock_only, $show_inactive];

$p = new SimplePager($pdo, $sql, $params, 10, $page);
$arr = $p->result;

$qs = '&name=' . urlencode($name) . '&category_id=' . urlencode($category_id) . '&low_stock=' . ($low_stock_only ? '1' : '')
    . '&show_inactive=' . ($show_inactive ? '1' : '') . '&view=' . $view;

// Base query string (filters + sort, no page) used to build the two View links below.
$view_base_qs = 'name=' . urlencode($name) . '&category_id=' . urlencode($category_id)
              . '&low_stock=' . ($low_stock_only ? '1' : '') . '&show_inactive=' . ($show_inactive ? '1' : '')
              . '&sort=' . urlencode($sort) . '&dir=' . urlencode($dir);

$_title = 'Product Maintenance';
require '../_head.php';
?>

<h1>Product Listing</h1>

<?php if ($low_stock_count > 0): ?>
<p style="background:#fdecea; border:1px solid #f5c2c0; padding:8px 12px; margin-bottom:12px;">
    <strong>⚠ Low Stock Alert:</strong>
    <?= $low_stock_count ?> product(s) at or below <?= LOW_STOCK_THRESHOLD ?> units.
    <a href="/product/list.php?low_stock=1">View them</a>
</p>
<?php endif; ?>

<form method="get" class="filter-form">
    <input type="hidden" name="view" value="<?= h($view) ?>">
    <?= html_search('name', "placeholder='Search product name'") ?>
    <?= html_select('category_id', $categories, 'All Categories') ?>
    <label><input type="checkbox" name="low_stock" value="1" <?= $low_stock_only ? 'checked' : '' ?>> Low stock only</label>
    <label><input type="checkbox" name="show_inactive" value="1" <?= $show_inactive ? 'checked' : '' ?>> Show inactive</label>
    <button>Search</button>
    <a href="/product/list.php" class="btn-outline">Reset</a>
</form>

<div class="toolbar">
    <a href="/product/insert.php" class="btn-accent">+ Add New Product</a>
    <a href="/product/export-csv.php?name=<?= urlencode($name) ?>&category_id=<?= urlencode($category_id) ?>" class="btn-accent">Export CSV</a>
    <a href="/product/batch-insert.php" class="btn-accent">Batch Insert (CSV)</a>

    <span class="toolbar-spacer"></span>

    <div class="view-control">
        <button type="button" class="view-btn" id="view-btn">
            <?php if ($view === 'photo'): ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="8" height="8" rx="1"/><rect x="13" y="3" width="8" height="8" rx="1"/><rect x="3" y="13" width="8" height="8" rx="1"/><rect x="13" y="13" width="8" height="8" rx="1"/></svg>
                <span>Photo View</span>
            <?php else: ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="16" rx="1"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                <span>Table View</span>
            <?php endif; ?>
            <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="view-menu" id="view-menu">
            <a class="view-option <?= $view === 'table' ? 'active' : '' ?>" href="?<?= $view_base_qs ?>&view=table">
                <span class="dot"></span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="16" rx="1"/><line x1="3" y1="10" x2="21" y2="10"/><line x1="9" y1="10" x2="9" y2="20"/></svg>
                Table View
            </a>
            <a class="view-option <?= $view === 'photo' ? 'active' : '' ?>" href="?<?= $view_base_qs ?>&view=photo">
                <span class="dot"></span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="8" height="8" rx="1"/><rect x="13" y="3" width="8" height="8" rx="1"/><rect x="3" y="13" width="8" height="8" rx="1"/><rect x="13" y="13" width="8" height="8" rx="1"/></svg>
                Photo View
            </a>
        </div>
    </div>
</div>

<p>
    <?= $p->count ?> of <?= $p->item_count ?> record(s) |
    Page <?= $p->page ?> of <?= $p->page_count ?>
</p>

<?php if ($view === 'photo'): ?>

<div class="photo-grid">
    <?php foreach ($arr as $row): ?>
    <?php $is_low = $row->stock_qty <= LOW_STOCK_THRESHOLD; ?>
    <?php $is_inactive = $row->status === 'Inactive'; ?>
    <div class="photo-card <?= $is_low ? 'low' : '' ?> <?= $is_inactive ? 'inactive' : '' ?>">
        <div class="photo-card-thumb">
            <?php if ($row->photo): ?>
                <img src="/photos/<?= h($row->photo) ?>" alt="">
            <?php else: ?>
                <span class="no-photo">No Photo</span>
            <?php endif; ?>
        </div>
        <div class="photo-card-body">
            <div class="photo-card-name">
                <a href="/product/view.php?id=<?= $row->id ?>"><?= h($row->name) ?></a>
                <?php if ($is_inactive): ?><span class="status-badge-inactive">Inactive</span><?php endif; ?>
            </div>
            <div class="photo-card-cat"><?= h($row->category_name) ?></div>
            <div class="photo-card-meta">
                <span class="photo-card-price">RM <?= number_format($row->price, 2) ?></span>
                <span class="photo-card-stock <?= $is_low ? 'low' : '' ?>">
                    <?= $row->stock_qty ?><?= $is_low ? ' ⚠' : '' ?>
                </span>
            </div>
            <div class="photo-card-actions">
                <a href="/product/update.php?id=<?= $row->id ?>">Edit</a> |
                <?php if ($is_inactive): ?>
                    <a href="/product/restore.php?id=<?= $row->id ?>" onclick="return confirm('Restore this product so it shows up again?')">Restore</a>
                <?php else: ?>
                    <a href="/product/delete.php?id=<?= $row->id ?>" onclick="return confirm('Delete this product? It will be hidden but can be restored later.')">Delete</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach ?>

    <?php if (!$arr): ?>
    <p>No products found.</p>
    <?php endif ?>
</div>

<?php else: ?>

<table class="table">
    <tr>
        <th style="text-align:center;"><input type="checkbox" id="select-all"></th>
        <th>Photo</th>
        <?= table_headers($fields, $sort, $dir, "$qs&page=$page") ?>
        <th style="white-space:nowrap;">Actions</th>
    </tr>

    <?php foreach ($arr as $row): ?>
    <?php $is_low = $row->stock_qty <= LOW_STOCK_THRESHOLD; ?>
    <?php $is_inactive = $row->status === 'Inactive'; ?>
    <tr style="<?= $is_inactive ? 'opacity:0.55;' : ($is_low ? 'background:#fdecea;' : '') ?>"
        data-name="<?= h($row->name) ?>" data-price="<?= $row->price ?>" data-cost="<?= $row->cost_price ?>">
        <td style="text-align:center;"><input type="checkbox" name="ids[]" value="<?= $row->id ?>" class="row-check"></td>
        <td>
            <?php if ($row->photo): ?>
                <img src="/photos/<?= h($row->photo) ?>" width="50" height="50">
            <?php else: ?>
                <span class="no-photo">No Photo</span>
            <?php endif; ?>
        </td>
        <td>
            <a href="/product/view.php?id=<?= $row->id ?>"><?= h($row->name) ?></a>
            <?php if ($is_inactive): ?><span class="status-badge-inactive">Inactive</span><?php endif; ?>
        </td>
        <td><?= h($row->category_name) ?></td>
        <td style="white-space:nowrap;">RM <?= number_format($row->price, 2) ?></td>
        <td style="white-space:nowrap;">
            <?= $row->stock_qty ?>
            <?php if ($is_low): ?>
                <span style="color:#c0392b; font-weight:bold;" title="Low stock">⚠</span>
            <?php endif; ?>
        </td>
        <td style="white-space:nowrap;">
            <a href="/product/update.php?id=<?= $row->id ?>">Edit</a> |
            <?php if ($is_inactive): ?>
                <a href="/product/restore.php?id=<?= $row->id ?>" onclick="return confirm('Restore this product so it shows up again?')">Restore</a>
            <?php else: ?>
                <a href="/product/delete.php?id=<?= $row->id ?>" onclick="return confirm('Delete this product? It will be hidden but can be restored later.')">Delete</a>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach ?>

    <?php if (!$arr): ?>
    <tr><td colspan="7">No products found.</td></tr>
    <?php endif ?>
</table>

<?php if ($arr): ?>
<div class="toolbar">
    <button type="button" id="delete-selected-btn">Delete Selected</button>
    <button type="button" class="btn-accent" id="open-batch-update">Batch Update Price</button>
    <span class="selection-note" id="selection-note"></span>
</div>

<section class="batch-panel" id="batch-panel">
    <h2>Batch Update Price</h2>
    <p class="sub" id="batch-panel-sub">Adjusting price for 0 selected products</p>

    <div class="batch-controls">
        <div class="control-group">
            <span class="label">Direction</span>
            <div class="seg" id="seg-direction">
                <button type="button" class="active" data-val="increase">Increase</button>
                <button type="button" data-val="decrease">Decrease</button>
            </div>
        </div>
        <div class="control-group">
            <span class="label">Unit</span>
            <div class="seg" id="seg-unit">
                <button type="button" class="active" data-val="percent">Percentage (%)</button>
                <button type="button" data-val="fixed">Fixed (RM)</button>
            </div>
        </div>
        <div class="control-group">
            <span class="label">Amount</span>
            <div class="value-input">
                <input type="number" id="batch-amount" value="10" min="0" step="0.01">
                <span id="unit-suffix">%</span>
            </div>
        </div>
    </div>

    <table class="preview">
        <tr><th>Product</th><th>Current Price</th><th></th><th>New Price</th></tr>
        <tbody id="batch-preview-body"></tbody>
    </table>

    <div class="batch-actions">
        <button type="button" id="apply-batch-update">Apply to <span id="apply-count">0</span> Products</button>
        <a class="cancel-link" id="cancel-batch-update">Cancel</a>
    </div>
</section>
<?php endif; ?>

<!-- Hidden forms, submitted via JS — keeps <table> out of a <form> wrapper -->
<form method="post" action="/product/batch-delete.php" id="batch-form" style="display:none;"></form>
<form method="post" action="/product/batch-update.php" id="batch-update-form" style="display:none;"></form>

<?php endif; ?>

<script>
    var viewBtn = document.getElementById('view-btn');
    var viewMenu = document.getElementById('view-menu');
    if (viewBtn && viewMenu) {
        viewBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            viewMenu.classList.toggle('open');
        });
        document.addEventListener('click', function (e) {
            if (!viewMenu.contains(e.target) && e.target !== viewBtn) {
                viewMenu.classList.remove('open');
            }
        });
    }

    var selectAll = document.getElementById('select-all');
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            document.querySelectorAll('.row-check').forEach(function (cb) {
                cb.checked = this.checked;
            }.bind(this));
        });
    }

    var deleteBtn = document.getElementById('delete-selected-btn');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function () {
            var checked = document.querySelectorAll('.row-check:checked');
            if (checked.length === 0) {
                alert('Select at least one product first.');
                return;
            }
            if (!confirm('Delete all selected products? They will be hidden but can be restored later.')) {
                return;
            }
            var form = document.getElementById('batch-form');
            checked.forEach(function (cb) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = cb.value;
                form.appendChild(input);
            });
            form.submit();
        });
    }

    // ---- Batch Update Price -------------------------------------------
    var batchDirection = 'increase';
    var batchUnit = 'percent';

    function checkedRows() {
        return Array.from(document.querySelectorAll('.row-check:checked')).map(function (cb) {
            return cb.closest('tr');
        });
    }

    function updateSelectionNote() {
        var note = document.getElementById('selection-note');
        if (!note) return;
        var n = document.querySelectorAll('.row-check:checked').length;
        note.textContent = n ? n + ' product' + (n === 1 ? '' : 's') + ' selected' : '';
    }
    document.querySelectorAll('.row-check').forEach(function (cb) {
        cb.addEventListener('change', function () {
            updateSelectionNote();
            renderBatchPreview();
        });
    });
    updateSelectionNote();

    function setSeg(id, initial, onChange) {
        var el = document.getElementById(id);
        if (!el) return;
        el.querySelectorAll('button').forEach(function (b) {
            b.addEventListener('click', function () {
                el.querySelectorAll('button').forEach(function (o) { o.classList.remove('active'); });
                b.classList.add('active');
                onChange(b.dataset.val);
            });
        });
    }
    setSeg('seg-direction', batchDirection, function (v) { batchDirection = v; renderBatchPreview(); });
    setSeg('seg-unit', batchUnit, function (v) {
        batchUnit = v;
        var suffix = document.getElementById('unit-suffix');
        if (suffix) suffix.textContent = v === 'percent' ? '%' : 'RM';
        renderBatchPreview();
    });

    var batchAmount = document.getElementById('batch-amount');
    if (batchAmount) batchAmount.addEventListener('input', renderBatchPreview);

    function renderBatchPreview() {
        var body = document.getElementById('batch-preview-body');
        var subEl = document.getElementById('batch-panel-sub');
        var countEl = document.getElementById('apply-count');
        if (!body) return;

        var amount = parseFloat(batchAmount.value) || 0;
        var rows = checkedRows();

        if (subEl) subEl.textContent = 'Adjusting price for ' + rows.length + ' selected product' + (rows.length === 1 ? '' : 's');
        if (countEl) countEl.textContent = rows.length;

        body.innerHTML = '';
        rows.forEach(function (tr) {
            var name = tr.dataset.name;
            var price = parseFloat(tr.dataset.price);
            var cost = parseFloat(tr.dataset.cost);
            var delta = batchUnit === 'percent' ? price * (amount / 100) : amount;
            var newPrice = batchDirection === 'increase' ? price + delta : price - delta;
            var warn = newPrice < cost || newPrice <= 0;

            var row = document.createElement('tr');
            row.innerHTML =
                '<td></td><td>RM ' + price.toFixed(2) + '</td><td class="arrow">&#8594;</td>' +
                '<td class="new-price' + (warn ? ' warn' : '') + '">RM ' + newPrice.toFixed(2) +
                (warn ? '<div class="warn-note">' + (newPrice <= 0 ? 'Would be zero or negative — skipped' : 'Below cost price (RM ' + cost.toFixed(2) + ')') + '</div>' : '') +
                '</td>';
            row.cells[0].textContent = name;
            body.appendChild(row);
        });
    }

    var openBatchBtn = document.getElementById('open-batch-update');
    var batchPanel = document.getElementById('batch-panel');
    if (openBatchBtn && batchPanel) {
        openBatchBtn.addEventListener('click', function () {
            if (document.querySelectorAll('.row-check:checked').length === 0) {
                alert('Select at least one product first.');
                return;
            }
            batchPanel.classList.add('open');
            renderBatchPreview();
        });
    }

    var cancelBatchBtn = document.getElementById('cancel-batch-update');
    if (cancelBatchBtn && batchPanel) {
        cancelBatchBtn.addEventListener('click', function () {
            batchPanel.classList.remove('open');
        });
    }

    var applyBatchBtn = document.getElementById('apply-batch-update');
    if (applyBatchBtn) {
        applyBatchBtn.addEventListener('click', function () {
            var checked = document.querySelectorAll('.row-check:checked');
            if (checked.length === 0) {
                alert('Select at least one product first.');
                return;
            }
            var amount = parseFloat(batchAmount.value);
            if (isNaN(amount) || amount < 0) {
                alert('Enter a valid amount, 0 or more.');
                return;
            }
            if (!confirm('Apply this price change to ' + checked.length + ' selected product(s)?')) {
                return;
            }
            var form = document.getElementById('batch-update-form');
            checked.forEach(function (cb) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = cb.value;
                form.appendChild(input);
            });
            ['direction', 'unit', 'amount'].forEach(function (key) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = key === 'direction' ? batchDirection : (key === 'unit' ? batchUnit : amount);
                form.appendChild(input);
            });
            form.submit();
        });
    }
</script>

<br>

<?= $p->links("&sort=$sort&dir=$dir$qs") ?>

<?php require '../_foot.php'; ?>