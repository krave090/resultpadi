<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

function require_super(): array {
    if (empty($_SESSION['super_admin_id'])) {
        set_flash('error', 'Please log in as Super Admin.');
        redirect('super/login.php');
    }
    $stmt = db()->prepare("SELECT * FROM super_admins WHERE id=? LIMIT 1");
    $stmt->execute([(int)$_SESSION['super_admin_id']]);
    $a = $stmt->fetch();
    if (!$a) {
        unset($_SESSION['super_admin_id'], $_SESSION['super_admin_email']);
        set_flash('error', 'Session expired. Log in again.');
        redirect('super/login.php');
    }
    return $a;
}