<?php

require_once __DIR__ . '/lib/PHPMailer.php';
require_once __DIR__ . '/lib/SMTP.php';
require_once __DIR__ . '/lib/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

date_default_timezone_set('Asia/Kuala_Lumpur');
session_start();

$_title = 'Stationary Online Store';

// =========================================================
// DATABASE (PDO)
// =========================================================
$pdo = new PDO('mysql:dbname=stationary_db', 'root', '', [
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
]);

// shipped if more than  7 days, auto-complete
const AUTO_COMPLETE_DAYS = 7;

function auto_complete_shipped_orders() {
    global $pdo;

    $stm = $pdo->prepare("SELECT o.order_id
                           FROM orders o
                           JOIN order_status_log l ON l.order_id = o.order_id AND l.status = 'Shipped'
                           WHERE o.order_status = 'Shipped'
                             AND l.changed_at <= NOW() - INTERVAL ? DAY");
    $stm->execute([AUTO_COMPLETE_DAYS]);
    $order_ids = $stm->fetchAll(PDO::FETCH_COLUMN);

    foreach ($order_ids as $order_id) {
        $pdo->prepare("UPDATE orders SET order_status = 'Completed' WHERE order_id = ?")
            ->execute([$order_id]);
        $pdo->prepare("INSERT INTO order_status_log (order_id, status) VALUES (?, 'Completed')")
            ->execute([$order_id]);
    }
}

// =========================================================
// PATH / URL HELPERS
// =========================================================
function root($path = '') {
    return __DIR__ . '/' . $path;
}

function base($path = '') {
    $protocol = (!empty($_SERVER['HTTPS'])) ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . '/' . $path;
}

// =========================================================
// REQUEST HELPERS
// =========================================================
function is_get() {
    return $_SERVER['REQUEST_METHOD'] == 'GET';
}

function is_post() {
    return $_SERVER['REQUEST_METHOD'] == 'POST';
}

// AJAX
function is_ajax() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

function trim_value($value) {
    if (is_array($value)) {
        return array_map('trim', $value);
    }
    return trim($value);
}

function get($key, $default = null) {
    return isset($_GET[$key]) ? trim_value($_GET[$key]) : $default;
}

function post($key, $default = null) {
    return isset($_POST[$key]) ? trim_value($_POST[$key]) : $default;
}

function req($key, $default = null) {
    return isset($_REQUEST[$key]) ? trim_value($_REQUEST[$key]) : $default;
}

// =========================================================
// REDIRECT / FLASH MESSAGE (TEMP DATA)
// =========================================================
function redirect($url = null) {
    if ($url === null) {
        $url = $_SERVER['REQUEST_URI'];
    }
    header("Location: $url");
    exit;
}

function temp($key, $value = null) {
    $key = 'temp_' . $key;
    if ($value !== null) {
        $_SESSION[$key] = $value;
        return;
    }
    $value = $_SESSION[$key] ?? null;
    unset($_SESSION[$key]);
    return $value;
}

// =========================================================
// SECURITY (LOGIN / LOGOUT / AUTH)
// =========================================================
$_user = $_SESSION['user'] ?? null;

// Online and last active
if ($_user) {
    $stm = $pdo->prepare("UPDATE member SET last_active = NOW() WHERE member_id = ?");
    $stm->execute([$_user->member_id]);
}

function login($user, $url = '/') {
    global $pdo;

    $_SESSION['user'] = $user;

    $stm = $pdo->prepare("INSERT INTO login_log (member_id, login_time) VALUES (?, NOW())");
    $stm->execute([$user->member_id]);

    redirect($url);
}

function logout($url = '/') {
    unset($_SESSION['user']);
    redirect($url);
}

function auth(...$roles) {
    global $_user;
    if ($_user) {
        if (!$roles || in_array($_user->role, $roles)) {
            return;
        }
    }
    redirect('/user/login.php');
}

// =========================================================
// EMAIL (PHPMailer + Gmail SMTP)
// =========================================================
// Fill in YOUR OWN Gmail address and App Password below (Google Account
// -> Security -> 2-Step Verification -> App Passwords). Never use your
// real Gmail login password here, and never commit real credentials to
// a public repo — if this project goes on GitHub, move these two lines
// into a separate untracked config file instead.
function get_mail() {
    $m = new PHPMailer(true);
    $m->isSMTP();
    $m->SMTPAuth = true;
    $m->Host = 'smtp.gmail.com';
    $m->Port = 587;
    $m->Username = '0602hehehe@gmail.com';       // <-- put your Gmail address here
    $m->Password = 'gvix wjyh bzjp hufo';  // <-- put your App Password here
    $m->CharSet = 'utf-8';
    $m->setFrom($m->Username, 'Stationary Online Store');
    return $m;
}

function send_email($to, $subject, $body, $attachments = []) {
    try {
        $mail = get_mail();
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        foreach ($attachments as $att) {
            $mail->addStringAttachment($att['content'], $att['name']);
        }
        $mail->send();
        return true;
    } catch (PHPMailerException $e) {
        error_log('send_email failed: ' . $e->getMessage());
        return false;
    }
}

// =========================================================
// HTML HELPERS
// =========================================================
function h($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES);
}

// CSV
function csv_safe($value) {
    $value = (string)($value ?? '');
    if (preg_match('/^[=+\-@]/', $value)) {
        return "'" . $value;
    }
    return $value;
}

// user avatar/initial letter
function user_avatar($u, $size = 32) {
    if ($u->photo) {
        return "<img src='/uploads/member/" . h($u->photo) . "' width='$size' height='$size' class='avatar'>";
    }
    $initial = h(strtoupper(substr($u->username, 0, 1)));
    return "<span class='avatar avatar-fallback' style='width:{$size}px;height:{$size}px;line-height:{$size}px;'>$initial</span>";
}

function html_text($key, $attr = '') {
    global $_err;
    $value = h($GLOBALS[$key] ?? '');
    $err_class = isset($_err[$key]) ? "class='err-input'" : '';
    return "<input type='text' id='$key' name='$key' value='$value' $err_class $attr>";
}

function html_password($key, $attr = '') {
    return "<input type='password' id='$key' name='$key' value='' $attr>";
}

function html_search($key, $attr = '') {
    $value = h($GLOBALS[$key] ?? '');
    return "<input type='search' id='$key' name='$key' value='$value' $attr>";
}

function html_number($key, $min = '', $max = '', $step = '', $attr = '') {
    $value = h($GLOBALS[$key] ?? '');
    return "<input type='number' id='$key' name='$key' value='$value' min='$min' max='$max' step='$step' $attr>";
}

function html_file($key, $accept = '', $attr = '') {
    return "<input type='file' id='$key' name='$key' accept='$accept' $attr>";
}

function html_date($key, $attr = '') {
    $value = h($GLOBALS[$key] ?? '');
    return "<input type='date' id='$key' name='$key' value='$value' $attr>";
}

function html_hidden($key, $value) {
    $value = h($value);
    return "<input type='hidden' id='$key' name='$key' value='$value'>";
}

function html_textarea($key, $attr = '') {
    $value = h($GLOBALS[$key] ?? '');
    return "<textarea id='$key' name='$key' $attr>$value</textarea>";
}

function html_checkbox($key, $label = '') {
    $checked = !empty($GLOBALS[$key]) ? 'checked' : '';
    return "<label><input type='checkbox' id='$key' name='$key' value='1' $checked> $label</label>";
}

function html_radios($key, $options, $inline = false) {
    $selected = $GLOBALS[$key] ?? null;
    $wrap = $inline ? 'span' : 'div';
    $html = "<$wrap>";
    foreach ($options as $value => $label) {
        $checked = ($selected == $value) ? 'checked' : '';
        $html .= "<label><input type='radio' id='{$key}_{$value}' name='$key' value='" . h($value) . "' $checked>" . h($label) . "</label>";
    }
    $html .= "</$wrap>";
    return $html;
}

function html_select($key, $options, $blank = '- Select One -', $attr = '') {
    $selected = $GLOBALS[$key] ?? null;
    $html = "<select id='$key' name='$key' $attr>";
    if ($blank !== null) {
        $html .= "<option value=''>" . h($blank) . "</option>";
    }
    foreach ($options as $value => $label) {
        $sel = ($selected == $value) ? 'selected' : '';
        $html .= "<option value='" . h($value) . "' $sel>" . h($label) . "</option>";
    }
    $html .= "</select>";
    return $html;
}

function err($key) {
    global $_err;
    if (isset($_err[$key])) {
        return "<span class='err' id='err_$key'>" . h($_err[$key]) . "</span>";
    }
    return "<span id='err_$key'></span>";
}

// =========================================================
// VALIDATION HELPERS
// =========================================================
function is_email($value) {
    return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
}

function is_money($value) {
    return preg_match('/^\d+(\.\d{1,2})?$/', $value) === 1;
}

function is_date($value) {
    $d = DateTime::createFromFormat('Y-m-d', $value);
    return $d && $d->format('Y-m-d') === $value;
}

function is_unique($table, $field, $value, $except_id = null, $id_field = null) {
    global $pdo;
    $sql = "SELECT COUNT(*) FROM `$table` WHERE `$field` = ?";
    $params = [$value];
    if ($except_id !== null) {
        $sql .= " AND `$id_field` != ?";
        $params[] = $except_id;
    }
    $stm = $pdo->prepare($sql);
    $stm->execute($params);
    return $stm->fetchColumn() == 0;
}

// Voucher
function voucher_effective_status($voucher) {
    $today = date('Y-m-d');
    if ($voucher->status == 'Active' && $voucher->valid_until < $today) {
        return 'Expired';
    }
    if ($voucher->status == 'Active' && $voucher->valid_from > $today) {
        return 'Scheduled';
    }
    return $voucher->status;
}

function is_exists($table, $field, $value) {
    global $pdo;
    $stm = $pdo->prepare("SELECT COUNT(*) FROM `$table` WHERE `$field` = ?");
    $stm->execute([$value]);
    return $stm->fetchColumn() > 0;
}

// =========================================================
// TABLE SORT HEADERS
// =========================================================
function table_headers($fields, $sort, $dir, $href = '') {
    $html = '';
    foreach ($fields as $label => $key) {
        $class = '';
        $new_dir = 'asc';
        if ($key == $sort) {
            $class = $dir;
            $new_dir = ($dir == 'asc') ? 'desc' : 'asc';
        }
        $html .= "<th><a class='$class' href='" . h("?sort=$key&dir=$new_dir$href") . "'>" . h($label) . "</a></th>";
    }
    return $html;
}

// =========================================================
// FILE UPLOAD
// =========================================================
function get_file($key) {
    $f = $_FILES[$key] ?? null;
    if ($f && $f['error'] === UPLOAD_ERR_OK) {
        return (object)$f;
    }
    return null;
}

function save_photo($f, $folder, $width = 200, $height = 200) {
    require_once root('lib/SimpleImage.php');

    // Auto-create upload folder if missing
    $dir = root($folder);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $photo = uniqid() . '.jpg';
    $path = "$dir/$photo";
    $img = new \claviska\SimpleImage();
    $img->fromFile($f->tmp_name)
        ->thumbnail($width, $height)
        ->toFile($path, 'image/jpeg');
    return $photo;
}

// =========================================================
// PDF TABLE REPORT
// =========================================================
function export_table_pdf($title, $headers, $rows, $filename) {
    require_once root('lib/TCPDF/tcpdf.php');

    $pdf = new TCPDF('L', PDF_UNIT, 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('Stationary Online Store');
    $pdf->SetTitle($title);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(15, 15, 15);
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 9);

    $html = '<style>
        h1 { font-size: 16px; margin-bottom: 2px; }
        .sub { font-size: 9px; color: #8a8175; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 8px; color: #8a8175; border-bottom: 1px solid #2b2622; padding: 4px; }
        td { font-size: 9px; padding: 4px; border-bottom: 1px solid #e4ddd0; }
    </style>
    <h1>Stationary Online Store</h1>
    <div class="sub">' . h($title) . ' &mdash; Generated ' . date('Y-m-d H:i') . ' &mdash; ' . count($rows) . ' record(s)</div>
    <table>
        <tr>';
    foreach ($headers as $head) {
        $html .= '<th>' . h($head) . '</th>';
    }
    $html .= '</tr>';
    foreach ($rows as $row) {
        $html .= '<tr>';
        foreach ($row as $cell) {
            $html .= '<td>' . h($cell) . '</td>';
        }
        $html .= '</tr>';
    }
    $html .= '</table>';

    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output($filename, 'D');
}

// PDF
function build_order_receipt_pdf($order, $items) {
    require_once root('lib/TCPDF/tcpdf.php');

    $shipping_address = $order->shipping_address ?: $order->address;

    $items_html = '';
    foreach ($items as $it) {
        $items_html .= '<tr>
            <td>' . h($it->product_name) . '</td>
            <td align="center">' . h($it->quantity) . '</td>
            <td align="right">RM ' . number_format($it->unit_price, 2) . '</td>
            <td align="right">RM ' . number_format($it->unit_price * $it->quantity, 2) . '</td>
        </tr>';
    }

    $html = '
    <style>
        h1 { font-size: 20px; text-align: center; margin-bottom: 2px; }
        .sub { font-size: 10px; text-align: center; color: #8a8175; letter-spacing: 2px; margin-bottom: 16px; }
        .row { font-size: 11px; margin-bottom: 4px; }
        .label { color: #8a8175; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th { text-align: left; font-size: 9px; color: #8a8175; border-bottom: 1px solid #2b2622; padding-bottom: 4px; }
        td { font-size: 11px; padding: 6px 0; border-bottom: 1px solid #e4ddd0; }
        .total { font-size: 14px; font-weight: bold; text-align: right; margin-top: 8px; }
        .footer { font-size: 10px; text-align: center; color: #8a8175; margin-top: 24px; }
    </style>
    <h1>Stationary Online Store</h1>
    <div class="sub">ORDER RECEIPT</div>

    <div class="row"><span class="label">Order #</span> ' . h($order->order_id) . '</div>
    <div class="row"><span class="label">Order Date</span> ' . h($order->order_date) . '</div>
    <div class="row"><span class="label">Status</span> ' . h($order->order_status) . '</div>
    <br>
    <div class="row"><span class="label">Customer</span> ' . h($order->username) . '</div>
    <div class="row"><span class="label">Email</span> ' . h($order->email) . '</div>
    <div class="row"><span class="label">Phone</span> ' . h($order->phone) . '</div>
    <div class="row"><span class="label">Address</span> ' . h($shipping_address) . '</div>
    <div class="row"><span class="label">Payment Method</span> ' . h($order->pay_name ?: 'Not specified') . '</div>

    <table>
        <tr><th>Item</th><th align="center">Qty</th><th align="right">Price</th><th align="right">Subtotal</th></tr>
        ' . $items_html . '
    </table>

    <div class="total">Total: RM ' . number_format($order->total_amount, 2) . '</div>

    <div class="footer">Thank you for shopping with us!</div>
    ';

    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('Stationary Online Store');
    $pdf->SetAuthor('Stationary Online Store');
    $pdf->SetTitle('Receipt - Order #' . $order->order_id);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(20, 20, 20);
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 11);
    $pdf->writeHTML($html, true, false, true, false, '');
    return $pdf;
}

// =========================================================
// PAGER
// =========================================================
require_once root('lib/SimplePager.php');

