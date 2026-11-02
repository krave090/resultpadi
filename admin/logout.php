<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
unset($_SESSION['school_id'], $_SESSION['school_name']);
set_flash('success', 'You have been logged out.');
redirect('admin/login.php');