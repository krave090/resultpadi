<?php
require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        // ... (your existing environment variable setup)

        $dsn = "pgsql:host={$host};port={$port};dbname={$name};sslmode=require";

        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_TIMEOUT            => 30, // Increase timeout to 30 seconds
            ]);
        } catch (PDOException $e) {
            // ... error handling
        }
    }
    return $pdo;
}
