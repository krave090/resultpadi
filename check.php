<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php');
if (!csrf_ok()) redirect('index.php?error=session');

$sid = (int)post('school_id', 0);
$adm = post('admission_no');

if ($sid <= 0 || $adm === '') redirect('index.php?error=missing');

$pdo = db();
$stmt = $pdo->prepare("SELECT * FROM students WHERE school_id=? AND admission_no=? LIMIT 1");
$stmt->execute([$sid, $adm]);
$student = $stmt->fetch();

if (!$student) {
    redirect('index.php?error=invalid');
}

// Store the student in session so they can proceed to PIN entry
$_SESSION['pending_check'] = [
    'student_id' => $student['id'],
    'school_id' => $sid,
    'surname' => $student['surname']
];

redirect('pin.php');