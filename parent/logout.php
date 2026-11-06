<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
unset($_SESSION['parent_id']);
set_flash('success', 'Logged out.');
redirect('index.php');