<?php
require_once __DIR__ . '/config/db.php';
try {
    $pdo = db();
    $pdo->exec("CREATE TABLE IF NOT EXISTS teachers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        school_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        class_assigned VARCHAR(50) NOT NULL,
        subject_assigned VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS teacher_scores (
        id INT AUTO_INCREMENT PRIMARY KEY,
        school_id INT NOT NULL,
        student_id INT NOT NULL,
        teacher_id INT NOT NULL,
        subject VARCHAR(100) NOT NULL,
        session VARCHAR(20) NOT NULL,
        term VARCHAR(20) NOT NULL,
        ca_json LONGTEXT NOT NULL,
        exam DECIMAL(6,2) NOT NULL,
        total DECIMAL(6,2) NOT NULL,
        UNIQUE KEY unique_score (student_id, subject, session, term),
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
    )");
    
    echo "<h1>Teacher Tables Created Successfully!</h1>";
    echo "<p>Delete this file and go to <a href='admin/teachers.php'>Admin -> Teachers</a></p>";
} catch (PDOException $e) { echo "<h1>Error: " . $e->getMessage() . "</h1>"; }