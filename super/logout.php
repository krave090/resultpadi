<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
unset($_SESSION['super_admin_id'], $_SESSION['super_admin_email']);
set_flash('success', 'You have been logged out.');
redirect('super/login.php');