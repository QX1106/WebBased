<?php require '../_base.php'; ?>
<?php

// Works for Member, Admin, and Super Admin — logout() just clears the
// session, so one file is enough. Send Admin/Super Admin back to the
// admin login, everyone else to the homepage.
$was_admin = $_user && in_array($_user->role, ['Admin', 'Super Admin']);
logout($was_admin ? '/admin/login.php' : '/');
