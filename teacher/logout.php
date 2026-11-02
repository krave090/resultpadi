<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
unset($_SESSION['teacher_id']);
set_flash('success', 'Teacher logged out.');
redirect('teacher/login.php');