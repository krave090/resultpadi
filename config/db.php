<?php
require_once __DIR__ . '/config.php';

/**
 * Shared PDO connection (singleton).
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        // Read credentials from environment variables, with fallbacks for local development
        $host = getenv('DB_HOST') ?: 'localhost';
        $port = getenv('DB_PORT') ?: '3306';
        $name = getenv('DB_NAME') ?: 'resultpadi_db';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') ?: '';

        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => true, // Helps with the 10-connection limit
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo '<div style="font-family:system-ui;max-width:640px;margin:60px auto;padding:24px;border:1px solid #fca5a5;background:#fef2f2;border-radius:12px;color:#7f1d1d">';
            echo '<h2>Database connection failed</h2>';
            echo '<p>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
            echo '</div>';
            exit;
        }
    }

    return $pdo;
}