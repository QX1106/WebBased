<?php require '../_base.php'; ?>
<?php

// Works for both Member and Admin — logout() just clears the session,
// so one file is enough. Send Admins back to the admin login, everyone
// else to the homepage.
$was_admin = $_user && $_user->role == 'Admin';
logout($was_admin ? '/admin/login.php' : '/');
