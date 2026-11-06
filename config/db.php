<?php
require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        // Read credentials from ZevCloud environment variables
        $host = getenv('DB_HOST') ?: 'ep-rapid-night-b9s7pray-pooler.c-7.eu-central-1.aws.neon.tech';
        $port = getenv('DB_PORT') ?: '5432';
        $user = getenv('DB_USER') ?: 'neondb_owner';
        $pass = getenv('DB_PASS') ?: '';
        $name = getenv('DB_NAME') ?: 'neondb';

        // The DSN for PostgreSQL. sslmode=require is essential for Neon.
        $dsn = "pgsql:host={$host};port={$port};dbname={$name};sslmode=require";

        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_TIMEOUT            => 30, // 30 seconds to allow Neon to wake up
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
