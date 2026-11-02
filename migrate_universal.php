<?php
require_once __DIR__ . '/config/db.php';
try {
    $pdo = db();
    // Drop the old pins table entirely to reset it without school ties
    $pdo->exec("DROP TABLE IF EXISTS pins");
    
    // Create the new universal pins table
    $pdo->exec("CREATE TABLE pins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        pin_code VARCHAR(20) NOT NULL UNIQUE,
        is_used TINYINT(1) DEFAULT 0,
        used_at DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    echo "<h1>Universal PIN System Installed!</h1>";
    echo "<p>All PINs are now global and not tied to specific schools.</p>";
    echo "<p>You can now delete this file and go to <a href='super/pins.php'>Super Admin -> Generate PINs</a>.</p>";
} catch (PDOException $e) {
    echo "<h1>Error: " . $e->getMessage() . "</h1>";
}