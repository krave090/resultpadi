<?php
require_once __DIR__ . '/config/db.php';

try {
    $pdo = db();
    
    // 1. Clear the table
    $pdo->exec("TRUNCATE TABLE super_admins");
    
    // 2. Create a fresh password hash
    $hash = password_hash('password123', PASSWORD_DEFAULT);
    
    // 3. Insert the new admin
    $stmt = $pdo->prepare("INSERT INTO super_admins (email, password_hash) VALUES (?, ?)");
    $stmt->execute(['admin@resultpadi.com', $hash]);
    
    echo "<h1 style='color:green; font-family:sans-serif;'>SUCCESS! Super Admin Reset.</h1>";
    echo "<h3 style='font-family:sans-serif;'>You can now log in with:</h3>";
    echo "<ul style='font-family:sans-serif; font-size:18px;'>";
    echo "<li><strong>Email:</strong> admin@resultpadi.com</li>";
    echo "<li><strong>Password:</strong> password123</li>";
    echo "</ul>";
    echo "<p style='font-family:sans-serif;'><a href='super/login.php'>Click here to go to the Login page</a></p>";
    
} catch (PDOException $e) {
    echo "<h1 style='color:red; font-family:sans-serif;'>ERROR</h1>";
    echo "<p style='font-family:sans-serif;'>" . htmlspecialchars($e->getMessage()) . "</p>";
}