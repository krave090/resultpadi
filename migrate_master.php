<?php
require_once __DIR__ . '/config/db.php';
try {
    $pdo = db();
    // Add PIN price to schools
    $pdo->exec("ALTER TABLE schools ADD COLUMN pin_price DECIMAL(10,2) DEFAULT 1000.00");
    
    // Create PINs table
    $pdo->exec("CREATE TABLE IF NOT EXISTS pins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        pin_code VARCHAR(20) NOT NULL UNIQUE,
        school_id INT NOT NULL,
        is_used TINYINT(1) DEFAULT 0,
        used_at DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
    )");
    
    echo "<h1>Master Migration Successful!</h1>";
    echo "<p>You can now delete this file.</p>";
} catch (PDOException $e) {
    if ($e->getCode() == '42S21') { echo "<h1>Columns already exist. You are good to go!</h1>"; }
    else { echo "<h1>Error: " . $e->getMessage() . "</h1>"; }
}