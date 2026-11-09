<?php
require __DIR__ . '/../includes/admin.php';

if (is_post()) {
    verify_csrf();
    unset($_SESSION['admin_id']);
    session_regenerate_id(true);
}
redirect('login.php');
