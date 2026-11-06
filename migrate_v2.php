<?php
require_once __DIR__ . '/config/db.php';
try {
    $pdo = db();
    
    // Create classes table
    $pdo->exec("CREATE TABLE IF NOT EXISTS classes (
        id SERIAL PRIMARY KEY,
        school_id INT NOT NULL REFERENCES schools(id) ON DELETE CASCADE,
        name VARCHAR(50) NOT NULL,
        UNIQUE (school_id, name)
    )");

    // Insert existing student classes into the new table
    $stmt = $pdo->query("SELECT DISTINCT school_id, class_name FROM students");
    while($row = $stmt->fetch()) {
        $check = $pdo->prepare("SELECT id FROM classes WHERE school_id = ? AND name = ?");
        $check->execute([$row['school_id'], $row['class_name']]);
        if (!$check->fetch()) {
            $pdo->prepare("INSERT INTO classes (school_id, name) VALUES (?, ?)")->execute([$row['school_id'], $row['class_name']]);
        }
    }

    // Add new columns to results and teacher_scores
    try { $pdo->exec("ALTER TABLE results ADD COLUMN affective_json TEXT DEFAULT NULL"); } catch(PDOException $e) {}
    try { $pdo->exec("ALTER TABLE results ADD COLUMN psychomotor_json TEXT DEFAULT NULL"); } catch(PDOException $e) {}
    try { $pdo->exec("ALTER TABLE teacher_scores ADD COLUMN remark VARCHAR(255) DEFAULT NULL"); } catch(PDOException $e) {}

    echo "<h1 style='color:green;'>Database Updated! You can delete this file.</h1>";
} catch (PDOException $e) {
    echo "<h1 style='color:red;'>Error: " . $e->getMessage() . "</h1>";
}