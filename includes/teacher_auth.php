<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';
function require_teacher(): array {
    if (empty($_SESSION['teacher_id'])) {
        set_flash('error', 'Please log in as a Teacher.');
        redirect('teacher/login.php');
    }
    $stmt = db()->prepare("SELECT t.*, s.school_name, s.settings_json FROM teachers t JOIN schools s ON s.id=t.school_id WHERE t.id=? LIMIT 1");
    $stmt->execute([(int)$_SESSION['teacher_id']]);
    $teacher = $stmt->fetch();
    if (!$teacher) {
        unset($_SESSION['teacher_id']);
        redirect('teacher/login.php');
    }
    return $teacher;
}