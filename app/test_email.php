<?php
// TEMPORARY DEBUG SCRIPT — delete this file once email is working.
// Place this in your project root (same folder as _base.php) and open
// it directly in your browser, e.g. http://localhost:8000/test_email.php

require __DIR__ . '/lib/PHPMailer.php';
require __DIR__ . '/lib/SMTP.php';
require __DIR__ . '/lib/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

// CHANGE THIS to your own email so you can see if it actually arrives
$test_recipient = 'chanjw-wm24@student.tarc.edu.my';

$m = new PHPMailer(true);

try {
    $m->SMTPDebug = 2; // print the full SMTP conversation on screen
    $m->Debugoutput = function ($str, $level) {
        echo '<pre style="background:#eee;padding:4px;margin:2px 0;">' . htmlspecialchars($str) . '</pre>';
    };

    $m->isSMTP();
    $m->SMTPAuth = true;
    $m->Host = 'smtp.gmail.com';
    $m->Port = 587;
    $m->Username = '0602hehehe@gmail.com';       // <-- same as in _base.php
    $m->Password = 'gvix wjyh bzjp hufo';  // <-- same as in _base.php
    $m->CharSet = 'utf-8';
    $m->setFrom($m->Username, 'Stationary Online Store Test');

    $m->addAddress($test_recipient);
    $m->isHTML(true);
    $m->Subject = 'Test email from my project';
    $m->Body = '<p>If you see this, it worked!</p>';

    $m->send();

    echo '<h2 style="color:green;">SUCCESS — check your inbox (and spam folder) at ' . htmlspecialchars($test_recipient) . '</h2>';
} catch (PHPMailerException $e) {
    echo '<h2 style="color:red;">FAILED</h2>';
    echo '<p><strong>Error message:</strong> ' . htmlspecialchars($m->ErrorInfo) . '</p>';
}
