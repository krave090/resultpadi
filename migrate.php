<?php
require_once __DIR__ . '/config/db.php';
try {
    $pdo = db();
    // Add settings_json column if it doesn't exist
    $pdo->exec("ALTER TABLE schools ADD COLUMN settings_json TEXT DEFAULT NULL");
    echo "<h1>Migration Successful! Settings column added.</h1>";
    echo "<p>You can now delete this file (migrate.php) and go to the <a href='admin/settings.php'>Settings Page</a>.</p>";
} catch (PDOException $e) {
    if ($e->getCode() == '42S21') {
        echo "<h1>Column already exists. You are good to go!</h1>";
    } else {
        echo "<h1>Error: " . $e->getMessage() . "</h1>";
    }
}