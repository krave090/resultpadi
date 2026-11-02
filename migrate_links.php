<?php
require_once __DIR__ . '/config/db.php';
try {
    $pdo = db();
    $pdo->exec("ALTER TABLE teachers ADD COLUMN magic_token VARCHAR(64) DEFAULT NULL");
    $pdo->exec("ALTER TABLE teachers ADD COLUMN token_expires_at DATETIME DEFAULT NULL");
    echo "<h1>Database Updated! Magic Link columns added.</h1>";
    echo "<p>You can now delete this file and go to <a href='admin/teachers.php'>Admin -> Teachers</a>.</p>";
} catch (PDOException $e) {
    if ($e->getCode() == '42S21') { echo "<h1>Columns already exist. You are good to go!</h1>"; }
    else { echo "<h1>Error: " . $e->getMessage() . "</h1>"; }
}