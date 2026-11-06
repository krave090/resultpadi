<?php
require_once __DIR__ . '/config/db.php';
try {
    $pdo = db();
    $pdo->exec("CREATE TABLE IF NOT EXISTS parents (
        id SERIAL PRIMARY KEY,
        email VARCHAR(150) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        name VARCHAR(100) NOT NULL,
        phone VARCHAR(20) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS parent_students (
        id SERIAL PRIMARY KEY,
        parent_id INT REFERENCES parents(id) ON DELETE CASCADE,
        student_id INT REFERENCES students(id) ON DELETE CASCADE,
        UNIQUE (parent_id, student_id)
    )");
    try { $pdo->exec("ALTER TABLE pins ADD COLUMN parent_id INT DEFAULT NULL"); } catch(PDOException $e) {}
    echo "<h1 style='color:green;'>Parent System Database Updated! You can delete this file.</h1>";
} catch (PDOException $e) {
    echo "<h1 style='color:red;'>Error: " . $e->getMessage() . "</h1>";
}