<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

function require_school(): array {
    if (empty($_SESSION['school_id'])) {
        set_flash('error', 'Please log in to continue.');
        redirect('admin/login.php');
    }
    $stmt = db()->prepare("SELECT * FROM schools WHERE id=? LIMIT 1");
    $stmt->execute([(int)$_SESSION['school_id']]);
    $school = $stmt->fetch();
    if (!$school) {
        unset($_SESSION['school_id'], $_SESSION['school_name']);
        set_flash('error', 'School account not found.');
        redirect('admin/login.php');
    }
    if ($school['status'] !== 'active') {
        unset($_SESSION['school_id'], $_SESSION['school_name']);
        set_flash('error', 'Account suspended. Contact ResultPadi.');
        redirect('admin/login.php');
    }
    return $school;
}