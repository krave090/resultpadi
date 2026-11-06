<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
function require_parent(): array {
    if (empty($_SESSION['parent_id'])) { set_flash('error', 'Please log in to access your dashboard.'); redirect('parent/login.php'); }
    $stmt = db()->prepare("SELECT * FROM parents WHERE id=? LIMIT 1"); $stmt->execute([(int)$_SESSION['parent_id']]); $parent = $stmt->fetch();
    if (!$parent) { unset($_SESSION['parent_id']); redirect('parent/login.php'); }
    return $parent;
}